<?php

namespace App\Http\Controllers;

use Database\Factories\CodeFactory; 
use App\Models\SalesorderDetail;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $validStatuses = ['pending', 'completed', 'cancelled']; // Define possible statuses
    
        $query = SalesOrder::query();

        // Apply status filter
        if ($status !== null && in_array($status, $validStatuses)) {
            $query->where('status', $status);
        }
    
        // Paginate the results
        $salesOrders = $query->paginate(10);
    
        // Define possible statuses
        $statuses = $validStatuses;
    
        return view('layouts.master.sales_order.index', compact('salesOrders', 'statuses'));
    }
    
    public function create()
    {
        // Fetch only active customers
        $customers = Customer::where('status', 'active')->get();
    
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Return the view with the customers and products data
        return view('layouts.master.sales_order.create-copy', compact('customers', 'products'));
    }
    
    
    // Function Store Original
    // public function store(Request $request)
    //     {
    //         // Validate the request data
    //         $validatedData = $request->validate([
    //             'customer_id' => 'required|exists:mstr_customer,id',
    //             'description' => 'nullable|string',
    //             'date' => 'required|date',
    //             'products' => 'required|json'
    //         ]);

    //         // Generate the sales order code using CodeFactory
    //         $salesOrderCode = CodeFactory::generateSalesOrderCode();

    //         // Begin a database transaction
    //         DB::beginTransaction();

    //         try {
    //             // Create a new sales order record
    //             $salesOrder = SalesOrder::create([
    //                 'code' => $salesOrderCode, // Use the generated code
    //                 'customer_id' => $validatedData['customer_id'],
    //                 'description' => $validatedData['description'],
    //                 'status' => 'pending',
    //                 'date' => $validatedData['date'],
    //             ]);

    //             // Decode products data
    //             $products = json_decode($validatedData['products'], true);

    //             // Insert products into the salesorder_detail table
    //             foreach ($products as $product) {
    //                 SalesOrderDetail::create([
    //                     'salesorder_id' => $salesOrder->id,
    //                     'product_id' => $product['product_id'],
    //                     'quantity' => $product['quantity'],
    //                     'price' => $product['price'],
    //                     'status' => 'pending',
    //                 ]);
    //             }

    //             // Commit transaction
    //             DB::commit();

    //             // Redirect to the sales orders index page with a success message
    //             return redirect()->route('sales_order.index')->with('success', 'Sales Order created successfully.');
    //         } catch (\Exception $e) {
    //             // Rollback transaction in case of error
    //             DB::rollback();

    //             // // Log the error for debugging
    //             // \Log::error('Failed to create Sales Order: ' . $e->getMessage());

    //             // Redirect back with error message
    //             return redirect()->back()->withErrors(['error' => 'Failed to create Sales Order. Please try again.']);
    //         }
    //     }

    public function store(Request $request)
    {
        // Get product details from the request
        $productIds = $request->input('product_ids', []);
        $quantities = $request->input('qtys', []);
        $prices = $request->input('price_eachs', []);
    
        // Filter out null values
        $filteredProductIds = array_filter($productIds, fn($value) => $value !== null);
        $filteredQuantities = array_filter($quantities, fn($value) => $value !== null);
        $filteredPrices = array_filter($prices, fn($value) => $value !== null);
    
        // Ensure all arrays have the same length after filtering
        if (count($filteredProductIds) !== count($filteredQuantities) || count($filteredProductIds) !== count($filteredPrices)) {
            return redirect()->back()->withErrors(['error' => 'Mismatch between product details.']);
        }
    
        // Prepare the request data for validation
        $request->merge([
            'product_ids' => array_values($filteredProductIds),
            'qtys' => array_values($filteredQuantities),
            'price_eachs' => array_values($filteredPrices),
        ]);
    
        // Validate the incoming request data
        $validatedData = $request->validate([
            'customer_id' => 'required|integer|exists:mstr_customer,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:mstr_product,id',
            'qtys' => 'required|array',
            'qtys.*' => 'required|integer|min:1',
            'price_eachs' => 'required|array',
            'price_eachs.*' => 'required|numeric|min:0',
        ]);
    
        // Generate the sales order code using CodeFactory
        $salesOrderCode = CodeFactory::generateSalesOrderCode();
    
        // Begin a database transaction for both SalesOrder and SalesOrderDetail
        DB::beginTransaction();
        // Create a new sales order record
        $salesOrder = SalesOrder::create([  
            'code' => $salesOrderCode,
            'customer_id' => $validatedData['customer_id'],
            'description' => $validatedData['description'],
            'status' => 'pending',
            'date' => $validatedData['date'],
        ]);
        
        // dd($salesOrder);

        // Get product details from the validated request data
        $productIds = $validatedData['product_ids'];
        $quantities = $validatedData['qtys'];
        $prices = $validatedData['price_eachs'];
    
        // Check if the arrays have the same length
        if (count($productIds) !== count($quantities) || count($productIds) !== count($prices)) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Mismatch between product details.']);
        }
    
        // Prepare an array to collect all detail data
        $detailsData = [];
    
        // Collect product details and filter out invalid entries
        foreach ($productIds as $index => $productId) {
            $quantity = $quantities[$index];
            $price = $prices[$index];
    
            // Add validated entry to the array
            $detailsData[] = [
                'salesorder_id' => $salesOrder->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'status' => 'pending',
            ];
        }
        
        // DD($detailsData);
        // Insert products into the salesorder_detail table in a batch
        SalesorderDetail::insert($detailsData);
    
        // Commit the transaction
        DB::commit();
    
        // Redirect with success message
        return redirect()->route('sales_order.show', ['id' => $salesOrder->id])
            ->with('success', 'Sales Order created successfully.')
            ->with('sales_order_code', $salesOrderCode);
    }
    
    
    public function show($id)
    {
        // Fetch the sales order and its details
        $salesOrder = SalesOrder::with('customer', 'details.product')->findOrFail($id);

        // Calculate total price
        $totalPrice = $salesOrder->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });

        // Return the view with the sales order and its details
        return view('layouts.master.sales_order.show', compact('salesOrder', 'totalPrice'));
    }

        public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $salesOrder = SalesOrder::findOrFail($id);
        $salesOrder->status = $request->input('status');
        $salesOrder->save();

        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Status updated successfully.');
    }
    
        public function edit($id)
    {
        // Fetch the sales order with details
        $salesOrder = SalesOrder::with('details.product')->findOrFail($id);

        // Convert date to Carbon object if it's not already
        $salesOrder->date = \Carbon\Carbon::parse($salesOrder->date);

        // Fetch customers and products
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('layouts.master.sales_order.edit', compact('salesOrder', 'customers', 'products'));
    }


    // Update method
    public function update(Request $request, $id)
    {
        // dd('Incoming request data:', $request->all());
        // Retrieve the sales order by ID
        $salesOrder = SalesOrder::findOrFail($id);
    
        // Update the sales order
        $salesOrder->update([
            'customer_id' => $request->input('customer_id'),
            'description' => $request->input('description'),
            'date' => \Carbon\Carbon::parse($request->input('date')),
        ]);
    
        // Get the arrays from the request
        $product_ids = $request->input('product_ids', []);
        
        $qtys = $request->input('qtys', []);
        $price_eachs = $request->input('price_eachs', []);
        $price_totals = $request->input('price_totals', []);
    
        // Initialize an empty array to hold the combined details
        $salesOrderDetails = [];
    
        // Determine the number of items in each array
        $length = count($product_ids);
    
        for ($i = 0; $i < $length; $i++) {
            // Only add to the details array if the product_id is not null
            if (!is_null($product_ids[$i])) {
                $salesOrderDetails[] = [
                    'product_id' => $product_ids[$i],
                    'quantity' => !is_null($qtys[$i]) ? (int)$qtys[$i] : null,
                    'price' => !is_null($price_eachs[$i]) ? (float)$price_eachs[$i] : null,
                    'price_total' => !is_null($price_totals[$i]) ? (float)$price_totals[$i] : null,
                ];
            }
        }
    
        // Dump the combined array to inspect
        // dd($salesOrderDetails);
    
        // Update existing details and insert new ones
        foreach ($salesOrderDetails as $detail) {
            SalesOrderDetail::updateOrCreate(
                [
                    'salesorder_id' => $salesOrder->id,
                    'product_id' => $detail['product_id']
                ],
                [
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'price_total' => $detail['price_total'],
                ]
            );
        }
        // dd($salesOrderDetails);
        // Delete details that are no longer in the $salesOrderDetails array
        SalesOrderDetail::where('salesorder_id', $salesOrder->id)
            ->whereNotIn('product_id', array_column($salesOrderDetails, 'product_id'))
            ->delete();
    
        // Redirect to the sales orders index page with a success message
        return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Sales Order updated successfully.');
    }

// SalesOrderController.php
// app/Http/Controllers/SalesOrderController.php
public function getProducts($salesOrderId)
{
    // Find the sales order and eager load the details with their associated product
    $salesOrder = SalesOrder::with(['details' => function ($query) {
        // Filter details to only include those with status "pending"
        $query->where('status', 'pending');
    }, 'details.product'])->find($salesOrderId);

    if ($salesOrder) {
        // Map the details to the required format
        $productsData = $salesOrder->details->map(function($detail) {
            return [
                'id' => $detail->product_id,
                'code' => $detail->product->code,
                'quantity' => $detail->quantity,
                'price' => $detail->price,
                'requested' => $detail->quantity // Assuming you still want to return quantity as requested
            ];
        });

        return response()->json(['products' => $productsData]);
    }

    return response()->json(['products' => []], 404);
}




    


// public function update(Request $request, $id)
// {
//     $request->validate([
//         'customer_id' => 'required|exists:customers,id',
//         'description' => 'nullable|string',
//         'date' => 'required|date',
//         'products' => 'nullable|array',
//         'products.*.product_id' => 'required|exists:products,id',
//         'products.*.quantity' => 'required|numeric|min:1',
//         'products.*.price' => 'required|numeric|min:0',
//     ]);

//     $salesOrder = SalesOrder::findOrFail($id);
//     $salesOrder->customer_id = $request->input('customer_id');
//     $salesOrder->description = $request->input('description');
//     $salesOrder->date = $request->input('date');
//     $salesOrder->save();

//     $salesOrder->details()->delete();
//     foreach ($request->input('products', []) as $productData) {
//         $salesOrder->details()->create([
//             'product_id' => $productData['product_id'],
//             'quantity' => $productData['quantity'],
//             'price' => $productData['price'],
//         ]);
//     }

//     return redirect()->route('sales_order.show', $salesOrder->id)->with('success', 'Sales Order updated successfully.');
// }


    // Add other methods for create, show, etc., if needed
}

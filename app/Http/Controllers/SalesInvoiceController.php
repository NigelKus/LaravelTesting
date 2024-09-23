<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\SalesorderDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Database\Factories\CodeFactory; 
use Illuminate\Support\Facades\Log;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Fetch all sales invoices with related sales orders and customers
        $salesInvoices = SalesInvoice::with(['salesOrder', 'customer'])->paginate(10);
    
        // Fetch all customers and sales orders if needed for other parts of the view
        $customers = Customer::all();
        $salesOrders = SalesOrder::all();
        
        // Pass the data to the view
        return view('layouts.transactional.sales_invoice.index', [
            'salesInvoices' => $salesInvoices,
            'customers' => $customers,
            'salesOrders' => $salesOrders
        ]);
    }
    
    

    public function create(Request $request)
    {
        // Fetch only active customers
        $customers = Customer::where('status', 'active')->get();
    
        // Fetch all products to populate the product dropdown
        $products = Product::where('status', 'active')->get();
    
        // Fetch all sales orders to populate the sales order dropdown
        $salesOrders = SalesOrder::where('status', 'pending')->get();
    
        $salesOrdersDetail = SalesorderDetail::where('status', 'pending')->get();
        // Optionally, fetch details of a specific sales order if provided
        // $salesOrderDetailsMap = [];
        // if ($request->has('salesorder_id')) {
        //     $salesOrder = SalesOrder::with('details.product')->find($request->input('salesorder_id'));
        //     if ($salesOrder) {
        //         $salesOrderDetailsMap = $salesOrder->details->mapWithKeys(function ($detail) {
        //             return [$detail => $detail->id];
        //         });
        //     }
        // }
        // Pass the data to the view
        return view('layouts.transactional.sales_invoice.create', [
            'customers' => $customers,
            'products' => $products,
            'salesOrders' => $salesOrders,
            'salesOrdersDetail' => $salesOrdersDetail
        ]);
    }
    
public function store(Request $request)
{   
    // Validate the incoming request data
    // dd($request->all());
    
    $filteredData = collect($request->input('requested'))->filter(function ($value, $key) {
        return $value > 0; // Keep only values greater than 0
    })->keys()->toArray();

    // Now filter other related fields to keep the same indexes
    $requestData = $request->all();
    foreach (['requested', 'qtys', 'price_eachs', 'price_totals', 'sales_order_detail_ids'] as $field) {
        $requestData[$field] = array_intersect_key($requestData[$field], array_flip($filteredData));
    }

    $requestData['price_eachs'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_eachs'], array_flip($filteredData)));
    $requestData['price_totals'] = array_map(fn($value) => str_replace(',', '', $value), array_intersect_key($requestData['price_totals'], array_flip($filteredData)));

    // Now validate the filtered data
    $request->replace($requestData);
    
    $request->validate([
        'customer_id' => 'required|exists:mstr_customer,id',
        'description' => 'nullable|string',
        'date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:date',
        'salesorder_id' => 'required|exists:mstr_salesorder,id',
        'requested.*' => 'required|integer|min:1', // Validate requested quantity
        'qtys.*' => 'required|integer|min:1',
        'price_eachs.*' => 'required|numeric|min:0',
        'price_totals.*' => 'required|numeric|min:0',
        'sales_order_detail_ids.*' => 'required|integer',
    ], [
        'requested.*.min' => 'Requested quantity must be at least 1.',
    ]);

    // dd($request->all());
    // dd($request);
    // dd($validator);
    // if ($validator->fails()) {
    //     return redirect()->back()->withErrors($validator)->withInput();
    // }

    // Begin a transaction to ensure atomicity
    DB::beginTransaction();
    
        
        // Create a new SalesInvoice record
        $salesInvoiceCode = CodeFactory::generateSalesInvoiceCode();

        $salesInvoice = new SalesInvoice();
        $salesInvoice->code = $salesInvoiceCode; // Set the generated code
        $salesInvoice->salesorder_id = $request->input('salesorder_id');
        $salesInvoice->customer_id = $request->input('customer_id');
        $salesInvoice->description = $request->input('description');
        $salesInvoice->date = $request->input('date');
        $salesInvoice->due_date = $request->input('due_date');
        $salesInvoice->status = 'pending'; // Assuming a default status
        $salesInvoice->save();
        
        // Get the sales order ID from the request
        $salesOrderId = $request->input('salesorder_id');
        
        // Fetch the corresponding sales order
        $existingSalesOrder = SalesOrder::with('details')->find($salesOrderId); // Eager load details
        if (!$existingSalesOrder) {
            throw new \Exception('Sales order not found.');
        }

        // Retrieve product details
        $requestedQuantities = $request->input('requested');
        $priceEaches = $request->input('price_eachs');
        $salesDetail = $request->input('sales_order_detail_ids');
        // dd($salesDetail);
        foreach ($salesDetail as $index => $salesOrderDetailId) {
            $salesOrderDetail = $existingSalesOrder->details->where('id', $salesOrderDetailId)->first();

            if (!$salesOrderDetail) {
                throw new \Exception('Sales order detail not found for ID ' . $salesOrderDetailId);
            }
            
            $productId = $salesOrderDetail->product_id;
            $requested = $requestedQuantities[$index] ?? 0;
            // dd($salesOrderDetailId);
            // $salesOrderDetailId = $salesOrderDetailIds[$index] ?? null;
            
            $salesInvoiceDetail = new SalesInvoiceDetail();
            $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
            $salesInvoiceDetail->product_id = $productId;
            $salesInvoiceDetail->quantity = $requested;
            $salesInvoiceDetail->salesdetail_id = $salesOrderDetail->id;
            $salesInvoiceDetail->price = $priceEaches[$index];
            $salesInvoiceDetail->status = 'pending'; // Assuming a default status
            // dd($salesInvoiceDetail);
            $salesInvoiceDetail->save();
            // dd($salesInvoiceDetail);
            // Update status of the sales order detail
            // dd($salesOrderDetailId);
            SalesorderDetail::checkAndUpdateStatus($salesOrderId, $productId, $salesOrderDetailId);
            // DD($statusUpdated);
        }

        // Commit the transaction
        DB::commit();

        // Redirect or return response
        return redirect()->route('sales_invoice.show', $salesInvoice->id)
        ->with('success', 'Sales invoice updated successfully.');
}


    public function show($id)
    {
        // Fetch the sales invoice with customer, details including related products, and invoicedetails
        $salesInvoice = SalesInvoice::with(['customer', 'details.product', 'salesOrder'])->findOrFail($id);
        
        // Calculate total price from invoicedetails
        $totalPrice = $salesInvoice->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });

        // dd([
        //     'salesInvoice' => $salesInvoice,
        //     'invoicedetails' => $salesInvoice->invoicedetails,
        //     'totalPrice' => $totalPrice,
        // ]);
    
        // Return the view with the sales invoice and its details
        return view('layouts.transactional.sales_invoice.show', [
            'salesInvoice' => $salesInvoice,
            'totalPrice' => $totalPrice,
        ]);
    }
    public function edit($id)
    {
        // Fetch the sales invoice with its details and related products
        $salesInvoice = SalesInvoice::with(['details.product'])->findOrFail($id);
        
        // Convert dates to Carbon instances
        $salesInvoice->date = \Carbon\Carbon::parse($salesInvoice->date);
        $salesInvoice->due_date = \Carbon\Carbon::parse($salesInvoice->due_date);
        
        // Fetch related customers, products, and sales orders
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $salesOrders = SalesOrder::where('status', 'active')->get();
        
        // Fetch sales order details for the given sales order ID
        // Fetch sales order details
        $salesOrderDetails = SalesOrderDetail::where('salesorder_id', $salesInvoice->salesorder_id)->get();
        
        // Map sales order details by product_id
        $salesOrderDetailsMap = $salesOrderDetails->keyBy('product_id');
        
        // Pass data to view
        return view('layouts.transactional.sales_invoice.edit', [
            'salesInvoice' => $salesInvoice,
            'customers' => $customers,
            'products' => $products,
            'salesOrders' => $salesOrders,
            'salesOrderDetailsMap' => $salesOrderDetailsMap,
        ]);
    }
    // public function update(Request $request, $id)
    // {
    //     // Find the sales invoice
    //     $salesInvoice = SalesInvoice::findOrFail($id);
        
    //     // Update sales invoice fields
    //     $salesInvoice->customer_id = $request['customer_id'];
    //     $salesInvoice->description = $request['description'];
    //     $salesInvoice->date = $request['date'];
    //     $salesInvoice->due_date = $request['due_date'];
    //     $salesInvoice->save();
        
    //     // Get existing sales invoice details
    //     $existingDetails = $salesInvoice->details;
        
    //     $requested = $request['requested'];
    //     $qtys = $request['qtys'];
    //     $priceEachs = $request['price_eachs'];
    //     $productIds = $request['product_id'];
    //     $sales_order_id = $request['sales_order_id'];
    //     $salesorderid = $sales_order_id;
    //     // dd($sales_order_id);
    //     // Track IDs of existing details to check for deletions later
    //     $existingDetailIds = $existingDetails->pluck('id')->toArray();
        

    //     foreach ($productIds as $i => $productId) {
    //         // Check if product ID is not null
    //         if ($productId !== null) {
    //             // Get the requested quantity
    //             $requestedQuantity = !empty($requested[$i]) ? (int)$requested[$i] : 0;
        
    //             // If requested quantity is 0, skip to the next iteration
    //             if ($requestedQuantity === 0) {
    //                 continue; // Do nothing for this iteration
    //             }
        
    //             $salesInvoiceDetail = null;
        
    //             // Check if we have an existing detail to update
    //             if (isset($existingDetailIds[$i]) && $existingDetailIds[$i] !== null) {
    //                 $salesInvoiceDetail = SalesInvoiceDetail::find($existingDetailIds[$i]);
    //             }
        
    //             if ($salesInvoiceDetail) {
    //                 // Update existing detail
    //                 $salesInvoiceDetail->quantity = $requestedQuantity;
    //                 $salesInvoiceDetail->price = (float)$priceEachs[$i];
    //                 $salesInvoiceDetail->save();
    //             } else {
    //                 // Create a new SalesInvoiceDetail instance
    //                 $salesInvoiceDetail = new SalesInvoiceDetail();
    //                 $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
    //                 $salesInvoiceDetail->product_id = $productId; // Product ID from the request
    //                 $salesInvoiceDetail->quantity = $requestedQuantity;
    //                 $salesInvoiceDetail->salesdetail_id = $request['salesdetail_id'][$i] !== null ? (int)$request['salesdetail_id'][$i] : null;
    //                 $salesInvoiceDetail->price = (float)$priceEachs[$i]; // Price from the request
    //                 $salesInvoiceDetail->status = 'pending';
    //                 $salesInvoiceDetail->save();
    //             }
    //             // Check and update the sales order status
    //         }
    //         SalesorderDetail::checkAndUpdateStatus($salesorderid, $productId, $request['salesdetail_id'][$i]);
    //     }
        

    //     // If there are any existing details not updated, we can delete them if they are not in the new request
    //     foreach ($existingDetails as $detail) {
    //         if (!in_array($detail->id, $existingDetailIds)) {
    //             $detail->delete();
    //         }
    //     }

    //     // Redirect or return response
    //     return redirect()->route('sales_invoice.show', $salesInvoice->id)
    //         ->with('success', 'Sales invoice updated successfully.');
    // }

    
    public function update(Request $request, $id)
    {   
        // Find the sales invoice
        // dd($request->all());

        $salesInvoice = SalesInvoice::findOrFail($id);
        
        // Update sales invoice fields
        $salesInvoice->customer_id = $request['customer_id'];
        $salesInvoice->description = $request['description'];
        $salesInvoice->date = $request['date'];
        $salesInvoice->due_date = $request['due_date'];
        // dd($salesInvoice);
        $salesInvoice->save();
        
        // Get existing sales invoice details
        $salesInvoice = SalesInvoice::with('details')->findOrFail($id);
        $invoiceDetails = $salesInvoice->details;

        // // Delete each detail
        foreach ($invoiceDetails as $detail) {
            $detail->delete();
        }

        // dd($invoiceDetails);
        $newDetails = []; // Array to hold newly created details
        $requested = $request['requested'];
        $qtys = $request['qtys'];
        $priceEachs = $request['price_eachs'];
        $productIds = $request['product_id'];
        $sales_order_id = $request['sales_order_id'];

        // dd($sales_order_id);

        foreach ($productIds as $i => $productId) {
            if ($productId !== null && (!empty($requested[$i]) && (int)$requested[$i] > 0)) { // Check if product ID is not null
                // Create a new SalesInvoiceDetail instance
                $salesInvoiceDetail = new SalesInvoiceDetail();
                $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
                $salesInvoiceDetail->product_id = $productId; // Product ID from the request
                $salesInvoiceDetail->quantity = $requested[$i] !== null ? (int)$requested[$i] : 0;
                $salesInvoiceDetail->salesdetail_id = $request['salesdetail_id'][$i] !== null ? (int)$request['salesdetail_id'][$i] : null;
                $salesInvoiceDetail->price = $priceEachs[$i] !== null ? (float)str_replace(',', '', $priceEachs[$i]) : 0; // Price from the request
                $salesInvoiceDetail->status = 'pending';
                
                // dd($salesInvoiceDetail);
                // dd($sales_order_id[$i], $productId,$request['salesdetail_id'][$i]);
                
                $salesInvoiceDetail->save();
                SalesorderDetail::checkAndUpdateStatus($sales_order_id[$i], $productId, $request['salesdetail_id'][$i]);
            }
        }
        // Redirect or return response
        return redirect()->route('sales_invoice.show', $salesInvoice->id)
            ->with('success', 'Sales invoice updated successfully.');
    }

    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $salesInvoice = SalesInvoice::findOrFail($id);
        $salesInvoice->status = $request->input('status');
        $salesInvoice->save();

        return redirect()->route('sales_invoice.show', $salesInvoice->id)->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        // Find the sales invoice or fail if not found
        $salesInvoice = SalesInvoice::findOrFail($id);
    
        // Update the status of the sales invoice to 'deleted'
        $salesInvoice->update([
            'status' => 'deleted'
        ]);
    
        // Iterate through each detail of the sales invoice
        foreach ($salesInvoice->details as $detail) {
            try {
                // Find the corresponding sales order detail using an appropriate relation
                $salesOrderDetail = $detail->salesOrderDetail; // Adjust this line as necessary
                
                // Call the adjustQuantityRemaining method on the SalesOrderDetail
                $salesOrderDetail->adjustQuantityRemaining($detail->quantity); // Adjust the quantity sent
                $detail->update(['status' => 'deleted']); // Update status of detail

                if ($salesOrderDetail->status === 'completed') {
                    $salesOrderDetail->update(['status' => 'pending']);
    
                    // Retrieve the associated sales order and update its status if necessary
                    $salesOrder = $salesOrderDetail->salesOrder; // Adjust this line as necessary
                    if ($salesOrder && $salesOrder->status === 'completed') {
                        $salesOrder->update(['status' => 'pending']);
                    }
                }

            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => $e->getMessage()]);
            }
        }
    
        // Redirect back to the sales invoice index with a success message
        return redirect()->route('sales_invoice.index')->with('success', 'Sales Invoice deleted successfully.');
    }
    
    
    
}
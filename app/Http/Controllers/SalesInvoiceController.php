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
    
        // Optionally, fetch details of a specific sales order if provided
        $salesOrderDetails = [];
        if ($request->has('salesorder_id')) {
            $salesOrder = SalesOrder::with('details.product')->find($request->input('salesorder_id'));
            if ($salesOrder) {
                $salesOrderDetails = $salesOrder->details->mapWithKeys(function ($detail) {
                    return [$detail->product_id => $detail->quantity];
                });
            }
        }
    
        // Pass the data to the view
        return view('layouts.transactional.sales_invoice.create', [
            'customers' => $customers,
            'products' => $products,
            'salesOrders' => $salesOrders,
            'salesOrderDetails' => $salesOrderDetails
        ]);
    }
    

    public function store(Request $request)
    {
        // dd($request->all());
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:mstr_customer,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'salesorder_id' => 'required|exists:mstr_salesorder,id',
            'requested.*' => 'required|integer|min:1', // Validate requested quantity
            'qtys.*' => 'required|integer|min:1',
            'price_eachs.*' => 'required|numeric|min:0',
            'price_totals.*' => 'required|numeric|min:0',
        ], [
            'requested.*.min' => 'Requested quantity must be at least 1.',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
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
        
        // dd($salesInvoice);

        $salesInvoice->save();
    
        // Get the sales order ID from the request
        $salesOrderId = $request->input('salesorder_id');
        
        // dd($salesOrderId);
    
        // Fetch the corresponding sales order
        $existingSalesOrder = SalesOrder::find($salesOrderId);
        if (!$existingSalesOrder) {
            // If the sales order does not exist, return an error
            return redirect()->back()->withErrors(['error' => 'Sales order not found.'])->withInput();
        }
    
        // Retrieve product details
        $requestedQuantities = $request->input('requested');
        $priceEaches = $request->input('price_eachs');
    
        foreach ($requestedQuantities as $index => $requested) {
            // Ensure that the correct product_id is used
            $productId = $existingSalesOrder->details[$index]->product_id ?? null;
    
            if (!$productId) {
                // If product details are missing, return an error
                return redirect()->back()->withErrors(['error' => 'Product details are missing.'])->withInput();
            }
    
            $salesInvoiceDetail = new SalesInvoiceDetail();
            $salesInvoiceDetail->invoicesales_id = $salesInvoice->id;
            $salesInvoiceDetail->product_id = $productId;
            $salesInvoiceDetail->quantity = $requested; // Use the requested quantity
            $salesInvoiceDetail->price = $priceEaches[$index];
            $salesInvoiceDetail->status = 'pending'; // Assuming a default status
            $salesInvoiceDetail->save();

            $statusUpdated = SalesorderDetail::checkAndUpdateStatus($salesOrderId, $productId, $requested);

            if (!$statusUpdated) {
                throw new \Exception('Failed to update sales order status.');
            }
    

        // Commit the transaction
        DB::commit();
    
        // Redirect or return response
        }
        return redirect()->route('sales_invoice.index')->with('success', 'Sales Invoice created successfully.');
    }

    public function show($id)
    {
        // Fetch the sales invoice with customer, details including related products, and invoicedetails
        $salesInvoice = SalesInvoice::with(['customer', 'invoicedetails.product'])->findOrFail($id);
    
        // Calculate total price from invoicedetails
        $totalPrice = $salesInvoice->invoicedetails->sum(function ($detail) {
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
        // Fetch the sales invoice with its details
        $salesInvoice = SalesInvoice::with(['details.product'])->findOrFail($id);
        
        // Convert dates to Carbon instances
        $salesInvoice->date = \Carbon\Carbon::parse($salesInvoice->date);
        $salesInvoice->due_date = \Carbon\Carbon::parse($salesInvoice->due_date);
        
        // Fetch related customers, products, and sales orders
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        $salesOrders = SalesOrder::where('status', 'active')->get();
        
        // Pass data to view
        return view('layouts.transactional.sales_invoice.edit', compact('salesInvoice', 'customers', 'products', 'salesOrders'));
    }
    
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $data['product_id'] = array_filter($data['product_id']);
        $data['requested'] = array_filter($data['requested']);
        $data['qtys'] = array_filter($data['qtys']);
        $data['price_eachs'] = array_filter($data['price_eachs']);
        $data['price_totals'] = array_filter($data['price_totals']);
        
        // Validate the request
        // $data = $request->validate([
        //     'customer_id' => 'required|exists:mstr_customer,id',
        //     'description' => 'nullable|string',
        //     'date' => 'required|date',
        //     'due_date' => 'required|date',
        //     'product_id' => 'required|exists:mstr_product,id',
        //     'requested' => 'array',
        //     'qtys' => 'array',
        //     'price_eachs' => 'array',
        //     'price_totals' => 'array',
        //     // Add validation rules for other fields as needed
        // ]);

        // Debug the entire validated data
        // dd($data);

        // Specifically debug the product IDs
        

        // Find the sales invoice
        $salesInvoice = SalesInvoice::findOrFail($id);
    
        // Update sales invoice fields
        $salesInvoice->customer_id = $data['customer_id'];
        $salesInvoice->description = $data['description'];
        $salesInvoice->date = $data['date'];
        $salesInvoice->due_date = $data['due_date'];
        // Update other sales invoice fields as needed
        // DD($salesInvoice);
        // $salesInvoice->save();
    
        // Update or create related sales invoice details
        // $details = $request->input('details', []);
        // DD($details);data
        
        // foreach ($details as $index => $detail) {
        //     $salesInvoiceDetail = SalesInvoiceDetail::where('invoicesales_id', $salesInvoice->id)
        //         ->where('product_id', $detail['product_id'])
        //         ->first();
                
            
        //     if ($salesInvoiceDetail) {
        //         // Update existing detail   
        //         $salesInvoiceDetail->quantity = $detail['requested'];
        //         $salesInvoiceDetail->price = $detail['price'];
        //         // $salesInvoiceDetail->save();
                
        //     } 

            $product_ids = $request->input('product_ids', []);
            DD($product_ids);
            $qtys = $request->input('requesteds', []);
            $price_eachs = $request->input('price_eachs', []);
            $price_totals = $request->input('price_totals', []);
        
            // Initialize an empty array to hold the combined details
            $salesInvoiceDetails = [];
        
            // Determine the number of items in each array
            $length = count($product_ids);
            
            for ($i = 0; $i < $length; $i++) {
                // Only add to the details array if the product_id is not null
                if (!is_null($product_ids[$i])) {
                    $salesInvoiceDetails[] = [
                        'product_id' => $product_ids[$i],
                        'quantity' => !is_null($qtys[$i]) ? (int)$qtys[$i] : null,
                        'price' => !is_null($price_eachs[$i]) ? (float)$price_eachs[$i] : null,
                        'price_total' => !is_null($price_totals[$i]) ? (float)$price_totals[$i] : null,
                    ];
                    DD($salesInvoiceDetails);
                }
            }
        
            // Dump the combined array to inspect
            // dd($salesOrderDetails);
        
            // Update existing details and insert new ones
            foreach ($salesInvoiceDetails as $detail) {
                SalesInvoiceDetail::updateOrCreate(
                    [
                        'salesinvoice_id' => $salesInvoice->id,
                        'product_id' => $detail['product_id']
                    ],
                    [
                        'quantity' => $detail['requested'],
                        'price' => $detail['price'],
                    ]
                );
            }
    
        // Optional: Update related sales order status if needed
        // ...
    
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
}
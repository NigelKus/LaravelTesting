@extends('adminlte::page')

@section('title', 'Sales Invoice Create')

@section('content_header')
    <h1>Sales Invoice</h1>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Sales Order Form -->
            <form method="POST" action="/admin/transactional/sales_invoice/store" class="form-horizontal">
                @csrf

                <!-- Customer Dropdown -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Sales Invoice Date</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" readonly>
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="due_date">Sales Invoice Due Date</label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ \Carbon\Carbon::now()->addDays(3)->format('Y-m-d') }}">
                    @error('due_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                
                <!-- Sales Order Dropdown -->
                <div class="form-group">
                    <label for="salesorder_id">Sales Order</label>
                    <select class="form-control @error('salesorder_id') is-invalid @enderror" id="salesorder_id" name="salesorder_id" required>
                        <option value="">Select a Sales Order</option>
                        @foreach($salesOrders as $salesOrder)
                            <option value="{{ $salesOrder->id }}" {{ old('salesorder_id') == $salesOrder->id ? 'selected' : '' }}>
                                {{ $salesOrder->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('salesorder_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Products Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Products</h3>
                    </div>
                    <div class="card-body">
                        <!-- Products Table -->
                        <table class="table table-bordered mt-3" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Requested</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Include existing rows dynamically -->
                                @foreach(range(0, 0) as $num)
                                    @include('layouts.transactional.sales_invoice.partials.product_line', [
                                        'product_id' => old('product_ids.' . ($num - 1)),
                                        'requested' => old('requested.' . ($num - 1)),
                                        'qty' => old('qtys.' . ($num - 1)),
                                        'price' => old('price_eachs.' . ($num - 1)),
                                        'price_total' => old('price_totals.' . ($num - 1)),
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Hidden Product Line Template -->
                        <table style="display: none;" id="product-line-template">
                            @include('layouts.transactional.sales_invoice.partials.product_line', [
                                'product_id' => '',
                                'requested' => '',
                                'qty' => '',
                                'price' => '',
                                'price_total' => '',
                            ])
                        </table>
                    </div>
                </div>

                <!-- Form Submit Button -->
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
        integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
        <script>
            $(document).ready(function() {
                // Initialize Select2 on the sales order dropdown
                $('#salesorder_id').select2({
                    placeholder: 'Select a Sales Order',
                    allowClear: true
                });
        
                // Event handler for sales order dropdown change
                $('#salesorder_id').change(function() {
                    var salesOrderId = $(this).val(); // Get the selected sales order ID
                    if (salesOrderId) {
                        $.ajax({
                            url: '/admin/master/sales_order/' + salesOrderId + '/products', // Adjusted URL
                            type: 'GET',
                            success: function(response) {
                                // Clear the existing rows in the products table
                                $('#products-table tbody').empty();
        
                                // Check if products array exists and has elements
                                if (response.products && response.products.length > 0) {
                                    // Append new rows to the products table
                                    response.products.forEach(function(product) {
                                        var productRow = `
                                            <tr class="product-line" id="product-line-${product.id}">
                                                <td>${product.code}</td>
                                                <td><input type="number" name="requested[]" class="form-control requested" value="${product.requested || 1}" min="1" /></td>
                                                <td><input type="number" name="qtys[]" class="form-control quantity" value="${product.quantity}" min="1" readonly/></td>
                                                <td><input type="text" name="price_eachs[]" class="form-control price-each" value="${product.price}" readonly /></td>
                                                <td><input type="text" name="price_totals[]" class="form-control price-total" value="${(product.requested || 1) * product.price}" readonly /></td>
                                                <td><button type="button" class="btn btn-danger btn-sm del-row">Remove</button></td>
                                            </tr>
                                        `;
                                        $('#products-table tbody').append(productRow);
                                    });
                                } else {
                                    // If no products are found, display a message
                                    $('#products-table tbody').append('<tr><td colspan="6">No products found.</td></tr>');
                                }
                            },
                            error: function(xhr) {
                                console.error('Failed to fetch products:', xhr.responseText);
                            }
                        });
                    } else {
                        // Clear the products table if no sales order is selected
                        $('#products-table tbody').empty();
                    }
                });
        
                // Event handler for input changes in the requested field
                $(document).on('input', '.requested', function() {
                    var $this = $(this);
                    var $row = $this.closest('tr');
                    var maxQty = parseInt($row.find('.quantity').val(), 10); // Get the quantity
                    var requestedQty = parseInt($this.val(), 10); // Get the requested quantity
                    
                    // Ensure the requested quantity is at least 1 and does not exceed available quantity
                    if (requestedQty < 1) {
                        $this.val(1); // Reset to minimum value of 1
                        alert('Requested quantity must be at least 1.');
                    } else if (requestedQty > maxQty) {
                        $this.val(maxQty); // Set requested quantity to maximum allowed
                        alert('Requested quantity cannot be greater than the available quantity.');
                    }
        
                    updateTotal($row); // Update the total price if needed
                });
        
                // Function to update the total price
                function updateTotal($row) {
                    var requestedQty = parseFloat($row.find('.requested').val()) || 0;
                    var priceEach = parseFloat($row.find('.price-each').val()) || 0;
                    var total = requestedQty * priceEach;
                    $row.find('.price-total').val(total.toFixed(2));
                }
        
                // Event handler for row removal
                $(document).on('click', '.del-row', function() {
                    $(this).closest('tr').remove(); // Remove the closest <tr> element
                });
            });
        </script>
        
@stop

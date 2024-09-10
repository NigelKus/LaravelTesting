@extends('adminlte::page')

@section('title', 'Edit Sales Invoice')

@section('content_header')
    <h1>Edit Sales Invoice</h1>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Sales Invoice Form -->
            <form method="POST" action="{{ route('sales_invoice.update', $salesInvoice->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')

                <!-- Sales Invoice Code -->
                <div class="form-group">
                    <label for="invoice_code">Invoice Code</label>
                    <input type="text" class="form-control @error('invoice_code') is-invalid @enderror" id="invoice_code" name="invoice_code" value="{{ old('invoice_code', $salesInvoice->code) }}" readonly>
                    @error('invoice_code')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Customer Dropdown -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $salesInvoice->customer_id) == $customer->id ? 'selected' : '' }}>
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
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $salesInvoice->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Sales Invoice Date</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $salesInvoice->date->format('Y-m-d')) }}" readonly>
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Due Date Field -->
                <div class="form-group">
                    <label for="due_date">Sales Invoice Due Date</label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date', $salesInvoice->due_date->format('Y-m-d')) }}">
                    @error('due_date')
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
                                @foreach($salesInvoice->details as $detail)
                                    @include('layouts.transactional.sales_invoice.partials.product_line_edit', [
                                        'product_id' => $detail->product_id,  
                                        'requested' => $detail->quantity, 
                                        'qty' => $detail->quantity,          
                                        'price' => $detail->price,            
                                        'price_total' => $detail->price * $detail->quantity, 
                                        'product' => $detail->product  
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Hidden Product Line Template -->
                        <table style="display: none;" id="product-line-template">
                            @include('layouts.transactional.sales_invoice.partials.product_line_edit', [
                                'product_id' => '',
                                'requested' => '',
                                'qty' => '',
                                'price' => '',
                                'price_total' => '',
                                'product' => '',
                            ])
                        </table>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('sales_invoice.show', $salesInvoice->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
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
            // Initialize Select2 on the sales order dropdown if it exists
            if ($('#salesorder_id').length) {
                $('#salesorder_id').select2({
                    placeholder: 'Select a Sales Order',
                    allowClear: true
                });
            }

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

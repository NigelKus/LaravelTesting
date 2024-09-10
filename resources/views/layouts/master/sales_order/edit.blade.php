@extends('adminlte::page')

@section('title', 'Sales Order Edit')

@section('content_header')
    <h1>Edit Sales Order</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Sales Order Form -->
            <form method="POST" action="{{ route('sales_order.update', $salesOrder->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')

                <!-- Customer Dropdown -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $salesOrder->customer_id) == $customer->id ? 'selected' : '' }}>
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
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $salesOrder->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Order Date</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ $salesOrder->date->format('Y-m-d') }}">
                    @error('date')
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
                        <!-- Add Product Button -->
                        <a href="#" id="btn-add-product-line" class="btn btn-sm btn-outline-info btn-labeled">
                            <span class="btn-label"></span>
                            Add Product Line
                        </a>
                        
                        <!-- Products Table -->
                        <table class="table table-bordered mt-3" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($salesOrder->details->isEmpty())
                                <!-- Default empty row if no details exist -->
                                @include('layouts.master.sales_order.partials.product_line_edit', [
                                    'product_id' => '',
                                    'qty' => '',
                                    'price' => '',
                                    'price_total' => '',
                                ])
                            @else
                                @foreach($salesOrder->details as $detail)
                                    @include('layouts.master.sales_order.partials.product_line_edit', [
                                        'product_id' => $detail->product_id,  
                                        'qty' => $detail->quantity,          
                                        'price' => $detail->price,            
                                        'price_total' => $detail->price * $detail->quantity, 
                                    ])
                                @endforeach
                            @endif
                            </tbody>
                        </table>

                        <!-- Hidden input to collect products data -->
                        <!-- Hidden Product Line Template -->
                        <table style="display: none;" id="product-line-template">
                            @include('layouts.master.sales_order.partials.product_line_edit', [
                                'product_id' => '',
                                'qty' => '',
                                'price' => '',
                                'price_total' => '',
                            ])
                        </table>

                    </div>
                </div>

                <!-- Form Submit and Cancel Buttons --> 
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary" >Save</button>
                    <a href="{{ route('sales_order.show', $salesOrder->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        var rowCounter = $('#products-table tbody tr').length; // Initialize rowCounter based on existing rows

        // Clone the hidden template
        var productLineTemplate = $('#product-line-template tbody').html().trim();

        // Add Product Button Click Event
        $('#btn-add-product-line').on('click', function(e) {
            e.preventDefault();
            rowCounter++;
            AddNewProductLine(rowCounter);
        });

        function AddNewProductLine(id) {
            let productLine = $(productLineTemplate).clone();

            // Update the ID and row number
            productLine.find('.product-line-number').text(id);

            // Reset input values
            productLine.find('.quantity').val('');
            productLine.find('.discount').val('');
            productLine.find('.price-each').val('');
            productLine.find('.price-total').val('');

            // Append the new row to the table
            $('#products-table tbody').append(productLine);

            // Initialize Select2 or similar plugin if needed
            productLine.find('.select-product').select2({
                placeholder: 'Select Product',
                allowClear: true
            });

            // Bind events for new elements
            bindEvents();
        }

        function bindEvents() {
            // Check for duplicate products
            $('#products-table').on('change', '.select-product', function() {
                var $select = $(this);
                var $row = $select.closest('tr');
                var selectedProductId = $select.val();

                // Check if the selected product is already in the table
                if (isProductDuplicate(selectedProductId, $row)) {
                    alert('This product has already been added.');
                    $select.val('').trigger('change');
                    return;
                }

                var price = $select.find('option:selected').data('price');
                var quantity = $row.find('.quantity').val();

                // Update price fields
                $row.find('.price-each').val(price);
                $row.find('.price-total').val(price * quantity);
            });

            // Update total price based on quantity
            $('#products-table').on('input', '.quantity', function() {
                var $input = $(this);
                var quantity = $input.val();
                var $row = $input.closest('tr');
                var priceEach = $row.find('.price-each').val();
                $row.find('.price-total').val(priceEach * quantity);
            });

            // Remove row
            $('#products-table').on('click', '.del-row', function() {
                $(this).closest('tr').remove();
            });
        }

        function isProductDuplicate(productId, $currentRow) {
            var isDuplicate = false;

            $('#products-table tbody tr').each(function() {
                var $row = $(this);
                var $select = $row.find('.select-product');
                var currentProductId = $select.val();

                if (currentProductId && currentProductId === productId && $row.get(0) !== $currentRow.get(0)) {
                    isDuplicate = true;
                    return false; // Break out of the loop
                }
            });

            return isDuplicate;
        }

        // Bind events for existing elements on page load
        $('#products-table tbody tr').each(function() {
            bindEvents();
        });
    });
</script>
    
@stop

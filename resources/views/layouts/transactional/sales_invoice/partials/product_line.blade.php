@php
    // Ensure $salesOrderDetails is an array if not provided
    $salesOrderDetails = $salesOrderDetails ?? [];
@endphp

@foreach ($products as $product)
    @php
        $product_id = $product->id;
        $details = $salesOrderDetails[$product_id] ?? null;
    @endphp

    @if ($details)
        @php
            // Extract details or provide default values
            $qty = $details['quantity'] ?? 0; 
            $product_code = $details['code'] ?? $product->code;
            $product_collection = $details['collection'] ?? $product->collection;
            $product_weight = $details['weight'] ?? $product->weight;
            $requested = $details['requested'] ?? 1; // Default to 1 if not specified
        @endphp

        <tr class="product-line" id="product-line-{{ $product_id }}">
            <td>
                <!-- Display product details based on sales order -->
                {{ $product_code }} - {{ $product_collection }} - {{ $product_weight }}
                <!-- Display error message for product selection if needed -->
                @error('product_ids.*')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </td>
            <td>
                <input type="number" name="requested[]" class="form-control requested" value="{{ $requested }}" min="1" />
                @error('requested.*')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </td>
            <td>
                <!-- Display quantity input -->
                <input type="number" name="qtys[]" class="form-control quantity @error('qtys.*') is-invalid @enderror" value="{{ $qty }}" min="1" readonly />
                @error('qtys.*')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </td>
            <td>
                <input type="text" name="price_eachs[]" class="form-control price-each" value="{{ $product->price }}" readonly />
            </td>
            <td>
                <input type="text" name="price_totals[]" class="form-control price-total" value="{{ $requested * $product->price }}" readonly />
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm del-row">
                    Remove
                </button>
            </td>
        </tr>
    @endif
@endforeach

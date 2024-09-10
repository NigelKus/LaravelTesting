@php
    // Debugging data can be uncommented for inspection
    // dd([
    //     'product_id' => $product_id,
    //     'requested' => $requested,
    //     'qty' => $qty,
    //     'price' => $price,
    //     'price_total' => $price_total,
    //     'product' => $product,
    // ]);
@endphp
<tr class="product-line" id="product-line-{{ $product_id }}">
    <td>
        <!-- Display product details -->
        {{ $product->code ?? 'N/A' }} - {{ $product->collection ?? 'N/A' }} - {{ $product->weight ?? 'N/A' }}
        @error('product_id.*')
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
        <input type="number" name="qtys[]" class="form-control quantity @error('qtys.*') is-invalid @enderror" value="{{ $qty }}" min="1" readonly />
        @error('qtys.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="text" name="price_eachs[]" class="form-control price-each" value="{{ $price }}" readonly />
    </td>
    <td>
        <input type="text" name="price_totals[]" class="form-control price-total" value="{{ $price_total }}" readonly />
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm del-row">
            Remove
        </button>
    </td>
    <!-- Hidden input for product_id -->
    <input type="hidden" name="product_id[]" value="{{ $product_id }}">
</tr>

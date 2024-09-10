@extends('adminlte::page')

@section('title', 'Sales Invoice Index')

@section('content_header')
    <h1>Sales Invoice</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sales Invoice List</h3>
            <div class="card-tools">
                <!-- Buttons, labels, and many other things can be placed here! -->
                <!-- Here is a label for example -->
            
                <!-- Create Button with Icon -->
                <a href="{{ route('sales_invoice.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Code</th> 
                            <th>Sales Order</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesInvoices as $salesInvoice)
                        <tr>
                            <td>{{ $salesInvoice->code }}</td>
                            <td>{{ $salesInvoice->salesOrder->code ?? 'N/A' }}</td>
                            <td>{{ $salesInvoice->customer->name ?? 'N/A' }}</td>
                            <td>{{ $salesInvoice->description }}</td>
                            <td>{{ $salesInvoice->status }}</td>
                            <td><a href="{{ route('sales_invoice.show', $salesInvoice->id) }}" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No Sales Invoice found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing
                    {{ $salesInvoices->firstItem() }}
                    to
                    {{ $salesInvoices->lastItem() }}
                    of
                    {{ $salesInvoices->total() }}
                </div>
                <div>
                    {{ $salesInvoices->appends(['status' => request('status')])->links() }}
                </div>
            </div>
        </div>
    </div>
@stop
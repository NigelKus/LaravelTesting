@extends('adminlte::page')

@section('title', 'Sales Orders Index')

@section('content_header')
    <h1>Sales Orders</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sales Orders List</h3>

            <!-- Filter Form -->
            <div class="card-tools">
                <a href="{{ route('sales_order.create-copy') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>
                            <form method="GET" action="{{ route('sales_order.index') }}" class="form-inline">
                                <select class="form-control" id="status" name="status" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesOrders as $order)
                        <tr>
                            <td>{{ $order->code }}</td>
                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                            <td>{{ $order->description }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->date)->format('Y-m-d') }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>
                                <a href="{{ route('sales_order.show', $order->id) }}" class="btn btn-info btn-sm">View</a>
                                <!-- Add other action buttons if needed -->
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No sales orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Card -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing
                    {{ $salesOrders->firstItem() }}
                    to
                    {{ $salesOrders->lastItem() }}
                    of
                    {{ $salesOrders->total() }}
                </div>
                <div>
                    {{ $salesOrders->appends(['status' => request('status')])->links() }}
                </div>
            </div>
        </div>
    </div>
@stop


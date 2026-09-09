@extends('layouts/layoutMaster')

@section('title', 'Orders & Sales - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Sales /</span> Orders</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Order #, customer name, email..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Order Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Payment Status</label>
          <select name="payment_status" class="form-select">
            <option value="">All Payments</option>
            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Orders Datatable Card -->
  <div class="card">
    <h5 class="card-header">All Orders ({{ $orders->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Payment</th>
            <th>Fulfillment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($orders as $order)
            <tr>
              <td><a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold">#{{ $order->order_number }}</a></td>
              <td><small class="text-muted">{{ $order->created_at->format('M d, Y H:i') }}</small></td>
              <td>
                <span class="d-block text-body fw-semibold">{{ $order->customer_name }}</span>
                <small class="text-muted">{{ $order->customer_email }}</small>
              </td>
              <td>
                <span class="badge bg-label-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                  {{ ucfirst($order->payment_status) }}
                </span>
                <small class="text-muted d-block">{{ strtoupper($order->payment_method) }}</small>
              </td>
              <td>
                <span class="badge bg-label-{{ $order->fulfillment_status === 'fulfilled' ? 'primary' : 'secondary' }}">
                  {{ ucfirst($order->fulfillment_status) }}
                </span>
              </td>
              <td>
                @php
                  $statusBadge = match($order->status) {
                    'completed' => 'bg-success',
                    'processing' => 'bg-info',
                    'pending' => 'bg-warning',
                    'cancelled', 'refunded' => 'bg-danger',
                    default => 'bg-secondary'
                  };
                @endphp
                <span class="badge {{ $statusBadge }}">{{ ucfirst($order->status) }}</span>
              </td>
              <td><span class="fw-bold text-primary">${{ number_format($order->grand_total, 2) }}</span></td>
              <td>
                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Order Details">
                  <i class="bx bx-show"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-receipt fs-3 mb-2 d-block"></i>
                No customer orders placed yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $orders->links() }}
    </div>
  </div>
</div>
@endsection

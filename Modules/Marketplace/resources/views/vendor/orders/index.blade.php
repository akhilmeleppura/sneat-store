@extends('layouts/layoutMaster')

@section('title', 'Vendor Orders - ' . $vendor->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Vendor /</span> Orders</h4>
      <small class="text-muted">Customer orders containing items fulfilled by your vendor store.</small>
    </div>
  </div>

  <div class="card">
    <h5 class="card-header">Fulfilled Orders ({{ $orders->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Your Items</th>
            <th>Your Gross</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($orders as $order)
            <tr>
              <td>
                <span class="fw-semibold font-monospace">#{{ $order->order_number }}</span>
              </td>
              <td>{{ $order->customer_name }}</td>
              <td>
                @foreach($order->items as $item)
                  <span class="d-block text-truncate" style="max-width: 200px;">
                    {{ $item->quantity }}x {{ $item->product_name }}
                  </span>
                @endforeach
              </td>
              <td>
                <span class="fw-bold">${{ number_format($order->items->sum('line_total'), 2) }}</span>
              </td>
              <td>
                <span class="badge bg-label-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'primary' : 'warning') }}">
                  {{ ucfirst($order->status) }}
                </span>
              </td>
              <td>{{ $order->created_at->format('M d, Y') }}</td>
              <td>
                <a href="{{ route('vendor.orders.show', $order->id) }}" class="btn btn-xs btn-outline-primary">
                  <i class="bx bx-show me-1"></i> Details
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No orders received yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($orders->hasPages())
      <div class="card-footer">
        {{ $orders->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

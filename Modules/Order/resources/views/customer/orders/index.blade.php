@extends('layouts/layoutFront')

@section('title', 'My Orders')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="row g-4">
    <!-- Account Sidebar Navigation -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center p-4">
          <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <span class="fs-2 fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
          </div>
          <h5 class="fw-bold mb-1">{{ auth()->user()->name }}</h5>
          <small class="text-muted d-block">{{ auth()->user()->email }}</small>
        </div>
        <div class="list-group list-group-flush">
          <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-home-alt me-2"></i> Account Dashboard
          </a>
          <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action active">
            <i class="bx bx-package me-2"></i> My Orders
          </a>
          <a href="{{ route('account.rma.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-revision me-2"></i> Returns & RMA
          </a>
          <a href="{{ route('account.loyalty') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-star me-2"></i> Loyalty Points
          </a>
          <a href="{{ route('account.referrals.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-share-alt me-2"></i> Referral Program
          </a>
          <a href="{{ route('account.payment_methods.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-credit-card me-2"></i> Payment Methods
          </a>
          <a href="{{ route('account.notifications') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-bell me-2"></i> Notifications
          </a>
          <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-user me-2"></i> Profile & Settings
          </a>
          <a href="{{ route('store.cart.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-cart me-2"></i> Shopping Cart
          </a>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="list-group-item list-group-item-action text-danger border-0 w-100 text-start">
              <i class="bx bx-log-out me-2"></i> Log Out
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Orders List -->
    <div class="col-lg-9">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
          <h3 class="fw-bold mb-1">Order History</h3>
          <p class="text-muted small mb-0">Track shipments, view receipts, and monitor delivery progress.</p>
        </div>
        <a href="{{ route('storefront.catalog') }}" class="btn btn-primary btn-sm">
          <i class="bx bx-shopping-bag me-1"></i> Continue Shopping
        </a>
      </div>

      <!-- Quick Filter Pills -->
      <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="{{ route('account.orders.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
          All Orders
        </a>
        <a href="{{ route('account.orders.index', ['status' => 'processing']) }}" class="btn btn-sm {{ request('status') === 'processing' ? 'btn-primary' : 'btn-outline-secondary' }}">
          <i class="bx bx-sync me-1"></i> Processing
        </a>
        <a href="{{ route('account.orders.index', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-primary' : 'btn-outline-secondary' }}">
          <i class="bx bx-check-circle me-1"></i> Completed
        </a>
        <a href="{{ route('account.orders.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">
          <i class="bx bx-time me-1"></i> Pending
        </a>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Order Number</th>
                <th>Items</th>
                <th>Total</th>
                <th>Order Status</th>
                <th>Fulfillment & Tracking</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $order)
                @php
                  $shipment = $order->latestShipment;
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('account.orders.show', $order->order_number) }}" class="fw-bold text-primary">
                      <code>#{{ $order->order_number }}</code>
                    </a>
                  </td>
                  <td>
                    @foreach($order->items as $item)
                      <span class="d-block text-truncate small" style="max-width: 200px;" title="{{ $item->product_name }}">
                        <strong>{{ $item->quantity }}x</strong> {{ $item->product_name }}
                      </span>
                    @endforeach
                  </td>
                  <td><span class="fw-bold">${{ number_format($order->grand_total, 2) }}</span></td>
                  <td>
                    <span class="badge bg-label-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'primary' : 'warning') }}">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>
                    @if($shipment)
                      <div class="d-flex flex-column gap-1">
                        <span class="badge {{ $shipment->status_badge_class }}">
                          <i class="bx bx-package me-1"></i>{{ $shipment->status_label }}
                        </span>
                        @if($shipment->tracking_number)
                          <small class="text-muted font-monospace">
                            {{ $shipment->carrier }}: {{ $shipment->tracking_number }}
                          </small>
                        @endif
                      </div>
                    @elseif($order->shippingMethod)
                      <span class="badge bg-label-secondary">
                        <i class="bx bx-car me-1"></i>{{ $order->shippingMethod->name }}
                      </span>
                    @else
                      <span class="badge bg-label-light text-muted">Standard Fulfillment</span>
                    @endif
                  </td>
                  <td><small class="text-muted">{{ $order->created_at->format('M d, Y') }}</small></td>
                  <td class="text-end">
                    <a href="{{ route('account.orders.show', $order->order_number) }}" class="btn btn-sm btn-outline-primary">
                      <i class="bx bx-map-pin me-1"></i> Track & Receipt
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bx bx-package fs-1 d-block mb-2 text-secondary"></i>
                    You have not placed any orders yet.
                  </td>
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
  </div>
</section>
@endsection

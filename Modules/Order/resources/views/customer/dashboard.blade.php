@extends('layouts/layoutFront')

@section('title', 'My Account Dashboard')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="row g-4">
    <!-- Account Sidebar Navigation -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center p-4">
          <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <span class="fs-2 fw-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
          </div>
          <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
          <small class="text-muted d-block">{{ $user->email }}</small>
        </div>
        <div class="list-group list-group-flush">
          <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action active">
            <i class="bx bx-home-alt me-2"></i> Account Dashboard
          </a>
          <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-package me-2"></i> My Orders ({{ $totalOrders }})
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

    <!-- Main Content Area -->
    <div class="col-lg-9">
      <h3 class="fw-bold mb-4">Account Overview</h3>

      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <span class="text-muted">Total Orders</span>
              <h3 class="fw-bold mb-0 text-primary">{{ $totalOrders }}</h3>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <span class="text-muted">In Progress</span>
              <h3 class="fw-bold mb-0 text-warning">{{ $pendingOrders }}</h3>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <span class="text-muted">Total Spent</span>
              <h3 class="fw-bold mb-0 text-success">${{ number_format($totalSpent, 2) }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold">Recent Orders</h5>
          <a href="{{ route('account.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentOrders as $order)
                <tr>
                  <td><code>#{{ $order->order_number }}</code></td>
                  <td>{{ $order->items->count() }} item(s)</td>
                  <td><span class="fw-bold">${{ number_format($order->grand_total, 2) }}</span></td>
                  <td>
                    <span class="badge bg-label-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'primary' : 'warning') }}">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>{{ $order->created_at->format('M d, Y') }}</td>
                  <td>
                    <a href="{{ route('account.orders.show', $order->order_number) }}" class="btn btn-xs btn-outline-primary">
                      Track Order
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">You have not placed any orders yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

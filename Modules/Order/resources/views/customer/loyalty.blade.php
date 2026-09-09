@extends('layouts/layoutFront')

@section('title', 'Loyalty Rewards & Points - My Account')

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
            <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-home-alt me-2"></i> Account Dashboard
            </a>
            <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-package me-2"></i> My Orders
            </a>
            <a href="{{ route('account.rma.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-revision me-2"></i> Returns & RMA
            </a>
            <a href="{{ route('account.loyalty') }}" class="list-group-item list-group-item-action active">
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
          </div>
        </div>
      </div>

      <!-- Main Loyalty Content Area -->
      <div class="col-lg-9">
        <!-- Hero Balance Card -->
        <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #696cff 0%, #8592a3 100%);">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
              <div>
                <span class="badge bg-white text-primary mb-2 fw-bold text-uppercase fs-6 px-3 py-1">
                  <i class="bx bx-crown me-1"></i>
                  @if($balance >= 1000)
                    Platinum Tier VIP
                  @elseif($balance >= 500)
                    Gold Tier Member
                  @elseif($balance >= 200)
                    Silver Tier Member
                  @else
                    Bronze Member
                  @endif
                </span>
                <h1 class="text-white fw-bold mb-1 display-5">{{ number_format($balance) }}</h1>
                <p class="text-white-50 mb-0 fs-5">Available Loyalty Reward Points</p>
              </div>
              <div class="bg-white bg-opacity-10 p-3 rounded-3 text-md-end">
                <span class="text-white-50 small d-block">Estimated Store Credit</span>
                <h2 class="text-white fw-bold mb-0">${{ number_format($creditValue, 2) }}</h2>
                <small class="text-white-50">Redeemable at checkout</small>
              </div>
            </div>

            <hr class="my-4 border-white opacity-25">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
              <span class="text-white small">
                <i class="bx bx-info-circle me-1"></i> 100 Points = $5.00 discount on any online order.
              </span>
              <a href="{{ route('storefront.catalog') }}" class="btn btn-light btn-sm text-primary fw-bold">
                <i class="bx bx-shopping-bag me-1"></i> Start Shopping & Earn
              </a>
            </div>
          </div>
        </div>

        <!-- How It Works Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-3 text-center">
                <div class="avatar avatar-md bg-label-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                  <i class="bx bx-shopping-bag fs-4"></i>
                </div>
                <h6 class="fw-bold mb-1">1. Shop & Earn</h6>
                <p class="text-muted small mb-0">Earn 1 point for every $1 spent across all eligible items.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-3 text-center">
                <div class="avatar avatar-md bg-label-success rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                  <i class="bx bx-check-shield fs-4"></i>
                </div>
                <h6 class="fw-bold mb-1">2. Accumulate</h6>
                <p class="text-muted small mb-0">Points never expire as long as your account remains active.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-3 text-center">
                <div class="avatar avatar-md bg-label-warning rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                  <i class="bx bx-gift fs-4"></i>
                </div>
                <h6 class="fw-bold mb-1">3. Redeem & Save</h6>
                <p class="text-muted small mb-0">Slide your points discount at checkout for instant cash savings.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Points Ledger History -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="mb-0 fw-bold">Points Activity Ledger</h5>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Activity Description</th>
                  <th>Order #</th>
                  <th>Points Change</th>
                  <th>Balance</th>
                </tr>
              </thead>
              <tbody>
                @forelse($ledger as $entry)
                  <tr>
                    <td>{{ $entry->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <span class="fw-semibold text-heading">{{ $entry->description }}</span>
                    </td>
                    <td>
                      @if($entry->order)
                        <a href="{{ route('account.orders.show', $entry->order->order_number) }}">
                          #{{ $entry->order->order_number }}
                        </a>
                      @else
                        <span class="text-muted small">-</span>
                      @endif
                    </td>
                    <td>
                      @if($entry->points_change > 0)
                        <span class="fw-bold text-success">+{{ number_format($entry->points_change) }} pts</span>
                      @else
                        <span class="fw-bold text-danger">{{ number_format($entry->points_change) }} pts</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge bg-label-dark">{{ number_format($entry->balance_after) }} pts</span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="bx bx-star bx-lg d-block mb-2 text-secondary"></i>
                      No points transactions recorded yet. Place an order to earn your first loyalty reward!
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($ledger->hasPages())
            <div class="card-footer d-flex justify-content-end">
              {{ $ledger->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

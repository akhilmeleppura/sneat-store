@extends('layouts/layoutFront')

@section('title', 'Referral & Affiliate Program')

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
            <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-package me-2"></i> My Orders
            </a>
            <a href="{{ route('account.rma.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-revision me-2"></i> Returns & RMA
            </a>
            <a href="{{ route('account.loyalty') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-star me-2"></i> Loyalty Points
            </a>
            <a href="{{ route('account.referrals.index') }}" class="list-group-item list-group-item-action active">
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
        @if(session('success'))
          <div class="alert alert-success alert-dismissible" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <!-- Referral Link Hero Card -->
        <div class="card border-0 shadow-sm mb-4 bg-label-primary">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div>
                <h4 class="fw-bold text-primary mb-1">
                  <i class="bx bx-gift me-2"></i> Earn {{ $affiliate->commission_rate }}% Commission on Every Order!
                </h4>
                <p class="text-body mb-0">Share your exclusive link with friends, audience, or followers and earn recurring revenue on their purchases.</p>
              </div>
              <span class="badge bg-primary fs-6 px-3 py-2">Code: {{ $affiliate->affiliate_code }}</span>
            </div>

            <hr class="my-3">

            <div class="row align-items-center g-3">
              <div class="col-md-8">
                <div class="input-group">
                  <input type="text" id="referralUrlInput" class="form-control" value="{{ $affiliate->referral_url }}" readonly>
                  <button class="btn btn-primary" type="button" onclick="copyReferralLink()">
                    <i class="bx bx-copy me-1"></i> Copy Link
                  </button>
                </div>
              </div>
              <div class="col-md-4 d-flex gap-2">
                <a href="https://api.whatsapp.com/send?text=Check%20out%20these%20awesome%20products:%20{{ urlencode($affiliate->referral_url) }}" target="_blank" class="btn btn-outline-success flex-fill" title="Share via WhatsApp">
                  <i class="bx bxl-whatsapp me-1"></i> WhatsApp
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($affiliate->referral_url) }}&text=Shop%20premium%20products%20online!" target="_blank" class="btn btn-outline-info flex-fill" title="Share on X">
                  <i class="bx bxl-twitter me-1"></i> Post
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card border-0 shadow-sm">
              <div class="card-body p-3 text-center">
                <span class="text-muted d-block small">Referred Orders</span>
                <h3 class="fw-bold mb-0 text-primary">{{ $referrals->total() }}</h3>
                <small class="text-muted">Total purchases made</small>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-0 shadow-sm">
              <div class="card-body p-3 text-center">
                <span class="text-muted d-block small">Pending Commission</span>
                <h3 class="fw-bold mb-0 text-warning">${{ number_format($affiliate->pending_earnings, 2) }}</h3>
                <small class="text-muted">Awaiting payout processing</small>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border-0 shadow-sm">
              <div class="card-body p-3 text-center">
                <span class="text-muted d-block small">Paid Commission</span>
                <h3 class="fw-bold mb-0 text-success">${{ number_format($affiliate->paid_earnings, 2) }}</h3>
                <small class="text-muted">Disbursed to your account</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Referral Conversion Ledger Table -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Referral History</h5>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Order Ref</th>
                  <th>Customer Email</th>
                  <th>Order Value</th>
                  <th>Rate</th>
                  <th>Commission</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($referrals as $ref)
                  <tr>
                    <td><code>#{{ $ref->order?->order_number ?? 'ORD-' . $ref->order_id }}</code></td>
                    <td>{{ Str::mask($ref->customer_email, '*', 3, 4) }}</td>
                    <td>${{ number_format($ref->order_amount, 2) }}</td>
                    <td>{{ $ref->commission_rate }}%</td>
                    <td><strong class="text-success">+${{ number_format($ref->commission_amount, 2) }}</strong></td>
                    <td>
                      @if($ref->status === 'paid')
                        <span class="badge bg-label-success">Paid</span>
                      @elseif($ref->status === 'approved')
                        <span class="badge bg-label-info">Approved</span>
                      @else
                        <span class="badge bg-label-warning">Pending</span>
                      @endif
                    </td>
                    <td>{{ $ref->created_at->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                      No referral orders recorded yet. Share your referral link to begin earning commissions!
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($referrals->hasPages())
            <div class="card-footer d-flex justify-content-end">
              {{ $referrals->links() }}
            </div>
          @endif
        </div>

        <!-- Payout Settings -->
        <div class="card border-0 shadow-sm">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Payout Preferences</h5>
          </div>
          <div class="card-body pt-4">
            <form action="{{ route('account.referrals.payout') }}" method="POST">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="payout_method">Payout Method</label>
                  <select name="payout_method" id="payout_method" class="form-select">
                    <option value="paypal" {{ $affiliate->payout_method === 'paypal' ? 'selected' : '' }}>PayPal</option>
                    <option value="bank_transfer" {{ $affiliate->payout_method === 'bank_transfer' ? 'selected' : '' }}>Direct Bank Wire / IBAN</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="payout_account">Account Email / Details</label>
                  <input type="text" name="payout_account" id="payout_account" class="form-control" value="{{ $affiliate->payout_account }}" placeholder="your-paypal-email@example.com" required>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Payout Settings</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  function copyReferralLink() {
    const input = document.getElementById('referralUrlInput');
    input.select();
    navigator.clipboard.writeText(input.value);
    alert('Referral link copied to clipboard!');
  }
</script>
@endsection

@extends('layouts/layoutFront')

@section('title', 'Become a Seller - Marketplace Merchant Onboarding')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">

    <!-- Hero Header -->
    <div class="text-center mb-5">
      <span class="badge bg-label-primary mb-2">Marketplace Partner Program</span>
      <h2 class="fw-bold mb-2">Sell on Sneat Store</h2>
      <p class="text-muted mx-auto" style="max-width: 650px;">
        Join hundreds of verified merchants reaching thousands of customers daily. Manage your products, fulfill orders, and receive automated payout disbursements.
      </p>
    </div>

    <!-- Value Propositions -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm text-center p-4">
          <div class="avatar avatar-lg bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bx bx-globe fs-2"></i>
          </div>
          <h5 class="fw-bold mb-2">Omnichannel Reach</h5>
          <p class="text-muted small mb-0">List your products across multiple localized store contexts and international currencies with dynamic FX conversion.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm text-center p-4">
          <div class="avatar avatar-lg bg-label-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bx bx-wallet fs-2"></i>
          </div>
          <h5 class="fw-bold mb-2">Transparent Payouts</h5>
          <p class="text-muted small mb-0">Keep 85% of your sales revenue. Automated ledger tracking with on-demand bank wire disbursements.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm text-center p-4">
          <div class="avatar avatar-lg bg-label-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bx bx-bar-chart-alt-2 fs-2"></i>
          </div>
          <h5 class="fw-bold mb-2">Vendor Analytics</h5>
          <p class="text-muted small mb-0">Gain real-time insights into your top-selling products, commission breakdowns, customer reviews, and fulfillment metrics.</p>
        </div>
      </div>
    </div>

    @if(isset($pendingVendor) && $pendingVendor->status === 'pending')
      <div class="card border-0 shadow-sm mb-5 border-start border-warning border-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="bx bx-time-five fs-3"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1">Application Under Review</h5>
              <p class="text-muted mb-0">
                Your application for <strong>{{ $pendingVendor->name }}</strong> was submitted on {{ $pendingVendor->created_at->format('M d, Y') }}. Our marketplace administrators are reviewing your submission and will activate your seller account shortly.
              </p>
            </div>
          </div>
        </div>
      </div>
    @else
      <!-- Application Form -->
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom p-4">
              <h4 class="fw-bold mb-1">Merchant Registration Form</h4>
              <p class="text-muted small mb-0">Fill out your store and payout information below to submit your seller application.</p>
            </div>
            <div class="card-body p-4">

              @if(session('success'))
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                  <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              @endif

              @if($errors->any())
                <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                  <ul class="mb-0">
                    @foreach($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              @endif

              <form action="{{ route('storefront.vendor.register.post') }}" method="POST">
                @csrf

                <h5 class="fw-bold mb-3 text-primary"><i class="bx bx-store me-1"></i> Store Information</h5>
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label">Store / Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Acme Tech Gear" value="{{ old('name') }}" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Business Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="seller@example.com" value="{{ old('email', auth()->user()->email ?? '') }}" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Contact Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="{{ old('phone') }}">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Store Bio & Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Tell buyers about your products, quality assurance, and brand story...">{{ old('description') }}</textarea>
                  </div>
                </div>

                <h5 class="fw-bold mb-3 text-primary"><i class="bx bx-credit-card me-1"></i> Payout & Banking Details</h5>
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" placeholder="e.g. Chase, Wells Fargo, Bank of America" value="{{ old('bank_name') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Account Number / IBAN</label>
                    <input type="text" name="account_number" class="form-control" placeholder="e.g. 1234567890" value="{{ old('account_number') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Routing Number / Swift Code</label>
                    <input type="text" name="routing_number" class="form-control" placeholder="e.g. 021000021" value="{{ old('routing_number') }}">
                  </div>
                </div>

                <div class="form-check mb-4">
                  <input class="form-check-input" type="checkbox" id="termsCheck" required>
                  <label class="form-check-label small text-muted" for="termsCheck">
                    I agree to the Sneat Store Marketplace Partner Agreement, 15% standard commission rate, and merchant code of conduct.
                  </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                  <i class="bx bx-paper-plane me-1"></i> Submit Seller Application
                </button>
              </form>

            </div>
          </div>
        </div>
      </div>
    @endif

  </div>
</section>
@endsection

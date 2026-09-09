@extends('layouts/layoutFront')

@section('title', 'Saved Payment Methods')

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
            <a href="{{ route('account.loyalty') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-star me-2"></i> Loyalty Points
            </a>
            <a href="{{ route('account.referrals.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-share-alt me-2"></i> Referral Program
            </a>
            <a href="{{ route('account.payment_methods.index') }}" class="list-group-item list-group-item-action active">
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

      <!-- Main Payment Methods Content -->
      <div class="col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
          <div>
            <h3 class="fw-bold mb-1">Payment Vault & Saved Cards</h3>
            <p class="text-muted mb-0">Manage tokenized payment methods for swift, secure checkout.</p>
          </div>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
            <i class="bx bx-plus me-1"></i> Add New Card
          </button>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <div class="d-flex align-items-center">
              <i class="bx bx-check-circle fs-4 me-2"></i>
              <div>{{ session('success') }}</div>
            </div>
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

        @if($paymentMethods->isEmpty())
          <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
              <div class="avatar avatar-xl bg-label-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="bx bx-credit-card fs-1 text-secondary"></i>
              </div>
              <h5 class="fw-bold mb-2">No Saved Payment Methods</h5>
              <p class="text-muted max-w-400 mx-auto mb-4">
                You haven't vaulted any cards yet. Add your credit or debit card to streamline future checkouts across our store.
              </p>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
                <i class="bx bx-plus me-1"></i> Add Your First Card
              </button>
            </div>
          </div>
        @else
          <div class="row g-3">
            @foreach($paymentMethods as $pm)
              <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 {{ $pm->is_default ? 'border-primary border-2' : '' }}">
                  <div class="card-body position-relative">
                    @if($pm->is_default)
                      <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                        <i class="bx bx-check me-1"></i> Default Card
                      </span>
                    @endif

                    <div class="d-flex align-items-center mb-3">
                      <div class="avatar avatar-md bg-label-info rounded me-3 d-flex align-items-center justify-content-center">
                        @if(strtolower($pm->card_brand) === 'visa')
                          <i class="bx bxl-visa fs-2 text-info"></i>
                        @elseif(strtolower($pm->card_brand) === 'mastercard')
                          <i class="bx bxl-mastercard fs-2 text-danger"></i>
                        @else
                          <i class="bx bx-credit-card fs-2 text-primary"></i>
                        @endif
                      </div>
                      <div>
                        <h6 class="fw-bold text-uppercase mb-0">{{ $pm->card_brand ?: 'Card' }}</h6>
                        <small class="text-muted">Token: {{ substr($pm->payment_method_token, 0, 10) }}...</small>
                      </div>
                    </div>

                    <div class="mb-3">
                      <div class="fs-5 fw-bold font-monospace letter-spacing-1">
                        •••• •••• •••• {{ $pm->card_last_four ?? '••••' }}
                      </div>
                      <small class="text-muted">
                        Expires: {{ $pm->card_exp_month }}/{{ $pm->card_exp_year }} &bull; Gateway: <span class="text-capitalize">{{ $pm->gateway }}</span>
                      </small>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                      @if(!$pm->is_default)
                        <form method="POST" action="{{ route('account.payment_methods.default', $pm->id) }}" class="m-0">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-star me-1"></i> Make Default
                          </button>
                        </form>
                      @else
                        <span class="text-success small fw-semibold">
                          <i class="bx bx-check-double me-1"></i> Primary payment method
                        </span>
                      @endif

                      <form method="POST" action="{{ route('account.payment_methods.destroy', $pm->id) }}" class="m-0" onsubmit="return confirm('Are you sure you want to remove this card from your vault?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-label-danger">
                          <i class="bx bx-trash me-1"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        <!-- Security Guarantee Card -->
        <div class="card border-0 shadow-sm mt-4 bg-light">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-success rounded d-flex align-items-center justify-content-center flex-shrink-0">
              <i class="bx bx-shield-quarter fs-2 text-success"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Bank-Grade Tokenized Security</h6>
              <p class="small text-muted mb-0">
                Your full card credentials are never saved on our servers. All transactions are securely vaulted and encrypted according to strict PCI-DSS Level 1 compliance standards.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Add Card Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="addCardModalLabel">
          <i class="bx bx-credit-card me-2 text-primary"></i> Add Payment Card
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('account.payment_methods.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold" for="card_holder_name">Cardholder Name</label>
            <input type="text" class="form-control" id="card_holder_name" name="card_holder_name" placeholder="John Doe" value="{{ old('card_holder_name', $user->name) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="card_number">Card Number</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bx bx-credit-card"></i></span>
              <input type="text" class="form-control" id="card_number" name="card_number" placeholder="4242 •••• •••• 4242" maxlength="19" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" for="card_exp_month">Exp Month</label>
              <select class="form-select" id="card_exp_month" name="card_exp_month" required>
                @for($m = 1; $m <= 12; $m++)
                  @php $monthVal = sprintf('%02d', $m); @endphp
                  <option value="{{ $monthVal }}" {{ old('card_exp_month') == $monthVal ? 'selected' : '' }}>
                    {{ $monthVal }} - {{ date('M', mktime(0, 0, 0, $m, 10)) }}
                  </option>
                @endfor
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" for="card_exp_year">Exp Year</label>
              <select class="form-select" id="card_exp_year" name="card_exp_year" required>
                @for($y = (int)date('Y'); $y <= (int)date('Y') + 10; $y++)
                  <option value="{{ $y }}" {{ old('card_exp_year') == $y ? 'selected' : '' }}>
                    {{ $y }}
                  </option>
                @endfor
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" for="card_brand">Card Brand</label>
              <select class="form-select" id="card_brand" name="card_brand">
                <option value="visa">Visa</option>
                <option value="mastercard">Mastercard</option>
                <option value="amex">American Express</option>
                <option value="discover">Discover</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" for="gateway">Payment Vault</label>
              <select class="form-select" id="gateway" name="gateway">
                <option value="stripe">Stripe Vault</option>
                <option value="paypal">PayPal Vault</option>
                <option value="razorpay">Razorpay</option>
              </select>
            </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_default">
              Set as primary default payment method
            </label>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-lock-alt me-1"></i> Save Card
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

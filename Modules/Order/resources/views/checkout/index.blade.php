@extends('layouts/layoutMaster')

@section('title', 'Secure Checkout — AK-Mart')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Store /</span> Checkout</h4>
    <a href="{{ route('store.cart.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Return to Cart
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <form action="{{ route('store.checkout.place') }}" method="POST">
    @csrf
    <div class="row">
      <!-- Left Column: Customer & Shipping Details -->
      <div class="col-lg-8 mb-4">
        <!-- Customer Details -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">1. Customer Information</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', auth()->user()?->name) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', auth()->user()?->email) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="tel" id="customer_phone" name="customer_phone" class="form-control" placeholder="+1 (555) 000-0000" value="{{ old('customer_phone') }}" required>
                  <button class="btn btn-outline-primary" type="button" id="btnSendOtp" onclick="sendCheckoutOtp()">
                    <i class="bx bx-mobile-alt me-1"></i> Send OTP
                  </button>
                </div>
                <input type="hidden" name="otp_verified" id="otp_verified_input" value="0">
                <div id="otpStatusArea" class="mt-2" style="display: none;">
                  <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="otp_code_input" class="form-control form-control-sm" placeholder="Enter 6-digit OTP" style="max-width: 160px;">
                    <button type="button" class="btn btn-sm btn-primary" id="btnVerifyOtp" onclick="verifyCheckoutOtp()">Verify</button>
                    <span id="otpDemoBadge" class="badge bg-label-info"></span>
                  </div>
                  <small id="otpMessage" class="text-muted d-block mt-1"></small>
                </div>
                <div id="otpVerifiedBadge" class="mt-1" style="display: none;">
                  <span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i> Phone Number Verified</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Shipping Address -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">2. Shipping Address</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Street Address <span class="text-danger">*</span></label>
                <input type="text" name="shipping_address[street]" class="form-control" placeholder="123 Commerce Way, Suite 400" value="{{ old('shipping_address.street') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">City <span class="text-danger">*</span></label>
                <input type="text" name="shipping_address[city]" class="form-control" value="{{ old('shipping_address.city') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">State / Province <span class="text-danger">*</span></label>
                <input type="text" name="shipping_address[state]" class="form-control" value="{{ old('shipping_address.state') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Postal / Zip Code <span class="text-danger">*</span></label>
                <input type="text" name="shipping_address[postal_code]" class="form-control" value="{{ old('shipping_address.postal_code') }}" required>
              </div>
              <div class="col-12">
                <label class="form-label">Country <span class="text-danger">*</span></label>
                <input type="text" name="shipping_address[country]" class="form-control" value="{{ old('shipping_address.country', 'United States') }}" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Shipping Method Selection -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">3. Delivery Method</h5>
            <small class="text-muted"><i class="bx bx-check-shield text-success me-1"></i>Tracked & Insured</small>
          </div>
          <div class="card-body">
            @if(isset($shippingMethods) && $shippingMethods->isNotEmpty())
              <div class="row g-3">
                @foreach($shippingMethods as $method)
                  <div class="col-md-6">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content p-3" for="shipping_method_{{ $method->id }}">
                        <input name="shipping_method_id" class="form-check-input shipping-method-radio" type="radio"
                               value="{{ $method->id }}" id="shipping_method_{{ $method->id }}"
                               data-rate="{{ $method->calculated_rate ?? $method->base_rate }}"
                               {{ ($defaultMethod && $defaultMethod->id === $method->id) || $loop->first ? 'checked' : '' }}
                               onchange="updateShippingPricing({{ $method->id }})">
                        <span class="custom-option-header d-flex justify-content-between align-items-center mb-1">
                          <span class="fw-semibold text-heading">{{ $method->name }}</span>
                          <span class="badge {{ ($method->calculated_rate ?? $method->base_rate) == 0 ? 'bg-label-success' : 'bg-label-primary' }}">
                            {{ ($method->calculated_rate ?? $method->base_rate) == 0 ? 'FREE' : '$' . number_format($method->calculated_rate ?? $method->base_rate, 2) }}
                          </span>
                        </span>
                        <span class="custom-option-body small text-muted d-block">
                          <i class="bx bx-bus me-1"></i>{{ $method->carrier }} &bull;
                          <i class="bx bx-time-five ms-1 me-1"></i>{{ $method->estimated_delivery_text }}
                        </span>
                        @if($method->description)
                          <span class="d-block small text-secondary mt-1">{{ $method->description }}</span>
                        @endif
                      </label>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="p-3 border rounded bg-lighter d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-0 fw-semibold text-heading"><i class="bx bx-package me-1 text-primary"></i> Standard Ground Delivery</h6>
                  <small class="text-muted">Reliable doorstep parcel delivery (3 - 5 business days)</small>
                </div>
                <span class="badge bg-label-success">FREE</span>
              </div>
            @endif

            @php
              $currentBranch = session('active_branch_id') ? \Modules\Context\Models\Branch::find(session('active_branch_id')) : null;
            @endphp
            <div class="mt-3 p-3 bg-lighter rounded border d-flex flex-wrap justify-content-between align-items-center">
              <div>
                <span class="badge bg-label-info mb-1"><i class="bx bx-store-alt me-1"></i> Fulfillment & Store Pickup</span>
                <p class="mb-0 small text-heading">
                  Active Pickup Branch: <strong>{{ $currentBranch?->name ?? 'Main Regional Fulfillment Hub' }}</strong>
                  @if($currentBranch?->full_address)
                    <span class="text-muted d-block small">({{ $currentBranch->full_address }})</span>
                  @endif
                </p>
              </div>
              <a href="{{ route('store.locations') }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 mt-sm-0">
                <i class="bx bx-map-pin me-1"></i> View Branches on Map
              </a>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">4. Payment Option</h5></div>
          <div class="card-body">
            @if(isset($savedPaymentMethods) && $savedPaymentMethods->isNotEmpty())
              <div class="form-check custom-option custom-option-basic mb-3 border-primary border-2">
                <label class="form-check-label custom-option-content" for="pay_saved_card">
                  <input name="payment_method" class="form-check-input" type="radio" value="saved_card" id="pay_saved_card" checked>
                  <span class="custom-option-header">
                    <span class="h6 mb-0 text-primary"><i class="bx bx-shield-quarter me-1"></i> Saved Card (1-Click Vault Checkout)</span>
                    <span class="badge bg-label-primary">Fastest</span>
                  </span>
                  <span class="custom-option-body text-muted small d-block mb-3">
                    Pay instantly using your securely tokenized credit or debit card.
                  </span>

                  <!-- Vaulted Cards Selector -->
                  <div class="p-3 bg-lighter rounded border" id="saved_cards_container">
                    <label class="form-label fw-semibold small mb-2 text-heading">Select a Saved Card:</label>
                    <div class="row g-2">
                      @foreach($savedPaymentMethods as $card)
                        <div class="col-12">
                          <div class="form-check p-2 border rounded bg-white d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                              <input class="form-check-input ms-0 me-2" type="radio" name="saved_payment_method_id" id="saved_card_{{ $card->id }}" value="{{ $card->id }}" {{ ($card->is_default || $loop->first) ? 'checked' : '' }}>
                              <label class="form-check-label d-flex align-items-center cursor-pointer mb-0" for="saved_card_{{ $card->id }}">
                                <span class="badge bg-label-info text-uppercase me-2">{{ $card->card_brand ?: 'Card' }}</span>
                                <span class="font-monospace fw-semibold">•••• •••• •••• {{ $card->card_last_four }}</span>
                                <span class="text-muted small ms-2">(Exp: {{ $card->card_exp_month }}/{{ $card->card_exp_year }})</span>
                              </label>
                            </div>
                            @if($card->is_default)
                              <span class="badge bg-primary ms-2"><i class="bx bx-check me-1"></i> Default</span>
                            @endif
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </label>
              </div>
            @endif

            <div class="form-check custom-option custom-option-basic mb-3">
              <label class="form-check-label custom-option-content" for="pay_cod">
                <input name="payment_method" class="form-check-input" type="radio" value="cod" id="pay_cod" {{ (!isset($savedPaymentMethods) || $savedPaymentMethods->isEmpty()) ? 'checked' : '' }}>
                <span class="custom-option-header">
                  <span class="h6 mb-0">Cash On Delivery (COD)</span>
                  <span><i class="bx bx-money fs-4 text-success"></i></span>
                </span>
                <span class="custom-option-body text-muted small">Pay in cash or card upon physical delivery at your address.</span>
              </label>
            </div>

            <div class="form-check custom-option custom-option-basic mb-3">
              <label class="form-check-label custom-option-content" for="pay_stripe">
                <input name="payment_method" class="form-check-input" type="radio" value="stripe" id="pay_stripe">
                <span class="custom-option-header">
                  <span class="h6 mb-0">Credit / Debit Card (Stripe Gateway)</span>
                  <span><i class="bx bx-credit-card fs-4 text-primary"></i></span>
                </span>
                <span class="custom-option-body text-muted small">Instant secure payment processing via Visa, MasterCard, or Amex.</span>
              </label>
            </div>

            <div class="form-check custom-option custom-option-basic mb-3">
              <label class="form-check-label custom-option-content" for="pay_paypal">
                <input name="payment_method" class="form-check-input" type="radio" value="paypal" id="pay_paypal">
                <span class="custom-option-header">
                  <span class="h6 mb-0">PayPal Express Checkout</span>
                  <span><i class="bx bxl-paypal fs-4 text-info"></i></span>
                </span>
                <span class="custom-option-body text-muted small">Pay seamlessly with your PayPal balance, bank account, or debit card.</span>
              </label>
            </div>

            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="pay_bank">
                <input name="payment_method" class="form-check-input" type="radio" value="bank_transfer" id="pay_bank">
                <span class="custom-option-header">
                  <span class="h6 mb-0">Direct Bank Wire Transfer</span>
                  <span><i class="bx bx-building fs-4 text-warning"></i></span>
                </span>
                <span class="custom-option-body text-muted small">Make payment directly into our official corporate bank account.</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Order Notes -->
        <div class="card">
          <div class="card-body">
            <label class="form-label">Order Notes / Delivery Instructions (Optional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Leave package by the front door or call before arriving...">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>

      <!-- Right Column: Order Review & Pricing Breakdown -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Review Order ({{ count($pricing['items']) }} Items)</h5></div>
          <div class="card-body">
            <ul class="list-unstyled mb-3">
              @foreach($pricing['items'] as $item)
                <li class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <h6 class="mb-0 text-body">{{ $item['product_name'] }}</h6>
                    <small class="text-muted">Qty: {{ $item['quantity'] }} &times; {{ money($item['unit_price']) }}</small>
                  </div>
                  <span class="fw-semibold">{{ money($item['line_total']) }}</span>
                </li>
              @endforeach
            </ul>

            <!-- Sneat Loyalty Points Reward Card -->
            <div class="card bg-label-primary border-primary border-dashed mb-3">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center">
                    <i class="bx bx-gift fs-4 text-primary me-2"></i>
                    <div>
                      <h6 class="mb-0 text-primary fw-bold">Loyalty Rewards</h6>
                      <small class="text-muted" id="loyalty-earn-preview">You will earn ~{{ (int) floor($pricing['subtotal']) }} points</small>
                    </div>
                  </div>
                  <span class="badge bg-primary" id="loyalty-tier-badge">Silver Tier</span>
                </div>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" id="use_loyalty_points" name="use_loyalty_points" value="1" onchange="toggleLoyaltyRedemption(this)">
                  <label class="form-check-label small fw-semibold" for="use_loyalty_points">
                    Apply loyalty discount (<span id="loyalty-points-avail">350 pts available</span>)
                  </label>
                </div>
                <input type="hidden" name="redeemed_points" id="redeemed_points_input" value="0">
                <div id="loyalty-discount-line" class="d-none text-success small mt-1 fw-semibold">
                  <i class="bx bx-check-circle me-1"></i> <span id="loyalty-discount-text">-$3.50 discount applied</span>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Subtotal</span>
              <span class="fw-semibold" id="checkout-subtotal">{{ money($pricing['subtotal']) }}</span>
            </div>

            @if($pricing['discount_amount'] > 0)
              <div class="d-flex justify-content-between mb-2 text-success">
                <span>Discount ({{ $pricing['coupon_code'] ?? $cart->coupon_code }})</span>
                <span class="fw-semibold">-{{ money($pricing['discount_amount']) }}</span>
              </div>
            @endif

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Tax ({{ $pricing['tax_rate'] }}%)</span>
              <span class="fw-semibold" id="checkout-tax">{{ money($pricing['tax_amount']) }}</span>
            </div>

            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Shipping</span>
              <span class="fw-semibold" id="checkout-shipping">
                {{ $pricing['shipping_amount'] == 0 ? 'FREE' : money($pricing['shipping_amount']) }}
              </span>
            </div>

            <hr class="my-3">

            <div class="d-flex justify-content-between align-items-center mb-4">
              <span class="fs-5 fw-bold">Total To Pay</span>
              <span class="fs-4 fw-bold text-primary" id="checkout-grand-total">{{ money($pricing['grand_total']) }}</span>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fs-6">
              <i class="bx bx-check-shield me-1"></i> Confirm & Place Order
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  function updateShippingPricing(methodId) {
    const token = document.querySelector('input[name="_token"]').value;
    fetch('{{ route('store.checkout.calculate_shipping') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ shipping_method_id: methodId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.pricing) {
        const p = data.pricing;
        const shippingElem = document.getElementById('checkout-shipping');
        const grandTotalElem = document.getElementById('checkout-grand-total');

        if (shippingElem) {
          shippingElem.textContent = p.shipping_amount === 0 ? 'FREE' : '$' + parseFloat(p.shipping_amount).toFixed(2);
        }
        if (grandTotalElem) {
          grandTotalElem.textContent = '$' + parseFloat(p.grand_total).toFixed(2);
        }
      }
    })
    .catch(err => console.error('Error recalculating shipping:', err));
  }

  function sendCheckoutOtp() {
    const phoneInput = document.getElementById('customer_phone');
    const phone = phoneInput ? phoneInput.value.trim() : '';
    if (!phone) {
      alert('Please enter your phone number first.');
      if (phoneInput) phoneInput.focus();
      return;
    }

    const btn = document.getElementById('btnSendOtp');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
    }

    const token = document.querySelector('input[name="_token"]').value;
    fetch('{{ route('otp.send') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ identifier: phone, type: 'checkout' })
    })
    .then(res => res.json())
    .then(data => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-refresh me-1"></i> Resend OTP';
      }
      if (data.status === 'success') {
        const area = document.getElementById('otpStatusArea');
        if (area) area.style.display = 'block';
        const msg = document.getElementById('otpMessage');
        if (msg) msg.textContent = data.message;
        const demoBadge = document.getElementById('otpDemoBadge');
        if (demoBadge && data.demo_code) {
          demoBadge.textContent = 'Demo Code: ' + data.demo_code;
          const codeInput = document.getElementById('otp_code_input');
          if (codeInput) codeInput.value = data.demo_code;
        }
      } else {
        alert(data.message || 'Failed to send OTP.');
      }
    })
    .catch(err => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-mobile-alt me-1"></i> Send OTP';
      }
      alert('An error occurred while sending OTP.');
    });
  }

  function verifyCheckoutOtp() {
    const phone = document.getElementById('customer_phone').value.trim();
    const code = document.getElementById('otp_code_input').value.trim();
    if (!code) {
      alert('Please enter the 6-digit OTP code.');
      return;
    }

    const btn = document.getElementById('btnVerifyOtp');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Verifying...';
    }

    const token = document.querySelector('input[name="_token"]').value;
    fetch('{{ route('otp.verify') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ identifier: phone, code: code, type: 'checkout' })
    })
    .then(res => res.json())
    .then(data => {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Verify';
      }
      if (data.status === 'success') {
        document.getElementById('otpStatusArea').style.display = 'none';
        document.getElementById('otpVerifiedBadge').style.display = 'block';
        document.getElementById('otp_verified_input').value = '1';
        document.getElementById('customer_phone').readOnly = true;
        document.getElementById('btnSendOtp').style.display = 'none';
      } else {
        alert(data.message || 'Invalid OTP code.');
      }
    })
    .catch(err => {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Verify';
      }
      alert('Verification failed. Please check the code and try again.');
    });
  }

  // Loyalty Points Redemption Handler
  let availableLoyaltyPoints = 350;
  let loyaltyDiscountValue = 3.50;

  function fetchCustomerLoyalty(email) {
    if (!email) return;
    fetch(`/store/rewards/balance?email=${encodeURIComponent(email)}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          availableLoyaltyPoints = data.current_points;
          loyaltyDiscountValue = data.max_discount;
          const availElem = document.getElementById('loyalty-points-avail');
          const tierElem = document.getElementById('loyalty-tier-badge');
          if (availElem) availElem.textContent = `${data.current_points} pts available ($${data.max_discount.toFixed(2)} off)`;
          if (tierElem) tierElem.textContent = `${data.tier} Tier`;
        }
      })
      .catch(e => console.error('Could not fetch loyalty points', e));
  }

  function toggleLoyaltyRedemption(checkbox) {
    const discountLine = document.getElementById('loyalty-discount-line');
    const discountText = document.getElementById('loyalty-discount-text');
    const grandTotalElem = document.getElementById('checkout-grand-total');
    const pointsInput = document.getElementById('redeemed_points_input');
    const originalTotal = parseFloat('{{ $pricing['grand_total'] }}');

    if (checkbox.checked) {
      if (availableLoyaltyPoints <= 0) {
        alert('You have no loyalty points available to redeem.');
        checkbox.checked = false;
        return;
      }
      pointsInput.value = availableLoyaltyPoints;
      discountLine.classList.remove('d-none');
      discountText.textContent = `-$${loyaltyDiscountValue.toFixed(2)} loyalty points discount applied`;
      const newTotal = Math.max(0, originalTotal - loyaltyDiscountValue);
      grandTotalElem.textContent = '$' + newTotal.toFixed(2);
    } else {
      pointsInput.value = 0;
      discountLine.classList.add('d-none');
      grandTotalElem.textContent = '$' + originalTotal.toFixed(2);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.querySelector('input[name="customer_email"]');
    if (emailInput && emailInput.value) {
      fetchCustomerLoyalty(emailInput.value);
    }
    if (emailInput) {
      emailInput.addEventListener('blur', () => fetchCustomerLoyalty(emailInput.value));
    }
  });
</script>
@endsection

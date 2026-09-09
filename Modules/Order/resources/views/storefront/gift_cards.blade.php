@extends('layouts/layoutFront')

@section('title', 'Check Gift Card Balance - Sneat Store')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <!-- Hero Header -->
    <div class="text-center mb-5">
      <span class="badge bg-label-primary mb-2 fs-6 px-3 py-2"><i class="bx bx-gift me-1"></i> Digital Vouchers</span>
      <h2 class="fw-bold mb-2">Check Your Gift Card Balance</h2>
      <p class="text-muted mx-auto" style="max-width: 600px;">
        Have a Sneat digital gift card or promotional store voucher? Enter your 16-character code below to verify your current available funds instantly.
      </p>
    </div>

    <!-- Balance Checker Card -->
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4 p-md-5">
            <form id="giftCardCheckForm" onsubmit="event.preventDefault(); verifyBalance();">
              @csrf
              <div class="mb-4">
                <label class="form-label fw-bold" for="cardCodeInput">16-Character Gift Card Code</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"><i class="bx bx-credit-card"></i></span>
                  <input type="text" id="cardCodeInput" name="code" class="form-control text-uppercase font-monospace text-center fw-bold" placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" required autocomplete="off">
                </div>
                <small class="text-muted">Enter the 16 characters found in your confirmation email.</small>
              </div>

              <button type="submit" id="checkBtn" class="btn btn-primary btn-lg w-100">
                <i class="bx bx-search me-1"></i> Check Balance
              </button>
            </form>

            <!-- Loading Spinner -->
            <div id="checkSpinner" class="text-center py-4 d-none">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Checking balance...</span>
              </div>
              <p class="text-muted small mt-2 mb-0">Verifying voucher on blockchain ledger...</p>
            </div>

            <!-- Error Notification -->
            <div id="checkError" class="alert alert-danger mt-4 d-none" role="alert">
              <i class="bx bx-error-circle me-1"></i> <span id="errorMessage">Invalid gift card code.</span>
            </div>

            <!-- Success Card Render -->
            <div id="cardResult" class="mt-4 d-none">
              <div class="card bg-dark text-white rounded-3 shadow border-0 overflow-hidden" style="background: linear-gradient(135deg, #1e1e2d 0%, #2b2b40 50%, #151521 100%);">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                      <span class="text-muted small text-uppercase letter-spacing-1">Sneat Store Gift Voucher</span>
                      <h4 class="text-white fw-bold mb-0" id="resCardCode">XXXX-XXXX-XXXX-XXXX</h4>
                    </div>
                    <span class="badge bg-success fs-6"><i class="bx bx-check me-1"></i> Valid</span>
                  </div>

                  <div class="d-flex justify-content-between align-items-end mt-4">
                    <div>
                      <small class="text-muted d-block">Available Balance</small>
                      <h2 class="text-white fw-bold mb-0" id="resCardBalance">$0.00</h2>
                    </div>
                    <div class="text-end">
                      <small class="text-muted d-block">Expires</small>
                      <span class="text-white fw-semibold small" id="resCardExpiry">Never</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-3 text-center">
                <a href="{{ route('storefront.catalog') }}" class="btn btn-outline-primary">
                  <i class="bx bx-shopping-bag me-1"></i> Redeem in Store
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- How It Works Accordion / Info -->
        <div class="card border-0 bg-transparent">
          <div class="card-body p-0">
            <h5 class="fw-bold mb-3"><i class="bx bx-info-circle text-primary me-2"></i>How to Redeem</h5>
            <ol class="text-muted ps-3 mb-0 small lh-lg">
              <li>Add your favorite products or services to your shopping cart.</li>
              <li>Proceed to checkout and look for the <strong>"Redeem Gift Card"</strong> section in payment options.</li>
              <li>Input your card code to deduct its balance directly from your order grand total.</li>
              <li>Any remaining balance will remain safely on your card for future purchases.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  // Format input with hyphens automatically
  document.getElementById('cardCodeInput').addEventListener('input', function(e) {
    let val = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
    let parts = [];
    for (let i = 0; i < val.length; i += 4) {
      parts.push(val.substring(i, i + 4));
    }
    e.target.value = parts.join('-');
  });

  function verifyBalance() {
    const code = document.getElementById('cardCodeInput').value.trim();
    const btn = document.getElementById('checkBtn');
    const spinner = document.getElementById('checkSpinner');
    const errorBox = document.getElementById('checkError');
    const resultBox = document.getElementById('cardResult');

    errorBox.classList.add('d-none');
    resultBox.classList.add('d-none');
    spinner.classList.remove('d-none');
    btn.disabled = true;

    fetch('{{ route('store.gift_cards.check') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ code: code })
    })
    .then(async response => {
      const data = await response.json();
      spinner.classList.add('d-none');
      btn.disabled = false;

      if (response.ok && data.status === 'success') {
        document.getElementById('resCardCode').textContent = data.code;
        document.getElementById('resCardBalance').textContent = data.formatted || ('$' + Number(data.current_balance).toFixed(2));
        document.getElementById('resCardExpiry').textContent = data.expires_at || 'Never';
        resultBox.classList.remove('d-none');
      } else {
        document.getElementById('errorMessage').textContent = data.message || 'Invalid or depleted gift card code.';
        errorBox.classList.remove('d-none');
      }
    })
    .catch(err => {
      spinner.classList.add('d-none');
      btn.disabled = false;
      document.getElementById('errorMessage').textContent = 'Unable to check card balance. Please try again later.';
      errorBox.classList.remove('d-none');
    });
  }
</script>
@endsection

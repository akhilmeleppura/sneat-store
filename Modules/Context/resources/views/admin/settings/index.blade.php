@extends('layouts/layoutMaster')

@section('title', 'Tenant & Store Settings - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Breadcrumb -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold py-1 mb-1">
        <span class="text-muted fw-light">Context /</span> Tenant & Store Settings
      </h4>
      <p class="text-muted mb-0">
        Manage company details, checkout behaviors, branding, and secure API credentials for 
        <strong class="text-primary">{{ $tenant->name }}</strong> (<code>{{ $tenant->slug }}</code>).
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-label-success px-3 py-2 fs-6">
        <i class="bx bx-check-shield me-1"></i> Status: {{ ucfirst($tenant->status) }}
      </span>
      <a href="{{ route('admin.currencies.index') }}" class="btn btn-outline-primary">
        <i class="bx bx-dollar me-1"></i> FX Rates
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible shadow-sm d-flex align-items-center" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>
      <div class="flex-grow-1">{{ session('success') }}</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible shadow-sm" role="alert">
      <div class="d-flex align-items-center mb-1">
        <i class="bx bx-error-circle fs-4 me-2"></i>
        <strong>Please check the form for errors:</strong>
      </div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Settings Form with Tabs -->
  <div class="nav-align-top mb-4">
    <ul class="nav nav-tabs shadow-sm" role="tablist">
      <li class="nav-item">
        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#nav-general" aria-controls="nav-general" aria-selected="true">
          <i class="bx bx-buildings me-1"></i> General & Company
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#nav-ecommerce" aria-controls="nav-ecommerce" aria-selected="false">
          <i class="bx bx-cart me-1"></i> E-Commerce & Checkout
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#nav-branding" aria-controls="nav-branding" aria-selected="false">
          <i class="bx bx-paint me-1"></i> Branding & Invoicing
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#nav-integrations" aria-controls="nav-integrations" aria-selected="false">
          <i class="bx bx-lock-alt me-1"></i> Security & Integrations
        </button>
      </li>
    </ul>

    <div class="tab-content border-0 p-0 pt-3">
      <form action="{{ route('admin.tenant.settings.update') }}" method="POST" id="tenantSettingsForm">
        @csrf

        <!-- 1. General & Company Tab -->
        <div class="tab-pane fade show active" id="nav-general" role="tabpanel">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">Company & Regional Preferences</h5>
              <small class="text-muted">Core organization identity and accounting preferences</small>
            </div>
            <div class="card-body pt-4">
              <div class="row g-3">
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="company_name">Company / Organization Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $settings['general']['company_name']) }}" required>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="contact_email">Business Contact Email <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings['general']['contact_email']) }}" required>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="contact_phone">Contact Phone</label>
                  <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings['general']['contact_phone']) }}">
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="default_currency">Default Currency <span class="text-danger">*</span></label>
                  <select class="form-select" id="default_currency" name="default_currency" required>
                    @foreach($currencies as $currency)
                      <option value="{{ $currency->code }}" {{ old('default_currency', $settings['general']['default_currency']) === $currency->code ? 'selected' : '' }}>
                        {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="timezone">System Timezone <span class="text-danger">*</span></label>
                  <select class="form-select" id="timezone" name="timezone" required>
                    @foreach(['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo'] as $tz)
                      <option value="{{ $tz }}" {{ old('timezone', $settings['general']['timezone']) === $tz ? 'selected' : '' }}>
                        {{ $tz }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" for="address">Headquarters Address</label>
                  <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $settings['general']['address']) }}</textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. E-Commerce & Checkout Tab -->
        <div class="tab-pane fade" id="nav-ecommerce" role="tabpanel">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">E-Commerce & Checkout Configuration</h5>
              <small class="text-muted">Order processing, inventory alerts, and tax policies</small>
            </div>
            <div class="card-body pt-4">
              <div class="row g-3">
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="order_prefix">Order Number Prefix <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="order_prefix" name="order_prefix" value="{{ old('order_prefix', $settings['ecommerce']['order_prefix']) }}" placeholder="e.g. SNT-" required>
                  <small class="text-muted">Prefix appended to generated order reference numbers.</small>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="tax_mode">Tax Calculation Mode <span class="text-danger">*</span></label>
                  <select class="form-select" id="tax_mode" name="tax_mode" required>
                    <option value="exclusive" {{ old('tax_mode', $settings['ecommerce']['tax_mode']) === 'exclusive' ? 'selected' : '' }}>Exclusive (Tax added at checkout)</option>
                    <option value="inclusive" {{ old('tax_mode', $settings['ecommerce']['tax_mode']) === 'inclusive' ? 'selected' : '' }}>Inclusive (Catalog prices include tax)</option>
                  </select>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="low_stock_threshold">Low Stock Warning Threshold <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $settings['ecommerce']['low_stock_threshold']) }}" required>
                  <small class="text-muted">Inventory level that triggers reorder alerts.</small>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="free_shipping_threshold">Free Shipping Minimum Amount <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" min="0" value="{{ old('free_shipping_threshold', $settings['ecommerce']['free_shipping_threshold']) }}" required>
                  </div>
                  <small class="text-muted">Cart subtotal required to qualify for automated free shipping.</small>
                </div>
                <div class="col-12 mt-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_guest_checkout" name="enable_guest_checkout" value="1" {{ old('enable_guest_checkout', $settings['ecommerce']['enable_guest_checkout']) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="enable_guest_checkout">Enable Guest Checkout</label>
                  </div>
                  <small class="text-muted ms-4">Allow unauthenticated shoppers to complete orders using their email and shipping details.</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Branding & Invoicing Tab -->
        <div class="tab-pane fade" id="nav-branding" role="tabpanel">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">Branding, Invoices & Customer Support</h5>
              <small class="text-muted">Customer-facing labels, invoice notes, and return policies</small>
            </div>
            <div class="card-body pt-4">
              <div class="row g-3">
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="store_tagline">Store Tagline</label>
                  <input type="text" class="form-control" id="store_tagline" name="store_tagline" value="{{ old('store_tagline', $settings['branding']['store_tagline']) }}">
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="support_hours">Customer Support Hours</label>
                  <input type="text" class="form-control" id="support_hours" name="support_hours" value="{{ old('support_hours', $settings['branding']['support_hours']) }}">
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="return_policy_days">Return / RMA Policy Window (Days) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="return_policy_days" name="return_policy_days" min="0" max="365" value="{{ old('return_policy_days', $settings['branding']['return_policy_days']) }}" required>
                  <small class="text-muted">Maximum number of days after delivery customers can submit return requests.</small>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" for="invoice_footer">Invoice Footer Note</label>
                  <textarea class="form-control" id="invoice_footer" name="invoice_footer" rows="3">{{ old('invoice_footer', $settings['branding']['invoice_footer']) }}</textarea>
                  <small class="text-muted">Appears at the bottom of printable and PDF order invoices.</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Security & Integrations Tab -->
        <div class="tab-pane fade" id="nav-integrations" role="tabpanel">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">Security & Encrypted API Integrations</h5>
              <small class="text-muted">Sensitive keys stored securely using AES-256 encryption at rest</small>
            </div>
            <div class="card-body pt-4">
              <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="bx bx-shield-quarter fs-3 me-2"></i>
                <div>
                  <strong>Tenant Encryption Active:</strong> Secrets configured below are encrypted in the database via <code>is_encrypted</code> and decrypted on demand only when accessed through the Context API.
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="webhook_secret">Incoming Webhook Secret</label>
                  <input type="password" class="form-control" id="webhook_secret" name="webhook_secret" value="{{ old('webhook_secret', $settings['integrations']['webhook_secret']) }}" placeholder="••••••••••••••••">
                  <small class="text-muted">Used for validating signatures from third-party logistics or external ERPs.</small>
                </div>
                <div class="col-md-6 col-12">
                  <label class="form-label fw-semibold" for="payment_gateway_api_key">Payment Gateway API Key / Secret</label>
                  <input type="password" class="form-control" id="payment_gateway_api_key" name="payment_gateway_api_key" value="{{ old('payment_gateway_api_key', $settings['integrations']['payment_gateway_api_key']) }}" placeholder="••••••••••••••••">
                  <small class="text-muted">Dedicated tenant payment processor merchant secret token.</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Submission Footer -->
        <div class="card shadow-sm border-0">
          <div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted small">
              <i class="bx bx-info-circle me-1"></i> Changes take effect immediately across all storefront and admin sessions.
            </span>
            <div class="d-flex gap-2">
              <button type="reset" class="btn btn-label-secondary">Reset</button>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bx bx-save me-1"></i> Save Configuration
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

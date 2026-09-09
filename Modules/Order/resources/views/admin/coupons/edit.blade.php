@extends('layouts/layoutMaster')

@section('title', 'Edit Coupon - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Coupons /</span> Edit Coupon: {{ $coupon->code }}</h4>
      <small class="text-muted">Modify promotional parameters, thresholds, or availability</small>
    </div>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Coupons
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

  <form action="{{ route('admin.coupons.update', $coupon->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
      <!-- Left Column: Primary Details -->
      <div class="col-lg-8">
        <!-- Basic Information Card -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">General Details</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Coupon Code <span class="text-danger">*</span></label>
                <input type="text" name="code" id="couponCode" class="form-control text-uppercase font-monospace fw-bold" value="{{ old('code', $coupon->code) }}" required>
                <small class="text-muted">Alphanumeric string entered by customer.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Promotion Name / Title <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $coupon->name) }}" required>
              </div>

              <div class="col-12">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $coupon->description) }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Discount Rules Card -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Discount Structure</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                <select name="type" id="discountType" class="form-select" onchange="toggleDiscountType()" required>
                  <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                  <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Discount Value <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text" id="typePrefix">{{ $coupon->type === 'percentage' ? '%' : '$' }}</span>
                  <input type="number" step="0.01" min="0.01" name="value" id="discountValue" class="form-control" value="{{ old('value', $coupon->value) }}" required>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Minimum Order Spend ($)</label>
                <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $coupon->min_order_amount) }}">
                <small class="text-muted">Cart subtotal required before discount applies.</small>
              </div>

              <div class="col-md-6" id="maxCapContainer">
                <label class="form-label">Maximum Discount Cap ($)</label>
                <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}">
                <small class="text-muted">Maximum dollar amount this percentage coupon can discount.</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Usage Limits Card -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Usage & Redemption Constraints</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Total Global Usage Limit</label>
                <input type="number" min="1" name="usage_limit" class="form-control" placeholder="Empty = Unlimited" value="{{ old('usage_limit', $coupon->usage_limit) }}">
                <small class="text-muted">Current redemptions: <strong>{{ $coupon->times_used }}</strong></small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Usage Limit Per Customer</label>
                <input type="number" min="1" name="usage_limit_per_user" class="form-control" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" required>
                <small class="text-muted">How many times each individual customer/email can use this coupon.</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Settings & Validity -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Publish & Validity</h5></div>
          <div class="card-body">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="isActive">Coupon Active</label>
              <div class="small text-muted">Enable or pause redemptions immediately.</div>
            </div>

            <hr class="my-3">

            <div class="mb-3">
              <label class="form-label">Valid From (Start Date)</label>
              <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d') : '') }}">
              <small class="text-muted">Optional scheduled launch date.</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Valid Until (Expiration Date)</label>
              <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}">
              <small class="text-muted">Optional expiration cut-off date.</small>
            </div>

            @if(count($vendors) > 0)
              <div class="mb-3">
                <label class="form-label">Restrict to Vendor (Optional)</label>
                <select name="vendor_id" class="form-select">
                  <option value="">All Vendors & Platform Products</option>
                  @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $coupon->vendor_id) == $vendor->id ? 'selected' : '' }}>
                      {{ $vendor->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Restricts coupon discount strictly to this vendor's catalog items.</small>
              </div>
            @endif

            <div class="mt-4">
              <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bx bx-check me-1"></i> Update Coupon
              </button>
            </div>
          </div>
        </div>

        <!-- Redemption Log Card -->
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Redemption Summary</h5></div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Total Times Redeemed:</span>
              <span class="fw-bold">{{ $coupon->times_used }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Total Discount Provided:</span>
              <span class="fw-bold text-success">{{ money($coupon->usages()->sum('discount_amount')) }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Status:</span>
              <span>{!! $coupon->status_badge !!}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function toggleDiscountType() {
  const type = document.getElementById('discountType').value;
  const prefix = document.getElementById('typePrefix');
  const cap = document.getElementById('maxCapContainer');

  if (type === 'percentage') {
    prefix.innerText = '%';
    cap.style.display = 'block';
  } else {
    prefix.innerText = '$';
    cap.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', toggleDiscountType);
</script>
@endsection

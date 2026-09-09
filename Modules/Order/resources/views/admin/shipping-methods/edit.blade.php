@extends('layouts/layoutMaster')

@section('title', 'Edit Shipping Method - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0">
        <a href="{{ route('admin.shipping-methods.index') }}" class="text-muted fw-light">Shipping Carriers /</a> Edit Method
      </h4>
      <small class="text-muted">Update courier rules, pricing tiers, and delivery timeframes for {{ $method->name }}</small>
    </div>
    <a href="{{ route('admin.shipping-methods.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Methods
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <h6 class="alert-heading mb-1"><i class="bx bx-error-circle me-1"></i> Validation Errors</h6>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <form action="{{ route('admin.shipping-methods.update', $method->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
      <!-- Left Column: Core Configuration -->
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0"><i class="bx bx-info-circle me-1 text-primary"></i> Carrier & Method Information</h5>
          </div>
          <div class="card-body pt-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="name">Method Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $method->name) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="code">Method Identifier Code <span class="text-danger">*</span></label>
                <input type="text" id="code" name="code" class="form-control text-uppercase" value="{{ old('code', $method->code) }}" required>
                <small class="text-muted">Unique uppercase code for system reference</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="carrier">Carrier / Courier <span class="text-danger">*</span></label>
                <input type="text" id="carrier" name="carrier" class="form-control" value="{{ old('carrier', $method->carrier) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="rate_type">Pricing / Rate Model <span class="text-danger">*</span></label>
                <select id="rate_type" name="rate_type" class="form-select" required onchange="handleRateTypeChange(this.value)">
                  <option value="flat" {{ old('rate_type', $method->rate_type) === 'flat' ? 'selected' : '' }}>Flat Fee</option>
                  <option value="tiered_weight" {{ old('rate_type', $method->rate_type) === 'tiered_weight' ? 'selected' : '' }}>Tiered by Weight (kg)</option>
                  <option value="tiered_total" {{ old('rate_type', $method->rate_type) === 'tiered_total' ? 'selected' : '' }}>Tiered by Order Total ($)</option>
                  <option value="free" {{ old('rate_type', $method->rate_type) === 'free' ? 'selected' : '' }}>Always Free</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="base_rate">Base Rate ($) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" step="0.01" min="0" id="base_rate" name="base_rate" class="form-control" value="{{ old('base_rate', $method->base_rate) }}" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="free_shipping_threshold">Free Shipping Threshold ($)</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" step="0.01" min="0" id="free_shipping_threshold" name="free_shipping_threshold" class="form-control" placeholder="Leave blank if none" value="{{ old('free_shipping_threshold', $method->free_shipping_threshold) }}">
                </div>
                <small class="text-muted">Orders at or above this value ship free</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="min_days">Minimum Delivery Days <span class="text-danger">*</span></label>
                <input type="number" min="0" max="90" id="min_days" name="min_days" class="form-control" value="{{ old('min_days', $method->min_days) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="max_days">Maximum Delivery Days <span class="text-danger">*</span></label>
                <input type="number" min="0" max="90" id="max_days" name="max_days" class="form-control" value="{{ old('max_days', $method->max_days) }}" required>
              </div>

              <div class="col-12">
                <label class="form-label" for="description">Customer Description</label>
                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $method->description) }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Tiered Rates Card -->
        <div class="card mb-4" id="tiered-rates-card" style="{{ in_array($method->rate_type, ['tiered_weight', 'tiered_total']) ? '' : 'display: none;' }}">
          <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bx bx-layer me-1 text-primary"></i> Tiered Rate Thresholds</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTierRow()">
              <i class="bx bx-plus me-1"></i> Add Tier
            </button>
          </div>
          <div class="card-body pt-4">
            <div id="tier-rows-container">
              <!-- Populated via existing settings or JavaScript -->
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Status & Actions -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0"><i class="bx bx-cog me-1 text-primary"></i> Visibility & Sort</h5>
          </div>
          <div class="card-body pt-4">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $method->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="is_active">Active & Available at Checkout</label>
            </div>

            <div class="mb-3">
              <label class="form-label" for="sort_order">Display Priority / Sort Order</label>
              <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', $method->sort_order) }}">
              <small class="text-muted">Lower values show first in carrier options list</small>
            </div>

            <hr>

            <button type="submit" class="btn btn-primary w-100 mb-2">
              <i class="bx bx-check-circle me-1"></i> Update Shipping Method
            </button>
            <a href="{{ route('admin.shipping-methods.index') }}" class="btn btn-outline-secondary w-100">
              Cancel
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  const existingSettings = @json($method->settings ?? []);

  function handleRateTypeChange(type) {
    const card = document.getElementById('tiered-rates-card');
    if (type === 'tiered_weight' || type === 'tiered_total') {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  }

  let tierCount = 0;
  function addTierRow(val1 = '', val2 = '') {
    const type = document.getElementById('rate_type').value;
    const isWeight = type === 'tiered_weight';
    const container = document.getElementById('tier-rows-container');

    const label1 = isWeight ? 'Max Weight (kg)' : 'Min Cart Total ($)';
    const fieldName1 = isWeight ? `weight_tiers[${tierCount}][max_weight]` : `total_tiers[${tierCount}][min_total]`;
    const fieldName2 = isWeight ? `weight_tiers[${tierCount}][rate]` : `total_tiers[${tierCount}][rate]`;

    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 align-items-center tier-row';
    row.innerHTML = `
      <div class="col-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text">${isWeight ? 'kg' : '$'}</span>
          <input type="number" step="0.1" min="0" name="${fieldName1}" value="${val1}" class="form-control" placeholder="${label1}" required>
        </div>
      </div>
      <div class="col-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text">$</span>
          <input type="number" step="0.01" min="0" name="${fieldName2}" value="${val2}" class="form-control" placeholder="Shipping Fee ($)" required>
        </div>
      </div>
      <div class="col-2 text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.tier-row').remove()">
          <i class="bx bx-trash"></i>
        </button>
      </div>
    `;
    container.appendChild(row);
    tierCount++;
  }

  document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('rate_type').value;
    if (currentType === 'tiered_weight' && existingSettings.weight_tiers) {
      existingSettings.weight_tiers.forEach(t => addTierRow(t.max_weight, t.rate));
    } else if (currentType === 'tiered_total' && existingSettings.total_tiers) {
      existingSettings.total_tiers.forEach(t => addTierRow(t.min_total, t.rate));
    }
  });
</script>
@endsection

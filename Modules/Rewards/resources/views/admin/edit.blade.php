@extends('layouts/layoutMaster')

@section('title', 'Edit Reward Program - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Rewards /</span> Edit Program: {{ $reward->name }}</h4>
      <small class="text-muted">Modify points earning multipliers and redemption conditions</small>
    </div>
    <a href="{{ route('admin.rewards.index') }}" class="btn btn-label-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to List
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Program Settings</h5>
    </div>
    <div class="card-body pt-4">
      <form action="{{ route('admin.rewards.update', $reward->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="name">Program Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $reward->name) }}" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="code">Program Code <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $reward->code) }}" required>
          </div>

          <div class="col-md-12">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $reward->description) }}</textarea>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="tier">Customer Tier <span class="text-danger">*</span></label>
            <select id="tier" name="tier" class="form-select" required>
              <option value="standard" {{ old('tier', $reward->tier) === 'standard' ? 'selected' : '' }}>Standard (All Customers)</option>
              <option value="bronze" {{ old('tier', $reward->tier) === 'bronze' ? 'selected' : '' }}>Bronze</option>
              <option value="silver" {{ old('tier', $reward->tier) === 'silver' ? 'selected' : '' }}>Silver</option>
              <option value="gold" {{ old('tier', $reward->tier) === 'gold' ? 'selected' : '' }}>Gold</option>
              <option value="vip" {{ old('tier', $reward->tier) === 'vip' ? 'selected' : '' }}>VIP / Platinum</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="earn_rate">Earn Rate (Points per $1 spent) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" min="0.1" id="earn_rate" name="earn_rate" class="form-control" value="{{ old('earn_rate', $reward->earn_rate) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="redeem_rate">Redeem Conversion (1 pt = $ value) <span class="text-danger">*</span></label>
            <input type="number" step="0.001" min="0.001" max="1" id="redeem_rate" name="redeem_rate" class="form-control" value="{{ old('redeem_rate', $reward->redeem_rate) }}" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="min_points_to_redeem">Minimum Points to Redeem <span class="text-danger">*</span></label>
            <input type="number" id="min_points_to_redeem" name="min_points_to_redeem" class="form-control" value="{{ old('min_points_to_redeem', $reward->min_points_to_redeem) }}" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="max_points_per_order">Max Points Redeemable per Order</label>
            <input type="number" id="max_points_per_order" name="max_points_per_order" class="form-control" value="{{ old('max_points_per_order', $reward->max_points_per_order) }}">
          </div>

          <div class="col-md-6">
            <label class="form-label" for="starts_at">Start Date (Optional)</label>
            <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $reward->starts_at?->format('Y-m-d\TH:i')) }}">
          </div>

          <div class="col-md-6">
            <label class="form-label" for="expires_at">End / Expiry Date (Optional)</label>
            <input type="datetime-local" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at', $reward->expires_at?->format('Y-m-d\TH:i')) }}">
          </div>

          <div class="col-12 mt-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $reward->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="is_active">Program is active</label>
            </div>
          </div>
        </div>

        <div class="pt-4 mt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-check me-1"></i> Update Program
          </button>
          <a href="{{ route('admin.rewards.index') }}" class="btn btn-label-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Coupons & Promotions - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Sales /</span> Coupons & Promotions</h4>
      <small class="text-muted">Create and manage marketing discounts, promotional codes, and redemption limits</small>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Create New Coupon
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Coupons</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalCoupons) }}</h4>
              </div>
              <small class="text-muted">Configured promotions</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-purchase-tag-alt fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Active Promos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($activeCoupons) }}</h4>
              </div>
              <small class="text-muted">Currently redeemable</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-circle fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Redemptions</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($totalRedemptions) }}</h4>
              </div>
              <small class="text-muted">Total orders discounted</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-shopping-bag fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Discount</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ money($totalSavings) }}</h4>
              </div>
              <small class="text-muted">Customer savings given</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-dollar-circle fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.coupons.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Search Coupon</label>
          <input type="text" name="search" class="form-control" placeholder="Code or promotion title..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Discount Type</label>
          <select name="type" class="form-select">
            <option value="">All Types</option>
            <option value="percentage" {{ request('type') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
            <option value="fixed" {{ request('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Coupons Datatable Card -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Coupons Directory ({{ $coupons->total() }})</h5>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Code & Title</th>
            <th>Discount</th>
            <th>Thresholds & Limits</th>
            <th>Usage</th>
            <th>Validity Window</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($coupons as $coupon)
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-dark font-monospace fs-6 px-2 py-1">{{ $coupon->code }}</span>
                </div>
                <div class="fw-semibold text-heading mt-1">{{ $coupon->name }}</div>
                @if($coupon->vendor)
                  <small class="badge bg-label-info mt-1"><i class="bx bx-store me-1"></i>{{ $coupon->vendor->name }}</small>
                @endif
              </td>
              <td>
                {!! $coupon->type_badge !!}
                @if($coupon->type === 'percentage' && $coupon->max_discount_amount)
                  <div class="small text-muted mt-1">Cap: {{ money($coupon->max_discount_amount) }}</div>
                @endif
              </td>
              <td>
                <div class="small">
                  <span class="text-muted">Min Spend:</span>
                  <span class="fw-semibold">{{ $coupon->min_order_amount > 0 ? money($coupon->min_order_amount) : 'None' }}</span>
                </div>
                <div class="small text-muted">
                  Per Customer: {{ $coupon->usage_limit_per_user ?: 'Unlimited' }}
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-bold">{{ $coupon->times_used }}</span>
                  <span class="text-muted">/ {{ $coupon->usage_limit ?? '∞' }}</span>
                </div>
                @if($coupon->usage_limit)
                  @php
                    $pct = min(100, round(($coupon->times_used / $coupon->usage_limit) * 100));
                  @endphp
                  <div class="progress mt-1" style="height: 5px; width: 90px;">
                    <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : 'bg-primary' }}" role="progressbar" style="width: {{ $pct }}%"></div>
                  </div>
                @endif
              </td>
              <td>
                <div class="small">
                  @if($coupon->starts_at)
                    <span class="text-muted">From:</span> {{ $coupon->starts_at->format('M d, Y') }}<br>
                  @endif
                  @if($coupon->expires_at)
                    <span class="text-muted">To:</span> {{ $coupon->expires_at->format('M d, Y') }}
                  @else
                    <span class="badge bg-label-secondary">No Expiry</span>
                  @endif
                </div>
              </td>
              <td>
                {!! $coupon->status_badge !!}
              </td>
              <td class="text-center">
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded fs-5"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('admin.coupons.edit', $coupon->id) }}">
                      <i class="bx bx-edit-alt me-1 text-primary"></i> Edit Coupon
                    </a>
                    <form action="{{ route('admin.coupons.toggle', $coupon->id) }}" method="POST" class="d-inline">
                      @csrf
                      <button type="submit" class="dropdown-item">
                        <i class="bx {{ $coupon->is_active ? 'bx-pause-circle text-warning' : 'bx-play-circle text-success' }} me-1"></i>
                        {{ $coupon->is_active ? 'Disable' : 'Enable' }}
                      </button>
                    </form>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('admin.coupons.destroy', $coupon->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete coupon {{ $coupon->code }}?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="bx bx-trash me-1"></i> Delete
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bx bx-purchase-tag-alt fs-1 d-block mb-2 text-secondary"></i>
                No coupons found. Click "Create New Coupon" to start a promotion campaign.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($coupons->hasPages())
      <div class="card-footer py-3">
        {{ $coupons->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

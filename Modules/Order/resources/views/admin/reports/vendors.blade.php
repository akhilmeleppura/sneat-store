@extends('layouts/layoutMaster')

@section('title', 'Marketplace & Vendor Performance - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Filters -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold py-1 mb-1">
        <span class="text-muted fw-light">Reports & Analytics /</span> Vendor Performance
      </h4>
      <p class="text-muted mb-0">Multi-vendor marketplace commissions, payout ledger, and vendor sales velocity.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('admin.marketplace.vendors.index') }}" class="btn btn-outline-primary">
        <i class="bx bx-store-alt me-1"></i> Manage Vendors
      </a>
      <a href="{{ route('admin.marketplace.payouts.index') }}" class="btn btn-primary">
        <i class="bx bx-wallet me-1"></i> Payout Requests
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.vendors') }}" class="row g-3 align-items-end">
        <div class="col-md-4 col-sm-6">
          <label class="form-label fw-semibold">Reporting Timeframe</label>
          <select name="period" class="form-select" onchange="this.form.submit()">
            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
            <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
            <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Past Year</option>
          </select>
        </div>
        <div class="col-md-4 col-sm-6 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bx bx-filter-alt me-1"></i> Apply Timeframe
          </button>
          <a href="{{ route('admin.reports.vendors') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-4 mb-4">
    <!-- Marketplace GMV -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Marketplace GMV</span>
            <div class="avatar bg-label-primary rounded p-2">
              <i class="bx bx-globe fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['marketplace_gmv'], 2) }}</h3>
          <small class="text-muted">Total gross sales across all vendors</small>
        </div>
      </div>
    </div>

    <!-- Platform Commissions -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Platform Commission</span>
            <div class="avatar bg-label-success rounded p-2">
              <i class="bx bx-check-shield fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-success">${{ number_format($overview['platform_commissions'], 2) }}</h3>
          <small class="text-muted">Marketplace operator net revenue</small>
        </div>
      </div>
    </div>

    <!-- Vendor Net Earnings -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Vendor Net Earnings</span>
            <div class="avatar bg-label-info rounded p-2">
              <i class="bx bx-user-check fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['vendor_net_earnings'], 2) }}</h3>
          <small class="text-muted">Net allocated to vendor wallets</small>
        </div>
      </div>
    </div>

    <!-- Payout Status -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Payouts Settled</span>
            <div class="avatar bg-label-warning rounded p-2">
              <i class="bx bx-credit-card fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['payouts_completed'], 2) }}</h3>
          <small class="text-muted">
            Pending: <strong class="text-warning">${{ number_format($overview['payouts_pending'], 2) }}</strong>
          </small>
        </div>
      </div>
    </div>
  </div>

  <!-- Ecosystem Status Banner -->
  <div class="card mb-4 bg-lighter border-0">
    <div class="card-body py-3">
      <div class="row text-center gy-2">
        <div class="col-sm-4">
          <div class="text-muted small">Registered Vendors</div>
          <div class="fw-bold text-dark fs-5">
            {{ $overview['total_vendors'] }} <span class="text-success small">({{ $overview['active_vendors'] }} Active)</span>
          </div>
        </div>
        <div class="col-sm-4 border-start border-end">
          <div class="text-muted small">Outstanding Vendor Balances</div>
          <div class="fw-bold text-primary fs-5">${{ number_format($overview['total_vendor_balances'], 2) }}</div>
        </div>
        <div class="col-sm-4">
          <div class="text-muted small">Commission Take-Rate</div>
          <div class="fw-bold text-info fs-5">
            {{ $overview['marketplace_gmv'] > 0 ? round(($overview['platform_commissions'] / $overview['marketplace_gmv']) * 100, 1) : '15.0' }}%
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Vendor Leaderboard Card -->
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Vendor Sales Leaderboard</h5>
      <span class="badge bg-label-primary">Ranked by Gross Volume</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;">Rank</th>
            <th>Vendor Name</th>
            <th>Commission Rate</th>
            <th class="text-center">Orders / Units</th>
            <th class="text-end">Gross Sales</th>
            <th class="text-end">Platform Fee</th>
            <th class="text-end">Net Payout</th>
            <th class="text-end">Current Balance</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($leaderboard as $index => $vendor)
            <tr>
              <td>
                @if($index === 0)
                  <span class="badge bg-warning rounded-pill">🥇 #1</span>
                @elseif($index === 1)
                  <span class="badge bg-secondary rounded-pill">🥈 #2</span>
                @elseif($index === 2)
                  <span class="badge bg-bronze rounded-pill" style="background-color: #cd7f32; color: #fff;">🥉 #3</span>
                @else
                  <span class="badge bg-label-secondary rounded-pill">#{{ $index + 1 }}</span>
                @endif
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ $vendor['vendor_name'] }}</div>
                <div class="text-muted small">{{ $vendor['vendor_email'] }}</div>
              </td>
              <td>
                <span class="badge bg-label-info">{{ $vendor['commission_rate'] }}%</span>
              </td>
              <td class="text-center">
                <span class="badge bg-label-primary rounded-pill">{{ $vendor['sales_count'] }}</span>
              </td>
              <td class="text-end fw-bold">${{ number_format($vendor['total_gross'], 2) }}</td>
              <td class="text-end text-success fw-semibold">
                +${{ number_format($vendor['total_commission'], 2) }}
              </td>
              <td class="text-end fw-bold text-primary">
                ${{ number_format($vendor['total_net'], 2) }}
              </td>
              <td class="text-end">
                <span class="fw-bold">${{ number_format($vendor['balance'], 2) }}</span>
              </td>
              <td class="text-center">
                <a href="{{ route('admin.marketplace.vendors.index') }}" class="btn btn-sm btn-icon btn-outline-primary" title="Manage Vendors">
                  <i class="bx bx-show"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                <i class="bx bx-store fs-3 d-block mb-1"></i> No vendor transactions recorded in this period.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

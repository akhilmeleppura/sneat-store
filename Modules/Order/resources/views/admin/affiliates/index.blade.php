@extends('layouts/layoutMaster')

@section('title', 'Affiliate & Referral Management - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketing /</span> Affiliates & Referrals</h4>
      <small class="text-muted">Manage partner programs, commission payouts, and customer referral tracking</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Affiliates</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($stats['total_affiliates']) }}</h4>
              </div>
              <small class="text-muted">Registered partners</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-group bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Referred Sales</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-primary">${{ number_format($stats['referred_sales'], 2) }}</h4>
              </div>
              <small class="text-success">Attributed store revenue</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-trending-up bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Pending Commissions</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">${{ number_format($stats['pending_commissions'], 2) }}</h4>
              </div>
              <small class="text-muted">Awaiting payout</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-time bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Paid Commissions</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">${{ number_format($stats['paid_commissions'], 2) }}</h4>
              </div>
              <small class="text-success">Disbursed to partners</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-check-double bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Affiliates Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Affiliate Partners</h5>
      <form method="GET" action="{{ route('admin.affiliates.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search partner or code..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Partner</th>
            <th>Affiliate Code</th>
            <th>Commission Rate</th>
            <th>Total Earnings</th>
            <th>Pending</th>
            <th>Paid</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($affiliates as $affiliate)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ strtoupper(substr($affiliate->user?->name ?? 'A', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $affiliate->user?->name ?? 'Partner' }}</span>
                    <br><small class="text-muted">{{ $affiliate->user?->email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-label-info">
                  <code>{{ $affiliate->affiliate_code }}</code>
                </span>
              </td>
              <td>
                <form action="{{ route('admin.affiliates.update_rate', $affiliate->id) }}" method="POST" class="d-flex align-items-center gap-1">
                  @csrf
                  <input type="number" step="0.5" min="1" max="50" name="commission_rate" class="form-control form-control-sm" style="width: 70px;" value="{{ $affiliate->commission_rate }}">
                  <button type="submit" class="btn btn-sm btn-icon btn-label-primary" title="Update Rate">
                    <i class="bx bx-check"></i>
                  </button>
                </form>
              </td>
              <td><span class="fw-bold text-heading">${{ number_format($affiliate->total_earnings, 2) }}</span></td>
              <td><span class="text-warning">${{ number_format($affiliate->pending_earnings, 2) }}</span></td>
              <td><span class="text-success">${{ number_format($affiliate->paid_earnings, 2) }}</span></td>
              <td>
                @if($affiliate->status === 'active')
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-secondary">Paused</span>
                @endif
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <form action="{{ route('admin.affiliates.toggle_status', $affiliate->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon {{ $affiliate->status === 'active' ? 'btn-label-warning' : 'btn-label-success' }}" title="{{ $affiliate->status === 'active' ? 'Pause' : 'Activate' }}">
                      <i class="bx {{ $affiliate->status === 'active' ? 'bx-pause' : 'bx-play' }}"></i>
                    </button>
                  </form>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $affiliate->referral_url }}'); alert('Referral URL copied!');" title="Copy Referral Link">
                    <i class="bx bx-copy me-1"></i> Copy Link
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-share-alt bx-lg d-block mb-2 text-secondary"></i>
                No affiliate partners registered yet. Registered customers automatically receive an affiliate profile!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($affiliates->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $affiliates->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

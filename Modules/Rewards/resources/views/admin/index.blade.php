@extends('layouts/layoutMaster')

@section('title', 'Rewards & Loyalty Campaigns - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketing /</span> Rewards & Loyalty</h4>
      <small class="text-muted">Manage points programs, member tiers, and customer loyalty campaigns</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.rewards.customers') }}" class="btn btn-outline-primary">
        <i class="bx bx-user me-1"></i> View Loyalty Members
      </a>
      <a href="{{ route('admin.rewards.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> New Reward Rule
      </a>
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
              <span class="text-heading">Total Campaigns</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalCampaigns) }}</h4>
                <span class="badge bg-label-primary">{{ $activeCampaigns }} Active</span>
              </div>
              <small class="text-muted">Reward configurations</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-gift bx-sm"></i>
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
              <span class="text-heading">Loyalty Members</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalMembers) }}</h4>
              </div>
              <small class="text-muted">Registered customers with points</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
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
              <span class="text-heading">Points Issued</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalPointsIssued) }}</h4>
              </div>
              <small class="text-success">Lifetime points earned</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-star bx-sm"></i>
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
              <span class="text-heading">Points Redeemed</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalPointsRedeemed) }}</h4>
              </div>
              <small class="text-warning">Points converted to discounts</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-badge-check bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Reward Rules Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Active Reward Programs</h5>
      <form method="GET" action="{{ route('admin.rewards.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search rules..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Program Name</th>
            <th>Code</th>
            <th>Tier</th>
            <th>Earn Rate</th>
            <th>Redeem Rate</th>
            <th>Min to Redeem</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($rewards as $reward)
            <tr>
              <td>
                <span class="fw-semibold text-heading">{{ $reward->name }}</span>
                @if($reward->description)
                  <br><small class="text-muted">{{ Str::limit($reward->description, 40) }}</small>
                @endif
              </td>
              <td><code>{{ $reward->code }}</code></td>
              <td>
                <span class="badge bg-label-{{ $reward->tier === 'vip' ? 'danger' : ($reward->tier === 'gold' ? 'warning' : ($reward->tier === 'silver' ? 'info' : 'primary')) }}">
                  {{ strtoupper($reward->tier) }}
                </span>
              </td>
              <td>{{ $reward->earn_rate }} pt / $1</td>
              <td>100 pts = ${{ number_format($reward->redeem_rate * 100, 2) }}</td>
              <td>{{ number_format($reward->min_points_to_redeem) }} pts</td>
              <td>
                @if($reward->is_active)
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-secondary">Disabled</span>
                @endif
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <a href="{{ route('admin.rewards.edit', $reward->id) }}" class="btn btn-sm btn-icon btn-label-secondary" title="Edit">
                    <i class="bx bx-edit"></i>
                  </a>
                  <form action="{{ route('admin.rewards.toggle', $reward->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon {{ $reward->is_active ? 'btn-label-warning' : 'btn-label-success' }}" title="{{ $reward->is_active ? 'Deactivate' : 'Activate' }}">
                      <i class="bx {{ $reward->is_active ? 'bx-pause' : 'bx-play' }}"></i>
                    </button>
                  </form>
                  <form action="{{ route('admin.rewards.destroy', $reward->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this reward rule?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-gift bx-lg d-block mb-2 text-secondary"></i>
                No reward programs configured yet. Click <strong>"New Reward Rule"</strong> to start rewarding your customers!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($rewards->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $rewards->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

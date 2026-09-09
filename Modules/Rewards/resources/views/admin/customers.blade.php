@extends('layouts/layoutMaster')

@section('title', 'Loyalty Members - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Rewards /</span> Loyalty Members</h4>
      <small class="text-muted">Track customer loyalty tiers, point balances, and lifetime engagement</small>
    </div>
    <a href="{{ route('admin.rewards.index') }}" class="btn btn-label-secondary">
      <i class="bx bx-arrow-back me-1"></i> Reward Programs
    </a>
  </div>

  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Registered Members</h5>
      <form method="GET" action="{{ route('admin.rewards.customers') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by email..." value="{{ request('search') }}">
        <select name="tier" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Tiers</option>
          <option value="Bronze" {{ request('tier') === 'Bronze' ? 'selected' : '' }}>Bronze</option>
          <option value="Silver" {{ request('tier') === 'Silver' ? 'selected' : '' }}>Silver</option>
          <option value="Gold" {{ request('tier') === 'Gold' ? 'selected' : '' }}>Gold</option>
          <option value="Platinum" {{ request('tier') === 'Platinum' ? 'selected' : '' }}>Platinum</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Tier</th>
            <th>Current Balance</th>
            <th>Lifetime Points</th>
            <th>Est. Discount Value</th>
            <th>Joined Loyalty</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($customers as $member)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ strtoupper(substr($member->customer_email, 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $member->user?->name ?? 'Storefront Customer' }}</span>
                    <br><small class="text-muted">{{ $member->customer_email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-label-{{ $member->tier === 'Platinum' ? 'danger' : ($member->tier === 'Gold' ? 'warning' : ($member->tier === 'Silver' ? 'info' : 'secondary')) }}">
                  {{ $member->tier }}
                </span>
              </td>
              <td>
                <span class="fw-bold text-primary">{{ number_format($member->current_points) }} pts</span>
              </td>
              <td>{{ number_format($member->lifetime_points) }} pts</td>
              <td>${{ number_format($member->current_points * 0.01, 2) }}</td>
              <td>{{ $member->created_at->format('M d, Y') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                No loyalty members found. Customers are automatically enrolled upon checkout or sign up!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($customers->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $customers->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

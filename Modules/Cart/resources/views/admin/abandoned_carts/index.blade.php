@extends('layouts/layoutMaster')

@section('title', 'Abandoned Cart Recovery - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketing /</span> Abandoned Carts</h4>
      <small class="text-muted">Recover lost revenue with automated reminders and one-click cart restoration</small>
    </div>
    <form action="{{ route('admin.abandoned_carts.scan') }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="btn btn-primary">
        <i class="bx bx-radar me-1"></i> Scan Inactive Carts Now
      </button>
    </form>
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
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Abandoned</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($stats['total_abandoned']) }}</h4>
              </div>
              <small class="text-muted">Carts left before checkout</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-cart-alt bx-sm"></i>
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
              <span class="text-heading">Recoverable Value</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">${{ number_format($stats['recoverable_amount'], 2) }}</h4>
              </div>
              <small class="text-muted">Pending recovery pipeline</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-dollar-circle bx-sm"></i>
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
              <span class="text-heading">Total Recovered</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">${{ number_format($stats['recovered_amount'], 2) }}</h4>
                <span class="badge bg-label-success">{{ $stats['total_recovered'] }} orders</span>
              </div>
              <small class="text-success">Revenue rescued</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-shield bx-sm"></i>
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
              <span class="text-heading">Recovery Rate</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-primary">{{ $stats['recovery_rate'] }}%</h4>
              </div>
              <small class="text-muted">Conversion efficiency</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-trending-up bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Abandoned Cart Sessions</h5>
      <form method="GET" action="{{ route('admin.abandoned_carts.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer email..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="reminded" {{ request('status') === 'reminded' ? 'selected' : '' }}>Reminder Sent</option>
          <option value="recovered" {{ request('status') === 'recovered' ? 'selected' : '' }}>Recovered</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Items</th>
            <th>Cart Value</th>
            <th>Discount Code</th>
            <th>Status</th>
            <th>Abandoned On</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($recoveries as $recovery)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                      {{ strtoupper(substr($recovery->customer_email, 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $recovery->user?->name ?? 'Guest Buyer' }}</span>
                    <br><small class="text-muted">{{ $recovery->customer_email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-label-secondary">{{ $recovery->items_count }} items</span>
              </td>
              <td>
                <span class="fw-bold text-heading">${{ number_format($recovery->cart_subtotal, 2) }}</span>
                <small class="text-muted d-block">{{ $recovery->currency }}</small>
              </td>
              <td>
                @if($recovery->recovery_discount_code)
                  <span class="badge bg-label-primary">{{ $recovery->recovery_discount_code }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                @if($recovery->status === 'recovered')
                  <span class="badge bg-label-success">
                    <i class="bx bx-check me-1"></i> Recovered
                  </span>
                  @if($recovery->recoveredOrder)
                    <br><small><a href="{{ route('orders.index') }}">#{{ $recovery->recoveredOrder->order_number }}</a></small>
                  @endif
                @elseif($recovery->status === 'first_reminder_sent' || $recovery->status === 'second_reminder_sent')
                  <span class="badge bg-label-info">
                    <i class="bx bx-paper-plane me-1"></i> Reminded
                  </span>
                  @if($recovery->sent_at)
                    <br><small class="text-muted">{{ $recovery->sent_at->diffForHumans() }}</small>
                  @endif
                @else
                  <span class="badge bg-label-warning">Pending Reminder</span>
                @endif
              </td>
              <td>{{ $recovery->created_at->format('M d, H:i') }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  @if($recovery->status !== 'recovered')
                    <form action="{{ route('admin.abandoned_carts.remind', $recovery->id) }}" method="POST" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-primary" title="Send Recovery Reminder">
                        <i class="bx bx-paper-plane me-1"></i> Send Reminder
                      </button>
                    </form>
                  @endif
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $recovery->recovery_url }}'); alert('Recovery link copied to clipboard!');" title="Copy recovery link">
                    <i class="bx bx-copy"></i>
                  </button>
                  <a href="{{ $recovery->recovery_url }}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary" title="Test Resume">
                    <i class="bx bx-link-external"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="bx bx-cart-alt bx-lg d-block mb-2 text-secondary"></i>
                No abandoned carts detected. Click <strong>"Scan Inactive Carts Now"</strong> to scan sessions.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($recoveries->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $recoveries->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

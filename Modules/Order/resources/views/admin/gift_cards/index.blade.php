@extends('layouts/layoutMaster')

@section('title', 'Gift Cards & Store Credit - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Sales /</span> Gift Cards</h4>
      <small class="text-muted">Issue digital vouchers, track customer redemptions, and manage outstanding liability</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueGiftCardModal">
      <i class="bx bx-gift me-1"></i> Issue Gift Card
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
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
              <span class="text-heading">Total Issued</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-primary">${{ number_format($stats['total_issued'], 2) }}</h4>
              </div>
              <small class="text-muted">Gross voucher value</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-credit-card bx-sm"></i>
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
              <span class="text-heading">Outstanding Balance</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">${{ number_format($stats['active_balance'], 2) }}</h4>
              </div>
              <small class="text-success">Available for redemption</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-wallet bx-sm"></i>
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
              <span class="text-heading">Active Cards</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($stats['active_cards']) }}</h4>
              </div>
              <small class="text-info">Ready to spend</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-check-circle bx-sm"></i>
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
              <span class="text-heading">Depleted Cards</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-secondary">{{ number_format($stats['depleted_cards']) }}</h4>
              </div>
              <small class="text-muted">Zero balance remaining</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-secondary">
                <i class="bx bx-archive bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cards Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Issued Gift Cards ({{ $giftCards->total() }})</h5>
      <form method="GET" action="{{ route('admin.gift_cards.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search code or email..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active & Funded</option>
          <option value="depleted" {{ request('status') === 'depleted' ? 'selected' : '' }}>Depleted (Zero Balance)</option>
          <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Deactivated</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Card Code</th>
            <th>Recipient</th>
            <th>Initial Value</th>
            <th>Remaining Balance</th>
            <th>Expires</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($giftCards as $card)
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-dark font-monospace fs-6">{{ $card->code }}</span>
                  <button type="button" class="btn btn-xs btn-icon btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $card->code }}'); alert('Card code copied!');" title="Copy Code">
                    <i class="bx bx-copy"></i>
                  </button>
                </div>
                <small class="text-muted">Issued {{ $card->created_at->format('M d, Y') }}</small>
              </td>
              <td>
                @if($card->recipient_email)
                  <span class="fw-semibold">{{ $card->recipient_email }}</span>
                @else
                  <span class="text-muted fst-italic">Unassigned / Direct</span>
                @endif
              </td>
              <td>
                <span class="text-muted">${{ number_format($card->initial_balance, 2) }} {{ $card->currency }}</span>
              </td>
              <td>
                <span class="fw-bold {{ $card->current_balance > 0 ? 'text-success' : 'text-muted' }}">
                  ${{ number_format($card->current_balance, 2) }} {{ $card->currency }}
                </span>
              </td>
              <td>
                @if($card->expires_at)
                  <span class="{{ $card->expires_at->isPast() ? 'text-danger' : 'text-body' }} small">
                    {{ $card->expires_at->format('M d, Y') }}
                  </span>
                @else
                  <span class="badge bg-label-info">Never</span>
                @endif
              </td>
              <td>
                @if(!$card->is_active)
                  <span class="badge bg-label-secondary">Inactive</span>
                @elseif($card->expires_at && $card->expires_at->isPast())
                  <span class="badge bg-label-danger">Expired</span>
                @elseif($card->current_balance <= 0)
                  <span class="badge bg-label-warning">Depleted</span>
                @else
                  <span class="badge bg-label-success">Active</span>
                @endif
              </td>
              <td>
                <form action="{{ route('admin.gift_cards.toggle', $card->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-icon {{ $card->is_active ? 'btn-label-warning' : 'btn-label-success' }}" title="{{ $card->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="bx {{ $card->is_active ? 'bx-pause' : 'bx-play' }}"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bx bx-gift bx-lg d-block mb-2 text-secondary"></i>
                No gift cards issued yet. Click "Issue Gift Card" to create your first voucher!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($giftCards->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $giftCards->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Issue Gift Card Modal -->
<div class="modal fade" id="issueGiftCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('admin.gift_cards.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-gift me-1 text-primary"></i> Issue Digital Gift Card</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Card Amount ($)</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="1" min="5" max="5000" name="amount" class="form-control form-control-lg fw-bold" placeholder="50.00" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label">Currency</label>
              <select name="currency" class="form-select">
                <option value="USD">USD ($)</option>
                <option value="EUR">EUR (€)</option>
                <option value="GBP">GBP (£)</option>
                <option value="CAD">CAD ($)</option>
                <option value="AUD">AUD ($)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Validity Period</label>
              <select name="valid_days" class="form-select">
                <option value="365">1 Year (365 days)</option>
                <option value="730">2 Years (730 days)</option>
                <option value="180">6 Months (180 days)</option>
                <option value="">Never Expires</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Recipient Email (Optional)</label>
            <input type="email" name="recipient_email" class="form-control" placeholder="customer@example.com">
            <small class="text-muted">Optional: Assign card directly to a customer account.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Issue Gift Card</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

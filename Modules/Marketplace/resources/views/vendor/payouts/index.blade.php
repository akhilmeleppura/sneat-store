@extends('layouts/layoutMaster')

@section('title', 'Payouts & Earnings - ' . $vendor->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Vendor /</span> Payouts & Balance</h4>
      <small class="text-muted">Review available earnings, submit withdrawal requests, and view payout history.</small>
    </div>
    @if($vendor->balance >= $minPayout)
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestPayoutModal">
        <i class="bx bx-export me-1"></i> Request Payout
      </button>
    @else
      <button type="button" class="btn btn-secondary" disabled title="Minimum payout is ${{ number_format($minPayout, 2) }}">
        <i class="bx bx-lock me-1"></i> Minimum ${{ number_format($minPayout, 2) }} Required
      </button>
    @endif
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

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

  <!-- Balance Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-4">
      <div class="card">
        <div class="card-body">
          <span class="text-muted">Available Withdrawable Balance</span>
          <h3 class="my-2 text-success fw-bold">${{ number_format($vendor->balance, 2) }}</h3>
          <small class="text-muted">Minimum threshold: ${{ number_format($minPayout, 2) }}</small>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card">
        <div class="card-body">
          <span class="text-muted">Pending Payouts</span>
          @php $pendingAmount = $payouts->where('status', 'requested')->sum('amount'); @endphp
          <h3 class="my-2 text-warning fw-bold">${{ number_format($pendingAmount, 2) }}</h3>
          <small class="text-muted">Under administrative review</small>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card">
        <div class="card-body">
          <span class="text-muted">Total Withdrawn to Date</span>
          @php $completedAmount = $payouts->where('status', 'completed')->sum('amount'); @endphp
          <h3 class="my-2 text-primary fw-bold">${{ number_format($completedAmount, 2) }}</h3>
          <small class="text-muted">Direct to bank / payment account</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Payout Requests History -->
  <div class="card">
    <h5 class="card-header">Payout History</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Reference</th>
            <th>Status</th>
            <th>Requested Date</th>
            <th>Processed Date</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($payouts as $payout)
            <tr>
              <td>#{{ $payout->id }}</td>
              <td><span class="fw-bold">${{ number_format($payout->amount, 2) }} {{ $payout->currency }}</span></td>
              <td><span class="badge bg-label-secondary text-capitalize">{{ str_replace('_', ' ', $payout->payout_method) }}</span></td>
              <td><code>{{ $payout->transaction_reference ?? '—' }}</code></td>
              <td>{!! $payout->status_badge !!}</td>
              <td>{{ $payout->created_at->format('M d, Y') }}</td>
              <td>{{ $payout->processed_at ? $payout->processed_at->format('M d, Y') : '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No payout requests submitted yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($payouts->hasPages())
      <div class="card-footer">
        {{ $payouts->links() }}
      </div>
    @endif
  </div>

  <!-- Request Payout Modal -->
  <div class="modal fade" id="requestPayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form action="{{ route('vendor.payouts.store') }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Request Balance Payout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Withdraw funds directly to your verified payout account.</p>
          <div class="mb-3">
            <label class="form-label">Withdrawal Amount (USD)</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="0.01" name="amount" class="form-control" value="{{ $vendor->balance }}" max="{{ $vendor->balance }}" min="{{ $minPayout }}" required>
            </div>
            <small class="text-muted">Available: ${{ number_format($vendor->balance, 2) }} (Min: ${{ number_format($minPayout, 2) }})</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Payout Method</label>
            <select name="payout_method" class="form-select" required>
              <option value="bank_transfer">Direct Bank Wire Transfer</option>
              <option value="paypal">PayPal Express Payout</option>
              <option value="stripe">Stripe Connect Account</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Account / Wire Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Bank routing details, account number, or PayPal email..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

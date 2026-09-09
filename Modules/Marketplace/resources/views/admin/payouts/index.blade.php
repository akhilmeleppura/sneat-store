@extends('layouts/layoutMaster')

@section('title', 'Vendor Payout Requests - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketplace /</span> Payout Requests</h4>
      <small class="text-muted">Review, approve, and finalize vendor balance withdrawal settlements.</small>
    </div>
    <a href="{{ route('admin.marketplace.vendors.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Vendors
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header">Payout Ledger ({{ $payouts->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Vendor</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Requested Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($payouts as $payout)
            <tr>
              <td>#{{ $payout->id }}</td>
              <td>
                <span class="fw-semibold">{{ $payout->vendor?->name ?? 'Unknown' }}</span>
                <small class="text-muted d-block">Current Balance: ${{ number_format($payout->vendor?->balance ?? 0, 2) }}</small>
              </td>
              <td><span class="fw-bold">${{ number_format($payout->amount, 2) }} {{ $payout->currency }}</span></td>
              <td><span class="badge bg-label-secondary text-capitalize">{{ str_replace('_', ' ', $payout->payout_method) }}</span></td>
              <td>{!! $payout->status_badge !!}</td>
              <td>{{ $payout->created_at->format('M d, Y H:i') }}</td>
              <td>
                @if($payout->status === 'requested')
                  <button type="button" class="btn btn-xs btn-success" data-bs-toggle="modal" data-bs-target="#actionPayoutModal{{ $payout->id }}">
                    <i class="bx bx-check me-1"></i> Review & Process
                  </button>

                  <!-- Modal -->
                  <div class="modal fade" id="actionPayoutModal{{ $payout->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <form action="{{ route('admin.marketplace.payouts.action', $payout->id) }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Process Payout #{{ $payout->id }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <p>Vendor: <strong>{{ $payout->vendor?->name }}</strong></p>
                          <p>Payout Amount: <strong class="text-primary">${{ number_format($payout->amount, 2) }} {{ $payout->currency }}</strong></p>
                          @if($payout->notes)
                            <div class="alert alert-light border py-2">
                              <small class="text-muted d-block">Vendor Notes:</small>
                              <p class="mb-0">{{ $payout->notes }}</p>
                            </div>
                          @endif

                          <div class="mb-3">
                            <label class="form-label">Decision</label>
                            <select name="action" class="form-select" required>
                              <option value="approve">Approve & Mark Completed (Deduct Balance)</option>
                              <option value="reject">Reject Request</option>
                            </select>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Transaction / Bank Reference</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="Wire transfer reference, PayPal transaction ID...">
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Notes for vendor notification..."></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Submit Decision</button>
                        </div>
                      </form>
                    </div>
                  </div>
                @else
                  <span class="text-muted small">Processed</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No payout requests found.</td>
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
</div>
@endsection

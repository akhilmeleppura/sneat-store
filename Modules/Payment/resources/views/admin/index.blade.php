@extends('layouts/layoutMaster')

@section('title', 'Payment Transactions & Ledger - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Finance & Billing /</span> Transactions & Settlement</h4>
      <small class="text-muted">Real-time payment gateway ledger, cross-module invoice settlement, and audit logs</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-list-ul me-1"></i> View Orders
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters & Search Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.payments.index') }}" class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Search Reference or Customer</label>
          <input type="text" name="search" class="form-control" placeholder="Search reference, order #, or customer..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Gateway</label>
          <select name="gateway" class="form-select">
            <option value="">All Gateways</option>
            <option value="stripe" {{ request('gateway') === 'stripe' ? 'selected' : '' }}>Stripe</option>
            <option value="paypal" {{ request('gateway') === 'paypal' ? 'selected' : '' }}>PayPal</option>
            <option value="cod" {{ request('gateway') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
            <option value="bank_transfer" {{ request('gateway') === 'bank_transfer' ? 'selected' : '' }}>Bank Wire</option>
            <option value="mock" {{ request('gateway') === 'mock' ? 'selected' : '' }}>Mock Simulator</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="successful" {{ request('status') === 'successful' ? 'selected' : '' }}>Successful</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Transactions Table Card -->
  <div class="card">
    <h5 class="card-header">Payment Ledger ({{ $transactions->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Transaction Reference</th>
            <th>Order</th>
            <th>Gateway</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($transactions as $txn)
            <tr>
              <td>
                <span class="fw-semibold font-monospace">{{ $txn->transaction_reference }}</span>
                @if(!empty($txn->payment_method_details['brand']))
                  <small class="text-muted d-block">{{ $txn->payment_method_details['brand'] }} •••• {{ $txn->payment_method_details['last4'] ?? '' }}</small>
                @endif
              </td>
              <td>
                @if($txn->order)
                  <a href="{{ route('admin.orders.show', $txn->order->id) }}" class="fw-semibold">
                    #{{ $txn->order->order_number }}
                  </a>
                  <small class="text-muted d-block">{{ $txn->order->customer_name }}</small>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                <span class="badge bg-label-secondary text-uppercase">{{ $txn->gateway }}</span>
              </td>
              <td>
                <span class="fw-bold">${{ number_format($txn->amount, 2) }}</span>
                <small class="text-muted">{{ $txn->currency }}</small>
              </td>
              <td>
                {!! $txn->status_badge !!}
              </td>
              <td>
                <span>{{ $txn->created_at->format('M d, Y') }}</span>
                <small class="text-muted d-block">{{ $txn->created_at->format('H:i') }}</small>
              </td>
              <td>
                @if($txn->status === 'successful')
                  <button type="button" class="btn btn-xs btn-outline-danger" data-bs-toggle="modal" data-bs-target="#refundModal{{ $txn->id }}">
                    <i class="bx bx-undo me-1"></i> Refund
                  </button>

                  <!-- Refund Modal -->
                  <div class="modal fade" id="refundModal{{ $txn->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <form action="{{ route('admin.payments.refund', $txn->id) }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Refund Transaction</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <p>Transaction: <code>{{ $txn->transaction_reference }}</code></p>
                          <p>Total Original Amount: <strong>${{ number_format($txn->amount, 2) }} {{ $txn->currency }}</strong></p>
                          <div class="mb-3">
                            <label class="form-label">Refund Amount</label>
                            <input type="number" step="0.01" max="{{ $txn->amount }}" min="0.01" name="amount" class="form-control" value="{{ $txn->amount }}" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Reason for Refund</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="Customer request, damaged good, cancellation..." required></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-danger">Confirm Refund</button>
                        </div>
                      </form>
                    </div>
                  </div>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No transactions found matching your criteria.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($transactions->hasPages())
      <div class="card-footer">
        {{ $transactions->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

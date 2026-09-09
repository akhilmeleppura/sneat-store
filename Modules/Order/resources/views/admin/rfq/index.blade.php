@extends('layouts/layoutMaster')

@section('title', 'B2B Quotes (RFQ) - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Sales /</span> B2B Quotations (RFQ)</h4>
      <small class="text-muted">Review corporate quotation inquiries, configure wholesale volume pricing, and issue proposals</small>
    </div>
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
              <span class="text-heading">Total Inquiries</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($stats['total']) }}</h4>
              </div>
              <small class="text-muted">Wholesale requests</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-file bx-sm"></i>
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
              <span class="text-heading">Pending Review</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ number_format($stats['pending']) }}</h4>
              </div>
              <small class="text-muted">Awaiting price quotes</small>
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
              <span class="text-heading">Proposals Sent</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($stats['quoted']) }}</h4>
              </div>
              <small class="text-info">Awaiting buyer response</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-paper-plane bx-sm"></i>
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
              <span class="text-heading">Won & Accepted</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['accepted']) }}</h4>
              </div>
              <small class="text-success">Deals closed</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-circle bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RFQ Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Quotations List</h5>
      <form method="GET" action="{{ route('admin.rfq.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search quote #, company, email..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="reviewing" {{ request('status') === 'reviewing' ? 'selected' : '' }}>Reviewing</option>
          <option value="quoted" {{ request('status') === 'quoted' ? 'selected' : '' }}>Quoted</option>
          <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Quote #</th>
            <th>Company / Buyer</th>
            <th>Contact</th>
            <th>Items</th>
            <th>Quoted Total</th>
            <th>Status</th>
            <th>Valid Until</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($quotes as $quote)
            <tr>
              <td>
                <a href="{{ route('admin.rfq.show', $quote->id) }}" class="fw-bold">
                  {{ $quote->quote_number }}
                </a>
                <br><small class="text-muted">{{ $quote->created_at->format('M d, Y') }}</small>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-secondary">
                      <i class="bx bx-buildings"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $quote->company_name }}</span>
                    @if($quote->tax_id)
                      <br><small class="text-muted">Tax ID: {{ $quote->tax_id }}</small>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <span>{{ $quote->contact_name }}</span>
                <br><small class="text-muted">{{ $quote->contact_email }}</small>
              </td>
              <td>
                @php
                  $itemCount = is_array($quote->items_payload) ? count($quote->items_payload) : 0;
                @endphp
                <span class="badge bg-label-dark">{{ $itemCount }} item line(s)</span>
              </td>
              <td>
                @if($quote->quoted_total)
                  <span class="fw-bold text-success">${{ number_format($quote->quoted_total, 2) }}</span>
                @else
                  <span class="text-muted fst-italic">Pending pricing</span>
                @endif
              </td>
              <td>
                @php
                  $statusBadge = [
                    'pending'   => 'warning',
                    'reviewing' => 'info',
                    'quoted'    => 'primary',
                    'accepted'  => 'success',
                    'rejected'  => 'danger',
                  ];
                @endphp
                <span class="badge bg-label-{{ $statusBadge[$quote->status] ?? 'secondary' }} text-uppercase">
                  {{ $quote->status }}
                </span>
              </td>
              <td>
                @if($quote->valid_until)
                  <span class="{{ $quote->valid_until->isPast() ? 'text-danger' : 'text-muted' }} small">
                    {{ $quote->valid_until->format('M d, Y') }}
                  </span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.rfq.show', $quote->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-show me-1"></i> Review & Quote
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bx bx-file bx-lg d-block mb-2 text-secondary"></i>
                No B2B quotation requests found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($quotes->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $quotes->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

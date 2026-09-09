@extends('layouts/layoutMaster')

@section('title', "Quotation #{$quote->quote_number} - Sneat Admin")

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Breadcrumb & Header -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold py-1 mb-0">
        <a href="{{ route('admin.rfq.index') }}" class="text-muted fw-light">B2B Quotations /</a> #{{ $quote->quote_number }}
      </h4>
      <small class="text-muted">Submitted on {{ $quote->created_at->format('F d, Y \a\t H:i') }}</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.rfq.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to Quotes
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left Column: Requested Line Items & Notes -->
    <div class="col-lg-8">
      <!-- Items Card -->
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Requested Wholesale Items</h5>
          @php
            $items = is_array($quote->items_payload) ? $quote->items_payload : [];
          @endphp
          <span class="badge bg-label-primary">{{ count($items) }} Line Item(s)</span>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Item / Description</th>
                <th>SKU / Variant</th>
                <th>Quantity</th>
                <th>Target Price (Unit)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $index => $item)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>
                    <span class="fw-bold text-heading">{{ $item['product_name'] ?? $item['name'] ?? 'Product Line Item' }}</span>
                    @if(!empty($item['notes']))
                      <br><small class="text-muted">{{ $item['notes'] }}</small>
                    @endif
                  </td>
                  <td>
                    <code>{{ $item['sku'] ?? 'N/A' }}</code>
                  </td>
                  <td>
                    <span class="badge bg-label-dark fs-6">{{ $item['quantity'] ?? 1 }} units</span>
                  </td>
                  <td>
                    @if(!empty($item['target_price']))
                      <span class="text-muted">${{ number_format($item['target_price'], 2) }}</span>
                    @else
                      <span class="text-muted fst-italic">Open to quote</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No line items recorded in payload.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Buyer Inbound Notes -->
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0">Buyer Requirements & Notes</h5>
        </div>
        <div class="card-body pt-3">
          @if($quote->notes)
            <div class="p-3 bg-light rounded text-body">
              {{ $quote->notes }}
            </div>
          @else
            <p class="text-muted fst-italic mb-0">No special instructions or comments provided by the buyer.</p>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Buyer Info & Quotation Proposal Form -->
    <div class="col-lg-4">
      <!-- Buyer Information Card -->
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0">Company Information</h5>
        </div>
        <div class="card-body pt-3">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar avatar-md me-3">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-buildings bx-sm"></i>
              </span>
            </div>
            <div>
              <h6 class="mb-0 fw-bold">{{ $quote->company_name }}</h6>
              <small class="text-muted">Registered Corporate Entity</small>
            </div>
          </div>

          <ul class="list-unstyled mb-0">
            <li class="mb-2">
              <span class="fw-semibold text-heading">Contact Person:</span>
              <span class="float-end">{{ $quote->contact_name }}</span>
            </li>
            <li class="mb-2">
              <span class="fw-semibold text-heading">Email:</span>
              <a href="mailto:{{ $quote->contact_email }}" class="float-end">{{ $quote->contact_email }}</a>
            </li>
            <li class="mb-2">
              <span class="fw-semibold text-heading">Phone:</span>
              <span class="float-end">{{ $quote->contact_phone ?? 'Not provided' }}</span>
            </li>
            <li class="mb-2">
              <span class="fw-semibold text-heading">Tax ID / VAT:</span>
              <span class="float-end font-monospace">{{ $quote->tax_id ?? 'N/A' }}</span>
            </li>
            <li>
              <span class="fw-semibold text-heading">Current Status:</span>
              <span class="float-end badge bg-label-primary text-uppercase">{{ $quote->status }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Formal Quote Proposal Form -->
      <div class="card mb-4 border-primary">
        <div class="card-header border-bottom bg-label-primary">
          <h5 class="card-title mb-0 text-primary fw-bold">
            <i class="bx bx-paper-plane me-1"></i> Issue Quotation Proposal
          </h5>
        </div>
        <div class="card-body pt-4">
          <form action="{{ route('admin.rfq.proposal', $quote->id) }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold">Quoted Total Amount ($)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" name="quoted_total" class="form-control form-control-lg fw-bold text-success" value="{{ old('quoted_total', $quote->quoted_total) }}" placeholder="0.00" required>
              </div>
              <small class="text-muted">Total wholesale package price including volume discounts.</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Offer Validity (Days)</label>
              <input type="number" min="1" max="90" name="valid_days" class="form-control" value="14" required>
              <small class="text-muted">Quote expires after this duration.</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Proposal Terms / Seller Comments</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="Lead times, shipping freight terms, payment terms (e.g. Net 30)...">{{ old('notes', $quote->notes) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="bx bx-send me-1"></i> Send / Update Proposal
            </button>
          </form>
        </div>
      </div>

      <!-- Quick Status Actions -->
      <div class="card">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0">Lifecycle Actions</h5>
        </div>
        <div class="card-body pt-3 d-flex flex-column gap-2">
          <form action="{{ route('admin.rfq.status', $quote->id) }}" method="POST">
            @csrf
            <input type="hidden" name="status" value="reviewing">
            <button type="submit" class="btn btn-outline-info w-100" {{ $quote->status === 'reviewing' ? 'disabled' : '' }}>
              <i class="bx bx-time me-1"></i> Mark as In-Review
            </button>
          </form>

          <form action="{{ route('admin.rfq.status', $quote->id) }}" method="POST">
            @csrf
            <input type="hidden" name="status" value="accepted">
            <button type="submit" class="btn btn-outline-success w-100" {{ $quote->status === 'accepted' ? 'disabled' : '' }}>
              <i class="bx bx-check-circle me-1"></i> Mark as Accepted / Won
            </button>
          </form>

          <form action="{{ route('admin.rfq.status', $quote->id) }}" method="POST">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <button type="submit" class="btn btn-outline-danger w-100" {{ $quote->status === 'rejected' ? 'disabled' : '' }}>
              <i class="bx bx-x-circle me-1"></i> Reject Quotation
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

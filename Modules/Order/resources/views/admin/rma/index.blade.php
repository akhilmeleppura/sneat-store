@extends('layouts/layoutMaster')

@section('title', 'RMA & Return Authorizations - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Orders /</span> Return Authorizations (RMA)</h4>
      <small class="text-muted">Inspect customer return requests, issue return tracking labels, and process refunds or replacements</small>
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
              <span class="text-heading">Pending Approval</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ number_format($stats['pending']) }}</h4>
              </div>
              <small class="text-muted">Requires review</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-time-five bx-sm"></i>
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
              <span class="text-heading">Approved / In-Transit</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-primary">{{ number_format($stats['approved']) }}</h4>
              </div>
              <small class="text-primary">Label issued / shipping</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-package bx-sm"></i>
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
              <span class="text-heading">Received / Inspected</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($stats['received']) }}</h4>
              </div>
              <small class="text-info">In warehouse triage</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
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
              <span class="text-heading">Resolved & Closed</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['resolved']) }}</h4>
              </div>
              <small class="text-success">Refunded / exchanged</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-double bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RMA Data Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Return Requests ({{ $rmaRequests->total() }})</h5>
      <form method="GET" action="{{ route('admin.rma.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search RMA #, Order #, customer..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
          <option value="label_issued" {{ request('status') === 'label_issued' ? 'selected' : '' }}>Label Issued</option>
          <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
          <option value="inspected" {{ request('status') === 'inspected' ? 'selected' : '' }}>Inspected</option>
          <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>RMA Number</th>
            <th>Customer</th>
            <th>Order #</th>
            <th>Reason & Condition</th>
            <th>Resolution</th>
            <th>Status</th>
            <th>Return Tracking</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($rmaRequests as $rma)
            <tr>
              <td>
                <span class="badge bg-label-dark fw-bold">{{ $rma->rma_number }}</span>
                <br><small class="text-muted">{{ $rma->created_at->format('M d, Y H:i') }}</small>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ strtoupper(substr($rma->user?->name ?? 'C', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $rma->user?->name ?? 'Guest / Customer' }}</span>
                    <br><small class="text-muted">{{ $rma->user?->email }}</small>
                  </div>
                </div>
              </td>
              <td>
                @if($rma->order)
                  <a href="{{ route('admin.orders.show', $rma->order_id) }}" class="fw-bold">
                    #{{ $rma->order->order_number }}
                  </a>
                @else
                  <span class="text-muted">#{{ $rma->order_id }}</span>
                @endif
              </td>
              <td>
                <span class="fw-semibold">{{ $rma->reason }}</span>
                <br><small class="text-muted">Condition: <span class="badge bg-label-secondary text-uppercase">{{ $rma->condition }}</span></small>
              </td>
              <td>
                <span class="badge bg-label-{{ $rma->resolution_type === 'refund' ? 'danger' : ($rma->resolution_type === 'exchange' ? 'info' : 'primary') }}">
                  {{ strtoupper(str_replace('_', ' ', $rma->resolution_type)) }}
                </span>
              </td>
              <td>
                @php
                  $statusClasses = [
                    'pending'      => 'bg-label-warning',
                    'approved'     => 'bg-label-primary',
                    'label_issued' => 'bg-label-info',
                    'received'     => 'bg-label-secondary',
                    'inspected'    => 'bg-label-dark',
                    'resolved'     => 'bg-label-success',
                    'rejected'     => 'bg-label-danger',
                  ];
                @endphp
                <span class="badge {{ $statusClasses[$rma->status] ?? 'bg-label-secondary' }}">
                  {{ strtoupper(str_replace('_', ' ', $rma->status)) }}
                </span>
              </td>
              <td>
                @if($rma->return_tracking_number)
                  <code class="text-primary">{{ $rma->return_tracking_number }}</code>
                @else
                  <span class="text-muted fst-italic">Not Dispatched</span>
                @endif
              </td>
              <td>
                <!-- Trigger Modal -->
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateRmaModal{{ $rma->id }}">
                  <i class="bx bx-edit me-1"></i> Update
                </button>

                <!-- Update Modal -->
                <div class="modal fade" id="updateRmaModal{{ $rma->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <form action="{{ route('admin.rma.status', $rma->id) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Update RMA #{{ $rma->rma_number }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label">Lifecycle Status</label>
                            <select name="status" class="form-select" required>
                              <option value="pending" {{ $rma->status === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                              <option value="approved" {{ $rma->status === 'approved' ? 'selected' : '' }}>Approved</option>
                              <option value="label_issued" {{ $rma->status === 'label_issued' ? 'selected' : '' }}>Label Issued</option>
                              <option value="received" {{ $rma->status === 'received' ? 'selected' : '' }}>Received at Warehouse</option>
                              <option value="inspected" {{ $rma->status === 'inspected' ? 'selected' : '' }}>Inspected</option>
                              <option value="resolved" {{ $rma->status === 'resolved' ? 'selected' : '' }}>Resolved (Refunded / Exchanged)</option>
                              <option value="rejected" {{ $rma->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Return Courier Tracking #</label>
                            <input type="text" name="return_tracking_number" class="form-control" value="{{ $rma->return_tracking_number }}" placeholder="e.g. 1Z9999999999999999 or FEDEX-82910">
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Internal Resolution Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Inspection notes, refund confirmation, or reason for rejection...">{{ $rma->admin_notes }}</textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-revision bx-lg d-block mb-2 text-secondary"></i>
                No RMA return authorization requests found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($rmaRequests->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $rmaRequests->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

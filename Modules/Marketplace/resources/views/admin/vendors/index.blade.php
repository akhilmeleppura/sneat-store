@extends('layouts/layoutMaster')

@section('title', 'Marketplace Vendors - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketplace /</span> Vendors</h4>
      <small class="text-muted">Manage seller accounts, approval statuses, commission rates, and balances.</small>
    </div>
    <a href="{{ route('admin.marketplace.payouts.index') }}" class="btn btn-outline-primary">
      <i class="bx bx-wallet me-1"></i> Payout Requests
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters & Search -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.marketplace.vendors.index') }}" class="row g-3">
        <div class="col-md-6">
          <input type="text" name="search" class="form-control" placeholder="Search vendor name, email..." value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('admin.marketplace.vendors.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Vendors Table -->
  <div class="card">
    <h5 class="card-header">All Vendors ({{ $vendors->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Vendor Name</th>
            <th>Owner</th>
            <th>Commission</th>
            <th>Products</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($vendors as $vendor)
            <tr>
              <td>
                <span class="fw-semibold">{{ $vendor->name }}</span>
                <small class="text-muted d-block">{{ $vendor->email ?? 'No email' }}</small>
              </td>
              <td>{{ $vendor->user?->name ?? 'Unassigned' }}</td>
              <td><span class="badge bg-label-info">{{ $vendor->commission_rate }}%</span></td>
              <td>{{ $vendor->products_count }} listed</td>
              <td><span class="fw-bold">${{ number_format($vendor->balance, 2) }}</span></td>
              <td>{!! $vendor->status_badge !!}</td>
              <td>
                <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editVendorModal{{ $vendor->id }}">
                  <i class="bx bx-edit-alt me-1"></i> Manage
                </button>

                <!-- Manage Modal -->
                <div class="modal fade" id="editVendorModal{{ $vendor->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <form action="{{ route('admin.marketplace.vendors.status', $vendor->id) }}" method="POST" class="modal-content">
                      @csrf
                      <div class="modal-header">
                        <h5 class="modal-title">Manage Vendor: {{ $vendor->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Vendor Account Status</label>
                          <select name="status" class="form-select">
                            <option value="active" {{ $vendor->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ $vendor->status === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="suspended" {{ $vendor->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Platform Commission Rate (%)</label>
                          <div class="input-group">
                            <input type="number" step="0.5" min="0" max="100" name="commission_rate" class="form-control" value="{{ $vendor->commission_rate }}" required>
                            <span class="input-group-text">%</span>
                          </div>
                        </div>
                        <p class="text-muted small">Adjusting commission rates will apply to all subsequent orders fulfilled by this vendor.</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No vendors registered yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($vendors->hasPages())
      <div class="card-footer">
        {{ $vendors->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

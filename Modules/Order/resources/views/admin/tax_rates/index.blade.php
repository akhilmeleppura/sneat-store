@extends('layouts/layoutMaster')

@section('title', 'Regional Tax Rates & VAT - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Settings /</span> Regional Taxes & VAT</h4>
      <small class="text-muted">Configure state sales tax, European VAT, compound rates, and wholesale B2B tax exemptions</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaxRateModal">
      <i class="bx bx-plus me-1"></i> Add Tax Rate
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
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
              <span class="text-heading">Total Tax Rules</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($stats['total']) }}</h4>
              </div>
              <small class="text-muted">Configured rates</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-globe bx-sm"></i>
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
              <span class="text-heading">Active Rates</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['active']) }}</h4>
              </div>
              <small class="text-success">Applied at checkout</small>
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

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">B2B Exempt Rules</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($stats['b2b_exempt']) }}</h4>
              </div>
              <small class="text-info">Wholesale VAT reverse-charge</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-buildings bx-sm"></i>
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
              <span class="text-heading">Compound Rates</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ number_format($stats['compound_rates']) }}</h4>
              </div>
              <small class="text-muted">Multi-tier surcharges</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-layer bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tax Rates Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Tax Jurisdictions</h5>
      <form method="GET" action="{{ route('admin.taxes.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, country or state..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Jurisdiction</th>
            <th>Tax Name</th>
            <th>Rate (%)</th>
            <th>Compound Tax</th>
            <th>B2B Reverse-Charge</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($taxRates as $tax)
            <tr>
              <td>
                <span class="badge bg-label-dark font-monospace fs-6">{{ $tax->country_code }}</span>
                @if($tax->state_code)
                  <span class="badge bg-label-secondary font-monospace">{{ $tax->state_code }}</span>
                @else
                  <span class="text-muted small">All States / Provinces</span>
                @endif
              </td>
              <td>
                <span class="fw-semibold text-heading">{{ $tax->tax_name }}</span>
              </td>
              <td>
                <span class="fw-bold text-primary fs-6">{{ number_format($tax->rate_percentage, 2) }}%</span>
              </td>
              <td>
                @if($tax->is_compound)
                  <span class="badge bg-label-warning"><i class="bx bx-check me-1"></i> Compound</span>
                @else
                  <span class="text-muted small">Standard</span>
                @endif
              </td>
              <td>
                @if($tax->is_b2b_exempt)
                  <span class="badge bg-label-info"><i class="bx bx-check me-1"></i> Exempt</span>
                @else
                  <span class="text-muted small">No Exemption</span>
                @endif
              </td>
              <td>
                @if($tax->is_active)
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-secondary">Disabled</span>
                @endif
              </td>
              <td>
                <div class="d-flex align-items-center gap-1">
                  <!-- Toggle Button -->
                  <form action="{{ route('admin.taxes.toggle', $tax->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon {{ $tax->is_active ? 'btn-label-warning' : 'btn-label-success' }}" title="{{ $tax->is_active ? 'Disable' : 'Enable' }}">
                      <i class="bx {{ $tax->is_active ? 'bx-pause' : 'bx-play' }}"></i>
                    </button>
                  </form>

                  <!-- Edit Modal Trigger -->
                  <button type="button" class="btn btn-sm btn-icon btn-label-primary" data-bs-toggle="modal" data-bs-target="#editTaxModal{{ $tax->id }}" title="Edit Rate">
                    <i class="bx bx-edit"></i>
                  </button>

                  <!-- Delete -->
                  <form action="{{ route('admin.taxes.destroy', $tax->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $tax->tax_name }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editTaxModal{{ $tax->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <form action="{{ route('admin.taxes.update', $tax->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                          <h5 class="modal-title fw-bold">Edit Tax Rule #{{ $tax->id }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row g-2 mb-3">
                            <div class="col-md-6">
                              <label class="form-label fw-semibold">Country Code (ISO 2)</label>
                              <input type="text" name="country_code" class="form-control text-uppercase" maxlength="2" value="{{ old('country_code', $tax->country_code) }}" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label fw-semibold">State / Region Code</label>
                              <input type="text" name="state_code" class="form-control text-uppercase" maxlength="10" value="{{ old('state_code', $tax->state_code) }}" placeholder="Optional (e.g. NY)">
                            </div>
                          </div>

                          <div class="mb-3">
                            <label class="form-label fw-semibold">Tax Name / Label</label>
                            <input type="text" name="tax_name" class="form-control" value="{{ old('tax_name', $tax->tax_name) }}" required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label fw-semibold">Tax Rate (%)</label>
                            <div class="input-group">
                              <input type="number" step="0.01" min="0" max="100" name="rate_percentage" class="form-control fw-bold" value="{{ old('rate_percentage', $tax->rate_percentage) }}" required>
                              <span class="input-group-text">%</span>
                            </div>
                          </div>

                          <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_compound" value="1" id="compoundEdit{{ $tax->id }}" {{ $tax->is_compound ? 'checked' : '' }}>
                            <label class="form-check-label" for="compoundEdit{{ $tax->id }}">Compound Tax (Applied on subtotal + previous taxes)</label>
                          </div>

                          <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_b2b_exempt" value="1" id="b2bEdit{{ $tax->id }}" {{ $tax->is_b2b_exempt ? 'checked' : '' }}>
                            <label class="form-check-label" for="b2bEdit{{ $tax->id }}">B2B Tax Exemption (Buyers with valid Tax ID exempt)</label>
                          </div>

                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeEdit{{ $tax->id }}" {{ $tax->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="activeEdit{{ $tax->id }}">Active & In-Use</label>
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
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bx bx-globe bx-lg d-block mb-2 text-secondary"></i>
                No tax jurisdictions defined yet. Click "Add Tax Rate" to create your first regional tax rule.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($taxRates->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $taxRates->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Add Tax Rate Modal -->
<div class="modal fade" id="addTaxRateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('admin.taxes.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-plus me-1 text-primary"></i> Create Regional Tax Rule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Country Code (ISO 2)</label>
              <input type="text" name="country_code" class="form-control text-uppercase" maxlength="2" placeholder="e.g. US, GB, CA" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">State / Region Code</label>
              <input type="text" name="state_code" class="form-control text-uppercase" maxlength="10" placeholder="e.g. CA, NY (Optional)">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tax Rule Name</label>
            <input type="text" name="tax_name" class="form-control" placeholder="e.g. State Sales Tax, Federal VAT, GST" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Percentage Rate (%)</label>
            <div class="input-group">
              <input type="number" step="0.01" min="0" max="100" name="rate_percentage" class="form-control fw-bold" placeholder="7.25" required>
              <span class="input-group-text">%</span>
            </div>
          </div>

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_compound" value="1" id="compoundNew">
            <label class="form-check-label" for="compoundNew">Compound Tax</label>
          </div>

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_b2b_exempt" value="1" id="b2bNew" checked>
            <label class="form-check-label" for="b2bNew">B2B Tax Exemption (Requires buyer Tax ID)</label>
          </div>

          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeNew" checked>
            <label class="form-check-label" for="activeNew">Active immediately</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Save Tax Rule</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

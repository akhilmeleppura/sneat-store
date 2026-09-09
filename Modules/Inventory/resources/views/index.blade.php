@extends('layouts/layoutMaster')

@section('title', 'Inventory & Stock Management - Sneat')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Inventory /</span> Stock Management</h4>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory.transactions') }}" class="btn btn-outline-secondary">
        <i class="bx bx-history me-1"></i> Audit Logs
      </a>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickAdjustModal">
        <i class="bx bx-transfer-alt me-1"></i> Adjust Stock
      </button>
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

  <!-- Filters Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('inventory.index') }}" class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Search Product / SKU</label>
          <input type="text" name="search" class="form-control" placeholder="Search by name or variant SKU..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Branch / Warehouse</label>
          <select name="branch_id" class="form-select">
            <option value="">All Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Stock Status</label>
          <select name="filter" class="form-select">
            <option value="">All Stock</option>
            <option value="low_stock" {{ request('filter') === 'low_stock' ? 'selected' : '' }}>Low Stock Alerts</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Stock Table Card -->
  <div class="card">
    <h5 class="card-header">Branch Stock Levels ({{ $stocks->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Product & Variant</th>
            <th>Branch</th>
            <th>On Hand</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>Reorder Level</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($stocks as $stock)
            <tr>
              <td>
                <span class="fw-semibold text-body d-block">{{ $stock->product->name ?? 'Unknown' }}</span>
                <small class="text-muted">SKU: {{ $stock->variant->sku ?? 'N/A' }} | {{ $stock->variant->attribute_summary ?? '' }}</small>
              </td>
              <td><span class="badge bg-label-info">{{ $stock->branch->name ?? 'Main Branch' }}</span></td>
              <td><span class="fw-bold">{{ $stock->quantity_on_hand }}</span></td>
              <td><span class="text-muted">{{ $stock->quantity_reserved }}</span></td>
              <td>
                <span class="fw-bold {{ $stock->quantity_available <= $stock->reorder_level ? 'text-danger' : 'text-success' }}">
                  {{ $stock->quantity_available }}
                </span>
              </td>
              <td>{{ $stock->reorder_level }}</td>
              <td>
                @if($stock->quantity_available <= 0)
                  <span class="badge bg-danger">Out of Stock</span>
                @elseif($stock->is_low_stock)
                  <span class="badge bg-warning">Low Stock</span>
                @else
                  <span class="badge bg-success">Healthy</span>
                @endif
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-primary"
                  onclick="openAdjustModal('{{ $stock->product_variant_id }}', '{{ $stock->tenant_branch_id }}', '{{ addslashes($stock->product->name ?? '') }} ({{ $stock->variant->sku ?? '' }})')">
                  <i class="bx bx-edit me-1"></i> Adjust
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-package fs-3 mb-2 d-block"></i>
                No inventory stock records found matching criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $stocks->links() }}
    </div>
  </div>
</div>

<!-- Quick Adjust Modal -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('inventory.adjust') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Adjust Physical Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Product Variant <span class="text-danger">*</span></label>
            <select name="product_variant_id" id="modal_variant_id" class="form-select" required>
              <option value="">Select Variant</option>
              @foreach($variants as $v)
                <option value="{{ $v->id }}">{{ $v->product->name ?? 'Product' }} ({{ $v->sku }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Branch / Warehouse <span class="text-danger">*</span></label>
            <select name="tenant_branch_id" id="modal_branch_id" class="form-select" required>
              @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <option value="stock_in">Stock In (+ Intake)</option>
                <option value="stock_out">Stock Out (- Shrinkage/Loss)</option>
                <option value="adjustment_positive">Correction (+ Count)</option>
                <option value="adjustment_negative">Correction (- Count)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Quantity Delta <span class="text-danger">*</span></label>
              <input type="number" name="delta" class="form-control" placeholder="e.g. 50 or -10" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Audit Note / Reason</label>
            <input type="text" name="note" class="form-control" placeholder="e.g. Supplier PO intake or Damaged stock write-off">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Apply Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAdjustModal(variantId, branchId, label) {
  const modalEl = document.getElementById('quickAdjustModal');
  const variantSelect = document.getElementById('modal_variant_id');
  const branchSelect = document.getElementById('modal_branch_id');
  
  if (variantSelect) variantSelect.value = variantId;
  if (branchSelect) branchSelect.value = branchId;
  
  const modal = new bootstrap.Modal(modalEl);
  modal.show();
}
</script>
@endsection

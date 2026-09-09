@extends('layouts/layoutMaster')

@section('title', 'Inventory Health & Stock Valuation - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Filters -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold py-1 mb-1">
        <span class="text-muted fw-light">Reports & Analytics /</span> Inventory Health & Valuation
      </h4>
      <p class="text-muted mb-0">Multi-branch stock availability, valuation analysis, and low stock warnings.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('inventory.index') }}" class="btn btn-outline-primary">
        <i class="bx bx-store-alt me-1"></i> Stock Management
      </a>
      <a href="{{ route('inventory.transactions') }}" class="btn btn-outline-secondary">
        <i class="bx bx-history me-1"></i> Audit Logs
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.inventory') }}" class="row g-3 align-items-end">
        <div class="col-md-5 col-sm-6">
          <label class="form-label fw-semibold">Branch Location</label>
          <select name="branch_id" class="form-select" onchange="this.form.submit()">
            <option value="">All Branches (Company Wide)</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                {{ $b->name }} ({{ $b->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 col-sm-6 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bx bx-filter-alt me-1"></i> Filter Branch
          </button>
          <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-4 mb-4">
    <!-- Physical Stock On Hand -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Total On Hand</span>
            <div class="avatar bg-label-primary rounded p-2">
              <i class="bx bx-box fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">{{ number_format($overview['total_on_hand']) }}</h3>
          <small class="text-muted">Total physical units in warehouse</small>
        </div>
      </div>
    </div>

    <!-- Available to Sell -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Available to Sell</span>
            <div class="avatar bg-label-success rounded p-2">
              <i class="bx bx-check-circle fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-success">{{ number_format($overview['total_available']) }}</h3>
          <small class="text-muted">
            Reserved for orders: <strong class="text-warning">{{ number_format($overview['total_reserved']) }}</strong>
          </small>
        </div>
      </div>
    </div>

    <!-- Estimated Valuation -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Inventory Valuation</span>
            <div class="avatar bg-label-info rounded p-2">
              <i class="bx bx-money fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['estimated_valuation'], 2) }}</h3>
          <small class="text-muted">Based on cost price / retail pricing</small>
        </div>
      </div>
    </div>

    <!-- Stock Alerts -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Stock Warnings</span>
            <div class="avatar bg-label-danger rounded p-2">
              <i class="bx bx-error-alt fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-danger">{{ $overview['low_stock_count'] + $overview['out_of_stock_count'] }}</h3>
          <div class="d-flex gap-2">
            <span class="badge bg-label-danger">{{ $overview['out_of_stock_count'] }} Out of Stock</span>
            <span class="badge bg-label-warning">{{ $overview['low_stock_count'] }} Low Stock</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SKU Coverage Bar -->
  <div class="card mb-4 bg-lighter border-0">
    <div class="card-body py-3">
      <div class="row text-center gy-2">
        <div class="col-sm-4">
          <div class="text-muted small">Managed Stock Records</div>
          <div class="fw-bold text-dark fs-5">{{ number_format($overview['total_sku_records']) }} SKUs</div>
        </div>
        <div class="col-sm-4 border-start border-end">
          <div class="text-muted small">Healthy Stock Percentage</div>
          @php
            $healthy = $overview['total_sku_records'] > 0 
              ? round((($overview['total_sku_records'] - ($overview['low_stock_count'] + $overview['out_of_stock_count'])) / $overview['total_sku_records']) * 100, 1) 
              : 100;
          @endphp
          <div class="fw-bold text-success fs-5">{{ $healthy }}%</div>
        </div>
        <div class="col-sm-4">
          <div class="text-muted small">Immediate Restock Required</div>
          <div class="fw-bold text-danger fs-5">{{ $overview['out_of_stock_count'] }} Items</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Critical Low Stock Alerts Table -->
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Critical Low Stock & Out-of-Stock SKUs</h5>
      <span class="badge bg-label-danger">Needs Attention</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>SKU</th>
            <th>Product Name</th>
            <th>Branch</th>
            <th class="text-center">On Hand</th>
            <th class="text-center">Reserved</th>
            <th class="text-center">Available</th>
            <th class="text-center">Reorder Threshold</th>
            <th class="text-center">Health Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($lowStockItems as $item)
            <tr>
              <td>
                <code class="text-primary fw-bold">{{ $item['sku'] }}</code>
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ $item['product_name'] }}</div>
              </td>
              <td>
                <span class="badge bg-label-secondary">{{ $item['branch_name'] }}</span>
              </td>
              <td class="text-center">{{ $item['on_hand'] }}</td>
              <td class="text-center text-muted">{{ $item['reserved'] }}</td>
              <td class="text-center fw-bold {{ $item['available'] <= 0 ? 'text-danger' : 'text-warning' }}">
                {{ $item['available'] }}
              </td>
              <td class="text-center text-muted">{{ $item['reorder_level'] }}</td>
              <td class="text-center">
                @if($item['is_out_of_stock'])
                  <span class="badge bg-label-danger">Out of Stock</span>
                @else
                  <span class="badge bg-label-warning">Low Stock</span>
                @endif
              </td>
              <td class="text-center">
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-plus-circle me-1"></i> Restock
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                <i class="bx bx-check-circle text-success fs-3 d-block mb-1"></i> All stock levels are within healthy reorder thresholds!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

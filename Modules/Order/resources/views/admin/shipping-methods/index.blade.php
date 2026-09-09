@extends('layouts/layoutMaster')

@section('title', 'Shipping Carriers & Methods - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Orders & Sales /</span> Shipping Carriers</h4>
      <small class="text-muted">Configure delivery carriers, shipping rates, and delivery timeframes</small>
    </div>
    <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Add Shipping Method
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Total Methods</span>
              <h4 class="mb-0 my-1">{{ number_format($totalMethods) }}</h4>
              <small class="text-muted">Registered delivery options</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-trip fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Active Methods</span>
              <h4 class="mb-0 my-1 text-success">{{ number_format($activeMethods) }}</h4>
              <small class="text-muted">Available on checkout</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-circle fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Carriers</span>
              <h4 class="mb-0 my-1 text-info">{{ number_format($uniqueCarriers) }}</h4>
              <small class="text-muted">Supported logistics partners</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-package fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Fastest Delivery</span>
              <h4 class="mb-0 my-1 text-warning">{{ $methods->min('min_days') ?? 1 }} Day(s)</h4>
              <small class="text-muted">Express turnaround time</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-time-five fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters & Table Card -->
  <div class="card">
    <div class="card-header border-bottom">
      <form method="GET" action="{{ route('admin.shipping-methods.index') }}" class="row g-3 align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search method, code, carrier..." value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="carrier" class="form-select">
            <option value="">All Carriers</option>
            @foreach($carriersList as $carrier)
              <option value="{{ $carrier }}" {{ request('carrier') === $carrier ? 'selected' : '' }}>{{ $carrier }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="rate_type" class="form-select">
            <option value="">All Rate Types</option>
            <option value="flat" {{ request('rate_type') === 'flat' ? 'selected' : '' }}>Flat Rate</option>
            <option value="tiered_weight" {{ request('rate_type') === 'tiered_weight' ? 'selected' : '' }}>Tiered Weight</option>
            <option value="tiered_total" {{ request('rate_type') === 'tiered_total' ? 'selected' : '' }}>Tiered Order Total</option>
            <option value="free" {{ request('rate_type') === 'free' ? 'selected' : '' }}>Free</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
          </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm px-3" title="Filter"><i class="bx bx-filter-alt"></i></button>
          <a href="{{ route('admin.shipping-methods.index') }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset"><i class="bx bx-reset"></i></a>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Carrier & Method</th>
            <th>Code</th>
            <th>Rate Type</th>
            <th>Base Rate</th>
            <th>Delivery Window</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($methods as $method)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded bg-label-secondary">
                      <i class="bx bx-bus"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $method->name }}</span>
                    <br>
                    <small class="text-muted"><i class="bx bx-buildings me-1"></i>{{ $method->carrier }}</small>
                  </div>
                </div>
              </td>
              <td><code>{{ $method->code }}</code></td>
              <td>
                @if($method->rate_type === 'flat')
                  <span class="badge bg-label-info">Flat Rate</span>
                @elseif($method->rate_type === 'tiered_weight')
                  <span class="badge bg-label-warning">Tiered (Weight)</span>
                @elseif($method->rate_type === 'tiered_total')
                  <span class="badge bg-label-primary">Tiered (Total)</span>
                @else
                  <span class="badge bg-label-success">Free Shipping</span>
                @endif
              </td>
              <td>
                <strong>${{ number_format($method->base_rate, 2) }}</strong>
                @if($method->free_shipping_threshold)
                  <br><small class="text-muted">Free on ${{ number_format($method->free_shipping_threshold, 2) }}+</small>
                @endif
              </td>
              <td>
                <i class="bx bx-calendar me-1 text-muted"></i>{{ $method->estimated_delivery_text }}
              </td>
              <td>
                <form action="{{ route('admin.shipping-methods.toggle', $method->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-xs {{ $method->is_active ? 'btn-label-success' : 'btn-label-secondary' }}" title="Click to toggle status">
                    {{ $method->is_active ? 'Active' : 'Disabled' }}
                  </button>
                </form>
              </td>
              <td class="text-end">
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('admin.shipping-methods.edit', $method->id) }}">
                      <i class="bx bx-edit-alt me-1"></i> Edit Method
                    </a>
                    <form action="{{ route('admin.shipping-methods.destroy', $method->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this shipping method?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="bx bx-trash me-1"></i> Delete
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">
                <div class="text-muted">
                  <i class="bx bx-package fs-1 mb-2"></i>
                  <p class="mb-0">No shipping methods configured.</p>
                  <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-sm btn-primary mt-2">
                    <i class="bx bx-plus me-1"></i> Create One Now
                  </a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($methods->hasPages())
      <div class="card-footer border-top d-flex justify-content-end">
        {{ $methods->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

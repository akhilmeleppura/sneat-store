@extends('layouts/layoutMaster')

@section('title', 'Multi-Tenancy & Store Hub - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- Header Banner -->
  <div class="card mb-4 bg-primary text-white overflow-hidden shadow-sm">
    <div class="card-body p-4 position-relative">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-white text-primary fw-bold text-uppercase px-2 py-1">Enterprise Core</span>
            <span class="badge bg-label-secondary text-white border border-white">Multi-Tenancy & Hierarchy Hub</span>
          </div>
          <h3 class="text-white fw-bold mb-1">Store & Tenant Management Hub</h3>
          <p class="text-white-50 mb-0">
            Control organizational tenants, virtual storefronts, and physical retail branches from a single unified console.
          </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <button type="button" class="btn btn-white text-primary shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addTenantModal">
            <i class="bx bx-plus me-1"></i> New Tenant
          </button>
          <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addStoreModal">
            <i class="bx bx-store me-1"></i> New Store
          </button>
          <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="bx bx-git-branch me-1"></i> New Branch
          </button>
          <a href="{{ route('admin.tenant.settings') }}" class="btn btn-light btn-icon" title="Tenant Settings">
            <i class="bx bx-slider"></i>
          </a>
        </div>
      </div>
      <!-- Background Graphic -->
      <div class="position-absolute end-0 top-0 bottom-0 d-none d-lg-block opacity-25 pe-4 pointer-events-none" style="z-index: 1;">
        <i class="bx bx-buildings" style="font-size: 11rem; line-height: 1;"></i>
      </div>
    </div>
  </div>

  <!-- Active Context Quick Switcher Strip -->
  <div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
      <form action="{{ route('admin.context.switch') }}" method="POST" class="row g-3 align-items-center">
        @csrf
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
          <span class="badge bg-label-primary p-2"><i class="bx bx-target-lock fs-5"></i></span>
          <div>
            <div class="text-muted small">Current Active Scope:</div>
            <div class="fw-bold text-dark">
              {{ $currentTenant ? $currentTenant->name : 'Global / Root' }}
              @if($currentStore) &rsaquo; {{ $currentStore->name }} @endif
              @if($currentBranch) &rsaquo; {{ $currentBranch->name }} @endif
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3 ms-md-auto">
          <label class="form-label visually-hidden">Switch Tenant</label>
          <select name="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="" disabled>-- Switch Tenant Scope --</option>
            @foreach($tenants as $t)
              <option value="{{ $t->id }}" {{ ($currentTenant && $currentTenant->id === $t->id) ? 'selected' : '' }}>
                Tenant: {{ $t->name }} ({{ $t->slug }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label visually-hidden">Switch Store</label>
          <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Default Store</option>
            @foreach($stores as $st)
              @if(!$currentTenant || $st->tenant_id === $currentTenant->id)
                <option value="{{ $st->id }}" {{ ($currentStore && $currentStore->id === $st->id) ? 'selected' : '' }}>
                  Store: {{ $st->name }} ({{ $st->slug }})
                </option>
              @endif
            @endforeach
          </select>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label visually-hidden">Switch Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Default Branch</option>
            @foreach($branches as $br)
              @if(!$currentStore || $br->store_id === $currentStore->id)
                <option value="{{ $br->id }}" {{ ($currentBranch && $currentBranch->id === $br->id) ? 'selected' : '' }}>
                  Branch: {{ $br->name }} ({{ $br->code ?? $br->slug }})
                </option>
              @endif
            @endforeach
          </select>
        </div>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible shadow-sm d-flex align-items-center mb-4" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>
      <div class="flex-grow-1">{{ session('success') }}</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible shadow-sm mb-4" role="alert">
      <div class="d-flex align-items-center mb-1">
        <i class="bx bx-error-circle fs-4 me-2"></i>
        <strong>Please correct the following errors:</strong>
      </div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Cards -->
  <div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="avatar avatar-md me-3 bg-label-primary rounded p-2">
            <i class="bx bx-buildings fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Total Tenants</div>
            <h4 class="mb-0 fw-bold">{{ $stats['total_tenants'] }}</h4>
            <small class="text-success">{{ $stats['active_tenants'] }} Active</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="avatar avatar-md me-3 bg-label-success rounded p-2">
            <i class="bx bx-store fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Active Stores</div>
            <h4 class="mb-0 fw-bold">{{ $stats['total_stores'] }}</h4>
            <small class="text-muted">{{ $stats['active_stores'] }} Online</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="avatar avatar-md me-3 bg-label-info rounded p-2">
            <i class="bx bx-git-branch fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Retail Branches</div>
            <h4 class="mb-0 fw-bold">{{ $stats['total_branches'] }}</h4>
            <small class="text-info">{{ $stats['active_branches'] }} Active</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="avatar avatar-md me-3 bg-label-warning rounded p-2">
            <i class="bx bx-money fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">FX Currencies</div>
            <h4 class="mb-0 fw-bold">{{ $stats['active_currencies'] }}</h4>
            <a href="{{ route('admin.currencies.index') }}" class="small text-primary text-decoration-none">Manage Rates &rsaquo;</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Navigation Tabs -->
  @php
    $activeTab = request()->query('tab', 'overview');
  @endphp
  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
      <li class="nav-item">
        <button type="button" class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#nav-overview" aria-controls="nav-overview" aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}">
          <i class="bx bx-grid-alt me-1"></i> Hierarchy Overview
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link {{ $activeTab === 'tenants' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#nav-tenants" aria-controls="nav-tenants" aria-selected="{{ $activeTab === 'tenants' ? 'true' : 'false' }}">
          <i class="bx bx-buildings me-1"></i> Tenants ({{ $tenants->count() }})
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link {{ $activeTab === 'stores' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#nav-stores" aria-controls="nav-stores" aria-selected="{{ $activeTab === 'stores' ? 'true' : 'false' }}">
          <i class="bx bx-store me-1"></i> Stores ({{ $stores->count() }})
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link {{ $activeTab === 'branches' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#nav-branches" aria-controls="nav-branches" aria-selected="{{ $activeTab === 'branches' ? 'true' : 'false' }}">
          <i class="bx bx-git-branch me-1"></i> Branches ({{ $branches->count() }})
        </button>
      </li>
    </ul>

    <div class="tab-content bg-transparent p-0 border-0 shadow-none">

      <!-- ========================================== -->
      <!-- TAB 1: HIERARCHY OVERVIEW                  -->
      <!-- ========================================== -->
      <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="nav-overview" role="tabpanel">
        <div class="row g-4">
          @forelse($tenants as $t)
            <div class="col-12 col-lg-6">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-label-primary py-3 d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-buildings fs-4 text-primary"></i>
                    <div>
                      <h5 class="mb-0 fw-bold">{{ $t->name }}</h5>
                      <span class="badge bg-white text-dark small">Slug: <code>{{ $t->slug }}</code></span>
                      @if($t->domain)
                        <span class="badge bg-white text-muted small"><i class="bx bx-globe me-1"></i>{{ $t->domain }}</span>
                      @endif
                    </div>
                  </div>
                  <span class="badge {{ $t->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                    {{ ucfirst($t->status) }}
                  </span>
                </div>
                <div class="card-body pt-3">
                  <div class="mb-3 d-flex flex-wrap gap-2 text-muted small">
                    <span><i class="bx bx-time me-1"></i>{{ $t->timezone ?? 'UTC' }}</span>
                    <span>&bull;</span>
                    <span><i class="bx bx-dollar me-1"></i>{{ $t->currency ?? 'USD' }}</span>
                    <span>&bull;</span>
                    <span><i class="bx bx-conversation me-1"></i>{{ $t->locale ?? 'en' }}</span>
                  </div>

                  <h6 class="fw-bold text-muted small text-uppercase mb-2">Stores & Branches</h6>
                  @if($t->stores->isEmpty())
                    <p class="text-muted fst-italic small">No stores configured for this tenant yet.</p>
                  @else
                    <div class="list-group list-group-flush border rounded">
                      @foreach($t->stores as $st)
                        <div class="list-group-item p-3">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-semibold">
                              <i class="bx bx-store text-success me-1"></i> {{ $st->name }}
                              @if($st->is_default)
                                <span class="badge bg-label-primary ms-1 small">Default</span>
                              @endif
                            </div>
                            <span class="badge {{ $st->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }} small">
                              {{ ucfirst($st->status) }}
                            </span>
                          </div>
                          <div class="text-muted small mb-2">
                            Slug: <code>{{ $st->slug }}</code>
                            @if($st->domain) &bull; Domain: <code>{{ $st->domain }}</code> @endif
                          </div>

                          <!-- Nested Branches -->
                          @if($st->branches->isNotEmpty())
                            <div class="ps-3 border-start ms-2 mt-2">
                              <div class="text-muted small fw-semibold mb-1">Branches:</div>
                              <div class="d-flex flex-wrap gap-2">
                                @foreach($st->branches as $br)
                                  <span class="badge bg-label-info d-flex align-items-center gap-1">
                                    <i class="bx bx-map-pin"></i> {{ $br->name }} ({{ $br->code ?? $br->slug }})
                                    @if($br->is_default) <i class="bx bxs-star text-warning" title="Default Branch"></i> @endif
                                  </span>
                                @endforeach
                              </div>
                            </div>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @endif
                </div>
                <div class="card-footer bg-light py-2 d-flex justify-content-between align-items-center">
                  <form action="{{ route('admin.context.switch') }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $t->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                      <i class="bx bx-check-circle me-1"></i> Select As Scope
                    </button>
                  </form>
                  <a href="{{ route('admin.tenant.settings') }}" class="btn btn-sm btn-link text-muted">
                    Configure Settings &rsaquo;
                  </a>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12">
              <div class="card border-0 shadow-sm text-center py-5">
                <i class="bx bx-buildings text-muted mb-2" style="font-size: 3rem;"></i>
                <h5>No Tenants Found</h5>
                <p class="text-muted">Get started by creating your first multi-tenant account.</p>
                <div>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                    <i class="bx bx-plus me-1"></i> Create Tenant
                  </button>
                </div>
              </div>
            </div>
          @endforelse
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 2: TENANTS TABLE                       -->
      <!-- ========================================== -->
      <div class="tab-pane fade {{ $activeTab === 'tenants' ? 'show active' : '' }}" id="nav-tenants" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0 fw-bold">All Tenants</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTenantModal">
              <i class="bx bx-plus me-1"></i> Add Tenant
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Tenant Name</th>
                  <th>Slug / Identifier</th>
                  <th>Custom Domain</th>
                  <th>Timezone / Currency</th>
                  <th>Stores</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody class="table-border-bottom-0">
                @forelse($tenants as $t)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-label-primary rounded">
                          <i class="bx bx-buildings"></i>
                        </div>
                        <div>
                          <strong>{{ $t->name }}</strong>
                          @if($currentTenant && $currentTenant->id === $t->id)
                            <span class="badge bg-label-primary ms-1 small">Current Scope</span>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td><code>{{ $t->slug }}</code></td>
                    <td>
                      @if($t->domain)
                        <a href="http://{{ $t->domain }}" target="_blank" class="text-muted">
                          <i class="bx bx-link-external me-1"></i>{{ $t->domain }}
                        </a>
                      @else
                        <span class="text-muted fst-italic">None</span>
                      @endif
                    </td>
                    <td>
                      <small class="d-block text-dark">{{ $t->timezone ?? 'UTC' }}</small>
                      <small class="text-muted">{{ $t->currency ?? 'USD' }} ({{ $t->locale ?? 'en' }})</small>
                    </td>
                    <td>
                      <span class="badge bg-label-info">{{ $t->stores->count() }} Stores</span>
                    </td>
                    <td>
                      <span class="badge {{ $t->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                        {{ ucfirst($t->status) }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-tenant-btn"
                          data-id="{{ $t->id }}"
                          data-name="{{ $t->name }}"
                          data-slug="{{ $t->slug }}"
                          data-domain="{{ $t->domain }}"
                          data-status="{{ $t->status }}"
                          data-timezone="{{ $t->timezone }}"
                          data-currency="{{ $t->currency }}"
                          data-locale="{{ $t->locale }}"
                          data-bs-toggle="modal" data-bs-target="#editTenantModal">
                          <i class="bx bx-pencil"></i>
                        </button>

                        <form action="{{ route('admin.context.tenants.delete', $t->id) }}" method="POST" onsubmit="return confirm('Delete this tenant? Related stores will be inaccessible.');" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No tenants created yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 3: STORES TABLE                        -->
      <!-- ========================================== -->
      <div class="tab-pane fade {{ $activeTab === 'stores' ? 'show active' : '' }}" id="nav-stores" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0 fw-bold">All Stores</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStoreModal">
              <i class="bx bx-plus me-1"></i> Add Store
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Store Name</th>
                  <th>Tenant</th>
                  <th>Slug</th>
                  <th>Custom Domain</th>
                  <th>Branches</th>
                  <th>Default?</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody class="table-border-bottom-0">
                @forelse($stores as $st)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-label-success rounded">
                          <i class="bx bx-store"></i>
                        </div>
                        <div>
                          <strong>{{ $st->name }}</strong>
                          @if($currentStore && $currentStore->id === $st->id)
                            <span class="badge bg-label-primary ms-1 small">Current</span>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-label-secondary">{{ $st->tenant ? $st->tenant->name : 'N/A' }}</span>
                    </td>
                    <td><code>{{ $st->slug }}</code></td>
                    <td>
                      @if($st->domain)
                        <code>{{ $st->domain }}</code>
                      @else
                        <span class="text-muted fst-italic">Inherited</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge bg-label-info">{{ $st->branches->count() }}</span>
                    </td>
                    <td>
                      @if($st->is_default)
                        <span class="badge bg-primary">Default</span>
                      @else
                        <span class="text-muted small">Standard</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $st->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                        {{ ucfirst($st->status) }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-store-btn"
                          data-id="{{ $st->id }}"
                          data-tenant-id="{{ $st->tenant_id }}"
                          data-name="{{ $st->name }}"
                          data-slug="{{ $st->slug }}"
                          data-domain="{{ $st->domain }}"
                          data-is-default="{{ $st->is_default ? 1 : 0 }}"
                          data-status="{{ $st->status }}"
                          data-bs-toggle="modal" data-bs-target="#editStoreModal">
                          <i class="bx bx-pencil"></i>
                        </button>

                        <form action="{{ route('admin.context.stores.delete', $st->id) }}" method="POST" onsubmit="return confirm('Delete this store?');" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No stores created yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 4: BRANCHES TABLE                      -->
      <!-- ========================================== -->
      <div class="tab-pane fade {{ $activeTab === 'branches' ? 'show active' : '' }}" id="nav-branches" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0 fw-bold">All Retail Branches</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBranchModal">
              <i class="bx bx-plus me-1"></i> Add Branch
            </button>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Branch Name</th>
                  <th>Code</th>
                  <th>Store / Tenant</th>
                  <th>Location</th>
                  <th>Contact</th>
                  <th>Default?</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody class="table-border-bottom-0">
                @forelse($branches as $br)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-label-info rounded">
                          <i class="bx bx-git-branch"></i>
                        </div>
                        <div>
                          <strong>{{ $br->name }}</strong>
                          @if($currentBranch && $currentBranch->id === $br->id)
                            <span class="badge bg-label-primary ms-1 small">Current</span>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td><code>{{ $br->code ?? 'N/A' }}</code></td>
                    <td>
                      <small class="d-block text-dark">{{ $br->store ? $br->store->name : 'N/A' }}</small>
                      <small class="text-muted">{{ $br->tenant ? $br->tenant->name : 'N/A' }}</small>
                    </td>
                    <td>
                      @if($br->city || $br->country)
                        <span>{{ $br->city }}{{ $br->city && $br->country ? ', ' : '' }}{{ $br->country }}</span>
                      @else
                        <span class="text-muted fst-italic">No address</span>
                      @endif
                    </td>
                    <td>
                      <small class="d-block">{{ $br->phone ?? '-' }}</small>
                      <small class="text-muted">{{ $br->email ?? '-' }}</small>
                    </td>
                    <td>
                      @if($br->is_default)
                        <span class="badge bg-primary">Default</span>
                      @else
                        <span class="text-muted small">Standard</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $br->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                        {{ ucfirst($br->status) }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-branch-btn"
                          data-id="{{ $br->id }}"
                          data-name="{{ $br->name }}"
                          data-slug="{{ $br->slug }}"
                          data-code="{{ $br->code }}"
                          data-address="{{ $br->address }}"
                          data-city="{{ $br->city }}"
                          data-state="{{ $br->state }}"
                          data-country="{{ $br->country }}"
                          data-postal-code="{{ $br->postal_code }}"
                          data-phone="{{ $br->phone }}"
                          data-email="{{ $br->email }}"
                          data-is-default="{{ $br->is_default ? 1 : 0 }}"
                          data-status="{{ $br->status }}"
                          data-bs-toggle="modal" data-bs-target="#editBranchModal">
                          <i class="bx bx-pencil"></i>
                        </button>

                        <form action="{{ route('admin.context.branches.delete', $br->id) }}" method="POST" onsubmit="return confirm('Delete this branch?');" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No branches created yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- MODALS: ADD & EDIT TENANT                               -->
<!-- ======================================================= -->
<div class="modal fade" id="addTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('admin.context.tenants.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-buildings me-1 text-primary"></i> Create New Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tenant Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Apex Global Corp">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Slug (Optional)</label>
              <input type="text" name="slug" class="form-control" placeholder="apex-global">
            </div>
            <div class="col-6">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="active" selected>Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Custom Domain (Optional)</label>
            <input type="text" name="domain" class="form-control" placeholder="store.apex.com">
          </div>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label">Timezone</label>
              <input type="text" name="timezone" class="form-control" value="UTC">
            </div>
            <div class="col-4">
              <label class="form-label">Currency</label>
              <input type="text" name="currency" class="form-control" value="USD">
            </div>
            <div class="col-4">
              <label class="form-label">Locale</label>
              <input type="text" name="locale" class="form-control" value="en">
            </div>
          </div>
          <div class="alert alert-info small mt-3 mb-0">
            <i class="bx bx-info-circle me-1"></i> A default main Store and initial HQ Branch will be automatically provisioned for this tenant.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Tenant</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editTenantForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-edit me-1 text-primary"></i> Edit Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tenant Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit_tenant_name" class="form-control" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Slug <span class="text-danger">*</span></label>
              <input type="text" name="slug" id="edit_tenant_slug" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" id="edit_tenant_status" class="form-select" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Custom Domain</label>
            <input type="text" name="domain" id="edit_tenant_domain" class="form-control">
          </div>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label">Timezone</label>
              <input type="text" name="timezone" id="edit_tenant_timezone" class="form-control">
            </div>
            <div class="col-4">
              <label class="form-label">Currency</label>
              <input type="text" name="currency" id="edit_tenant_currency" class="form-control">
            </div>
            <div class="col-4">
              <label class="form-label">Locale</label>
              <input type="text" name="locale" id="edit_tenant_locale" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- MODALS: ADD & EDIT STORE                                -->
<!-- ======================================================= -->
<div class="modal fade" id="addStoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('admin.context.stores.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-store me-1 text-success"></i> Create New Store</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Parent Tenant <span class="text-danger">*</span></label>
            <select name="tenant_id" class="form-select" required>
              @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ ($currentTenant && $currentTenant->id === $t->id) ? 'selected' : '' }}>
                  {{ $t->name }} ({{ $t->slug }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Store Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Apex Electronics Flagship">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control" placeholder="flagship">
            </div>
            <div class="col-6">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="active" selected>Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Custom Domain (Optional)</label>
            <input type="text" name="domain" class="form-control" placeholder="electronics.apex.com">
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="store_is_default">
            <label class="form-check-label" for="store_is_default">Set as default store for this tenant</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Store</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editStoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editStoreForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-edit me-1 text-success"></i> Edit Store</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Store Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit_store_name" class="form-control" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Slug <span class="text-danger">*</span></label>
              <input type="text" name="slug" id="edit_store_slug" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" id="edit_store_status" class="form-select" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Custom Domain</label>
            <input type="text" name="domain" id="edit_store_domain" class="form-control">
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="edit_store_is_default">
            <label class="form-check-label" for="edit_store_is_default">Set as default store for this tenant</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- MODALS: ADD & EDIT BRANCH                               -->
<!-- ======================================================= -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="{{ route('admin.context.branches.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-git-branch me-1 text-info"></i> Create Retail Branch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Store Association <span class="text-danger">*</span></label>
              <select name="store_id" class="form-select" required>
                @foreach($stores as $st)
                  <option value="{{ $st->id }}" {{ ($currentStore && $currentStore->id === $st->id) ? 'selected' : '' }}>
                    {{ $st->name }} (Tenant: {{ $st->tenant ? $st->tenant->name : 'N/A' }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Branch Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Downtown Flagship">
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Branch Code</label>
              <input type="text" name="code" class="form-control" placeholder="e.g. BR-DT-01">
            </div>
            <div class="col-md-4">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control" placeholder="downtown">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="active" selected>Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Street Address</label>
            <input type="text" name="address" class="form-control" placeholder="100 Broadway Avenue">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" placeholder="New York">
            </div>
            <div class="col-md-3">
              <label class="form-label">State/Province</label>
              <input type="text" name="state" class="form-control" placeholder="NY">
            </div>
            <div class="col-md-3">
              <label class="form-label">Country</label>
              <input type="text" name="country" class="form-control" placeholder="USA">
            </div>
            <div class="col-md-3">
              <label class="form-label">Postal Code</label>
              <input type="text" name="postal_code" class="form-control" placeholder="10001">
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="+1 (555) 012-3456">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="branch@example.com">
            </div>
          </div>
          <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="branch_is_default">
            <label class="form-check-label" for="branch_is_default">Set as default branch for this store</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Branch</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editBranchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="editBranchForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-edit me-1 text-info"></i> Edit Retail Branch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Branch Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_branch_name" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Branch Code</label>
              <input type="text" name="code" id="edit_branch_code" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" id="edit_branch_status" class="form-select" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Slug <span class="text-danger">*</span></label>
            <input type="text" name="slug" id="edit_branch_slug" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Street Address</label>
            <input type="text" name="address" id="edit_branch_address" class="form-control">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label">City</label>
              <input type="text" name="city" id="edit_branch_city" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">State/Province</label>
              <input type="text" name="state" id="edit_branch_state" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Country</label>
              <input type="text" name="country" id="edit_branch_country" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Postal Code</label>
              <input type="text" name="postal_code" id="edit_branch_postal_code" class="form-control">
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" id="edit_branch_phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="edit_branch_email" class="form-control">
            </div>
          </div>
          <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="edit_branch_is_default">
            <label class="form-check-label" for="edit_branch_is_default">Set as default branch for this store</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Tenant Edit Population
  document.querySelectorAll('.edit-tenant-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const form = document.getElementById('editTenantForm');
      form.action = '{{ url("admin/context/tenants") }}/' + id;
      document.getElementById('edit_tenant_name').value = this.dataset.name || '';
      document.getElementById('edit_tenant_slug').value = this.dataset.slug || '';
      document.getElementById('edit_tenant_domain').value = this.dataset.domain || '';
      document.getElementById('edit_tenant_status').value = this.dataset.status || 'active';
      document.getElementById('edit_tenant_timezone').value = this.dataset.timezone || 'UTC';
      document.getElementById('edit_tenant_currency').value = this.dataset.currency || 'USD';
      document.getElementById('edit_tenant_locale').value = this.dataset.locale || 'en';
    });
  });

  // Store Edit Population
  document.querySelectorAll('.edit-store-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const form = document.getElementById('editStoreForm');
      form.action = '{{ url("admin/context/stores") }}/' + id;
      document.getElementById('edit_store_name').value = this.dataset.name || '';
      document.getElementById('edit_store_slug').value = this.dataset.slug || '';
      document.getElementById('edit_store_domain').value = this.dataset.domain || '';
      document.getElementById('edit_store_status').value = this.dataset.status || 'active';
      document.getElementById('edit_store_is_default').checked = this.dataset.isDefault == '1';
    });
  });

  // Branch Edit Population
  document.querySelectorAll('.edit-branch-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const form = document.getElementById('editBranchForm');
      form.action = '{{ url("admin/context/branches") }}/' + id;
      document.getElementById('edit_branch_name').value = this.dataset.name || '';
      document.getElementById('edit_branch_slug').value = this.dataset.slug || '';
      document.getElementById('edit_branch_code').value = this.dataset.code || '';
      document.getElementById('edit_branch_address').value = this.dataset.address || '';
      document.getElementById('edit_branch_city').value = this.dataset.city || '';
      document.getElementById('edit_branch_state').value = this.dataset.state || '';
      document.getElementById('edit_branch_country').value = this.dataset.country || '';
      document.getElementById('edit_branch_postal_code').value = this.dataset.postalCode || '';
      document.getElementById('edit_branch_phone').value = this.dataset.phone || '';
      document.getElementById('edit_branch_email').value = this.dataset.email || '';
      document.getElementById('edit_branch_status').value = this.dataset.status || 'active';
      document.getElementById('edit_branch_is_default').checked = this.dataset.isDefault == '1';
    });
  });
});
</script>
@endsection

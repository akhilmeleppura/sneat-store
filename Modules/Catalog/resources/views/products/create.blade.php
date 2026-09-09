@extends('layouts/layoutMaster')

@section('title', 'Create Product - Catalog')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog / Products /</span> Add New</h4>
    <a href="{{ route('catalog.products.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Products
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <form action="{{ route('catalog.products.store') }}" method="POST">
    @csrf
    <div class="row">
      <!-- Left Column: Main Info -->
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">General Information</h5></div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Product Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Wireless Noise Canceling Headphones" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Short Description</label>
              <textarea name="short_description" class="form-control" rows="2" placeholder="Brief summary of the product...">{{ old('short_description') }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Full Description</label>
              <textarea name="description" class="form-control" rows="5" placeholder="Detailed product specifications and features...">{{ old('description') }}</textarea>
            </div>
          </div>
        </div>

        <!-- Pricing Card -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Pricing & Inventory</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Base Selling Price ($) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" value="{{ old('price') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Compare At Price ($)</label>
                <input type="number" step="0.01" name="compare_at_price" class="form-control" placeholder="0.00" value="{{ old('compare_at_price') }}">
                <small class="text-muted">Original price before discount</small>
              </div>
              <div class="col-md-4">
                <label class="form-label">Cost Price ($)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control" placeholder="0.00" value="{{ old('cost_price') }}">
                <small class="text-muted">Internal purchase cost</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">SKU (Stock Keeping Unit)</label>
                <input type="text" name="sku" class="form-control" placeholder="e.g. PROD-001" value="{{ old('sku') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Initial Physical Stock (Branch Intake)</label>
                <input type="number" name="initial_stock" class="form-control" placeholder="0" value="{{ old('initial_stock', 0) }}">
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-Store Channels Card -->
        @if(isset($stores) && $stores->isNotEmpty())
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
            <div>
              <h5 class="card-title mb-0">Multi-Store Channels & Price Overrides</h5>
              <small class="text-muted">Assign product visibility and custom retail prices per store</small>
            </div>
            <span class="badge bg-label-primary"><i class="bx bx-store me-1"></i> Multi-Store</span>
          </div>
          <div class="card-body pt-3">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;">Publish</th>
                    <th>Store Name</th>
                    <th>Slug / Domain</th>
                    <th style="width: 200px;">Store Price Override ($)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($stores as $st)
                    <tr>
                      <td>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="store_{{ $st->id }}" checked>
                        </div>
                      </td>
                      <td>
                        <label class="form-check-label fw-semibold cursor-pointer" for="store_{{ $st->id }}">
                          {{ $st->name }}
                        </label>
                        @if($st->is_default)
                          <span class="badge bg-label-info ms-1" style="font-size: 10px;">Default Store</span>
                        @endif
                      </td>
                      <td>
                        <code>{{ $st->slug }}</code>
                      </td>
                      <td>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">$</span>
                          <input type="number" step="0.01" class="form-control" name="store_prices[{{ $st->id }}]" value="{{ old('store_prices.' . $st->id) }}" placeholder="Inherit base">
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <small class="text-muted mt-2 d-block">
              <i class="bx bx-info-circle me-1"></i> Checked stores will have access to sell this product. Leave price blank to use Base Selling Price.
            </small>
          </div>
        </div>
        @endif
      </div>

      <!-- Right Column: Taxonomy & Status -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Publishing Details</h5></div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Product Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <option value="simple" {{ old('type', 'simple') === 'simple' ? 'selected' : '' }}>Simple Product</option>
                <option value="variable" {{ old('type') === 'variable' ? 'selected' : '' }}>Variable (Multiple Variants)</option>
                <option value="digital" {{ old('type') === 'digital' ? 'selected' : '' }}>Digital / Downloadable</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select">
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">Select Brand</option>
                @foreach($brands as $b)
                  <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
              </select>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-save me-1"></i> Save Product</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

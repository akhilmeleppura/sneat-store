@extends('layouts/layoutMaster')

@section('title', 'Edit Product - Catalog')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0">
        <span class="text-muted fw-light">Catalog / Products /</span> Edit: {{ $product->name }}
      </h4>
      <small class="text-muted">Manage product specifications, multi-store channels, and pricing rules.</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('catalog.products.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to Products
      </a>
      <a href="{{ route('storefront.product.show', $product->slug) }}" target="_blank" class="btn btn-outline-primary">
        <i class="bx bx-show me-1"></i> View in Storefront
      </a>
    </div>
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

  <form action="{{ route('catalog.products.update', $product->id) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="sync_stores" value="1">

    <div class="row">
      <!-- Left Column: Main Info -->
      <div class="col-lg-8">
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0">General Information</h5>
          </div>
          <div class="card-body pt-3">
            <div class="mb-3">
              <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Short Description</label>
              <textarea name="short_description" class="form-control" rows="2">{{ old('short_description', $product->short_description) }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Full Description</label>
              <textarea name="description" class="form-control" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>
          </div>
        </div>

        <!-- Pricing Card -->
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0">Base Pricing & Inventory Details</h5>
          </div>
          <div class="card-body pt-3">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Base Selling Price ($) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Compare At Price ($)</label>
                <input type="number" step="0.01" name="compare_at_price" class="form-control" value="{{ old('compare_at_price', $product->compare_at_price) }}">
                <small class="text-muted">Original price before discount</small>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Cost Price ($)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}">
                <small class="text-muted">Internal purchase cost</small>
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">SKU (Stock Keeping Unit)</label>
                <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
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
                  @php
                    $assignedStores = $product->stores->keyBy('id');
                  @endphp
                  @foreach($stores as $st)
                    @php
                      $isAssigned = $assignedStores->has($st->id);
                      $overridePrice = $isAssigned ? $assignedStores[$st->id]->pivot->price_override : null;
                    @endphp
                    <tr>
                      <td>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="store_{{ $st->id }}" {{ $isAssigned ? 'checked' : '' }}>
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
                          <input type="number" step="0.01" class="form-control" name="store_prices[{{ $st->id }}]" value="{{ old('store_prices.' . $st->id, $overridePrice) }}" placeholder="Base: {{ number_format($product->price, 2) }}">
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <small class="text-muted mt-2 d-block">
              <i class="bx bx-info-circle me-1"></i> Leave Store Price Override blank to inherit the global Base Selling Price ($).
            </small>
          </div>
        </div>
        @endif
      </div>

      <!-- Right Column: Taxonomy & Publishing -->
      <div class="col-lg-4">
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0">Publishing Details</h5>
          </div>
          <div class="card-body pt-3">
            <div class="mb-3">
              <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="published" {{ old('status', $product->status) === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="archived" {{ old('status', $product->status) === 'archived' ? 'selected' : '' }}>Archived</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Category</label>
              <select name="category_id" class="form-select">
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">Select Brand</option>
                @foreach($brands as $b)
                  <option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
              </select>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bx bx-save me-1"></i> Update Product
            </button>
          </div>
        </div>

        <!-- Inventory Summary Card -->
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="card-title mb-0">Inventory Stock Level</h5>
          </div>
          <div class="card-body pt-3">
            @php
              $totalStock = $product->variants->sum(fn($v) => $v->total_available_stock);
            @endphp
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted">Total Available Units:</span>
              <span class="badge {{ $totalStock > 5 ? 'bg-label-success' : ($totalStock > 0 ? 'bg-label-warning' : 'bg-label-danger') }} fs-6">
                {{ $totalStock }} In Stock
              </span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted">Variant Count:</span>
              <span class="fw-semibold">{{ $product->variants->count() }} variant(s)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Add Product - Vendor Portal')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Vendor / Products /</span> Add New Product</h4>
      <small class="text-muted">List a new product in the marketplace catalog under your vendor brand.</small>
    </div>
    <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary">
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

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <h5 class="card-header">Product Information</h5>
        <div class="card-body">
          <form action="{{ route('vendor.products.store') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Product Title <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Professional Studio Headphones" value="{{ old('name') }}" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                  <option value="">Select Category</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                      {{ $category->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                  <option value="">Select Brand</option>
                  @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                      {{ $brand->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Price (USD) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" step="0.01" name="price" class="form-control" placeholder="99.99" value="{{ old('price') }}" required>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                <input type="number" name="stock_quantity" class="form-control" placeholder="50" value="{{ old('stock_quantity', 10) }}" min="0" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">SKU <span class="text-danger">*</span></label>
                <input type="text" name="sku" class="form-control" placeholder="e.g. PROD-001" value="{{ old('sku') }}" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="4" placeholder="Detailed product specifications, warranty, and features...">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Publish Product</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <h5 class="card-header">Vendor Terms</h5>
        <div class="card-body">
          <div class="alert alert-info py-2">
            <h6 class="alert-heading mb-1"><i class="bx bx-info-circle me-1"></i> Marketplace Terms</h6>
            <p class="mb-0 small">Platform Commission: <strong>{{ $vendor->commission_rate }}%</strong> on every completed order. Your net earnings are automatically calculated and added to your balance.</p>
          </div>
          <small class="text-muted">Ensure all listed products follow marketplace quality guidelines.</small>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

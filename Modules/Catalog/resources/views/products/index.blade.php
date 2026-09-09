@extends('layouts/layoutMaster')

@section('title', 'Product Catalog - Sneat')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog /</span> Products</h4>
    <a href="{{ route('catalog.products.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Add Product
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('catalog.products.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Search by name or SKU..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('catalog.products.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Products Table Card -->
  <div class="card">
    <h5 class="card-header">All Products ({{ $products->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Base Price</th>
            <th>Total Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($products as $product)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-3 bg-label-primary rounded p-1 d-flex align-items-center justify-content-center">
                    <i class="bx bx-package fs-4"></i>
                  </div>
                  <div>
                    <span class="fw-semibold d-block text-body">{{ $product->name }}</span>
                    <small class="text-muted">SKU: {{ $product->sku ?? 'N/A' }} | Type: {{ ucfirst($product->type ?? 'simple') }}</small>
                  </div>
                </div>
              </td>
              <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
              <td>{{ $product->brand->name ?? 'None' }}</td>
              <td><span class="fw-bold">${{ number_format($product->price, 2) }}</span></td>
              <td>
                @php
                  $totalStock = $product->variants->sum(fn($v) => $v->stocks->sum('quantity_on_hand'));
                @endphp
                @if($totalStock <= 5)
                  <span class="badge bg-label-danger">{{ $totalStock }} (Low Stock)</span>
                @else
                  <span class="badge bg-label-success">{{ $totalStock }} In Stock</span>
                @endif
              </td>
              <td>
                @if(($product->status ?? 'published') === 'published')
                  <span class="badge bg-success">Published</span>
                @elseif($product->status === 'draft')
                  <span class="badge bg-secondary">Draft</span>
                @else
                  <span class="badge bg-warning">Archived</span>
                @endif
              </td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('catalog.products.edit', $product->id) }}"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                    <form action="{{ route('catalog.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger"><i class="bx bx-trash me-1"></i> Delete</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                No products found in the catalog.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $products->links() }}
    </div>
  </div>
</div>
@endsection

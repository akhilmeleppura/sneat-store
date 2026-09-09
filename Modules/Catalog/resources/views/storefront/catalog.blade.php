@extends('layouts/layoutFront')

@section('title', 'Shop Catalog — Marketplace Products')

@section('content')
<div class="container py-5">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Catalog</li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- Filters Sidebar -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-bottom">
          <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-1"></i> Filter Products</h5>
        </div>
        <div class="card-body">
          <form method="GET" action="{{ route('storefront.catalog') }}">
            <!-- Search Query -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Keywords</label>
              <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..." value="{{ request('q') }}">
                <button type="submit" class="btn btn-outline-secondary"><i class="bx bx-search"></i></button>
              </div>
            </div>

            <!-- Categories -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Category</label>
              <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                    {{ $cat->name }} ({{ $cat->products_count }})
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Brands -->
            @if($brands->isNotEmpty())
              <div class="mb-4">
                <label class="form-label fw-semibold">Brand</label>
                <select name="brand" class="form-select" onchange="this.form.submit()">
                  <option value="">All Brands</option>
                  @foreach($brands as $brand)
                    <option value="{{ $brand->slug }}" {{ request('brand') === $brand->slug ? 'selected' : '' }}>
                      {{ $brand->name }} ({{ $brand->products_count }})
                    </option>
                  @endforeach
                </select>
              </div>
            @endif

            <!-- Vendors -->
            @if($vendors->isNotEmpty())
              <div class="mb-4">
                <label class="form-label fw-semibold">Seller / Vendor</label>
                <select name="vendor" class="form-select" onchange="this.form.submit()">
                  <option value="">All Sellers</option>
                  @foreach($vendors as $ven)
                    <option value="{{ $ven->slug }}" {{ request('vendor') === $ven->slug ? 'selected' : '' }}>
                      {{ $ven->name }} ({{ $ven->products_count }})
                    </option>
                  @endforeach
                </select>
              </div>
            @endif

            <!-- Price Range -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Price Range ($)</label>
              <div class="row g-2">
                <div class="col-6">
                  <input type="number" name="min_price" class="form-control" placeholder="Min" value="{{ request('min_price') }}">
                </div>
                <div class="col-6">
                  <input type="number" name="max_price" class="form-control" placeholder="Max" value="{{ request('max_price') }}">
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
              <a href="{{ route('storefront.catalog') }}" class="btn btn-outline-secondary">Reset All</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="col-lg-9">
      <!-- Sort & Stats Bar -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 bg-body border rounded shadow-sm">
        <span class="text-muted">Showing <strong>{{ $products->total() }}</strong> product(s)</span>
        <form method="GET" action="{{ route('storefront.catalog') }}" class="d-flex align-items-center gap-2">
          @foreach(request()->except('sort') as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
          @endforeach
          <label class="text-muted small text-nowrap mb-0">Sort By:</label>
          <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest Arrivals</option>
            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Alphabetical (A-Z)</option>
          </select>
        </form>
      </div>

      <!-- Products Grid -->
      <div class="row g-4">
        @forelse($products as $product)
          <div class="col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm product-card">
              <div class="position-relative">
                <a href="{{ route('storefront.product.show', $product->slug) }}">
                  <img src="{{ $product->thumbnail_url }}" class="card-img-top p-3" alt="{{ $product->name }}" style="height: 200px; object-fit: contain;">
                </a>
                @if($product->vendor)
                  <span class="badge bg-label-info position-absolute top-0 start-0 m-2">
                    <i class="bx bx-store me-1"></i>{{ $product->vendor->name }}
                  </span>
                @endif
                <form action="{{ route('store.wishlist.toggle') }}" method="POST" class="position-absolute top-0 end-0 m-2 z-2">
                  @csrf
                  <input type="hidden" name="product_id" value="{{ $product->id }}">
                  <button type="submit" class="btn btn-sm btn-icon btn-light rounded-circle shadow-sm text-danger" title="Save to Wishlist">
                    <i class="bx bx-heart"></i>
                  </button>
                </form>
              </div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <small class="text-muted text-truncate" style="max-width: 50%;">{{ $product->category?->name ?? 'General' }}</small>
                  @if($product->vendor)
                    <a href="{{ route('storefront.vendor.show', $product->vendor->slug) }}" class="small text-muted text-decoration-none text-truncate" style="max-width: 48%;" title="Seller: {{ $product->vendor->name }}">
                      <i class="bx bx-store me-1"></i>{{ $product->vendor->name }}
                    </a>
                  @endif
                </div>
                <h6 class="card-title mb-2">
                  <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading fw-semibold text-decoration-none text-truncate d-block">
                    {{ $product->name }}
                  </a>
                </h6>
                <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
                  <div>
                    <span class="fs-5 fw-bold text-primary">{{ money($product->price) }}</span>
                  </div>
                  @if($product->variants->isNotEmpty())
                    <form action="{{ route('store.cart.add') }}" method="POST">
                      @csrf
                      <input type="hidden" name="product_variant_id" value="{{ $product->variants->first()->id }}">
                      <input type="hidden" name="quantity" value="1">
                      <button type="submit" class="btn btn-sm btn-primary" title="Add to Cart">
                        <i class="bx bx-cart-add fs-5"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12 text-center py-5">
            <i class="bx bx-package fs-1 text-muted mb-2"></i>
            <h5 class="text-muted">No products found matching your filter criteria.</h5>
            <a href="{{ route('storefront.catalog') }}" class="btn btn-primary mt-2">Clear Filters</a>
          </div>
        @endforelse
      </div>

      <!-- Pagination -->
      @if($products->hasPages())
        <div class="mt-5 d-flex justify-content-center">
          {{ $products->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@extends('layouts/layoutFront')

@section('title', $vendor->name . ' - Marketplace Seller Store')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    
    <!-- Vendor Header Card -->
    <div class="card border-0 shadow-sm overflow-hidden mb-5">
      <div class="bg-label-primary p-4 p-md-5 text-white position-relative" style="background: linear-gradient(135deg, #696cff 0%, #3f4294 100%);">
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
          <div class="avatar avatar-xl bg-white rounded-circle p-1 shadow-sm d-flex align-items-center justify-content-center text-primary" style="width: 80px; height: 80px;">
            @if($vendor->logo_url)
              <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}" class="rounded-circle w-100 h-100 object-fit-cover">
            @else
              <span class="fs-1 fw-bold">{{ strtoupper(substr($vendor->name, 0, 1)) }}</span>
            @endif
          </div>
          <div class="flex-grow-1 text-white">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <h2 class="fw-bold mb-0 text-white">{{ $vendor->name }}</h2>
              <span class="badge bg-success"><i class="bx bx-check-shield me-1"></i> Verified Merchant</span>
            </div>
            <p class="text-white-50 mb-2">{{ $vendor->description ?? 'Trusted seller on Sneat Store offering quality products and express fulfillment.' }}</p>
            <div class="d-flex flex-wrap gap-3 small text-white-50">
              @if($vendor->email)
                <span><i class="bx bx-envelope me-1"></i> {{ $vendor->email }}</span>
              @endif
              @if($vendor->phone)
                <span><i class="bx bx-phone me-1"></i> {{ $vendor->phone }}</span>
              @endif
              <span><i class="bx bx-calendar me-1"></i> Member since {{ $vendor->created_at->format('M Y') }}</span>
              <span><i class="bx bx-package me-1"></i> {{ $products->total() }} Products Listed</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Trust Badges Bar -->
      <div class="card-body py-3 bg-body-tertiary border-top">
        <div class="row text-center g-2 small">
          <div class="col-sm-4 border-end">
            <i class="bx bx-shield-quarter text-primary me-1"></i> Buyer Protection Guaranteed
          </div>
          <div class="col-sm-4 border-end">
            <i class="bx bx-badge-check text-success me-1"></i> 100% Authentic Quality
          </div>
          <div class="col-sm-4">
            <i class="bx bx-refresh text-info me-1"></i> 30-Day Hassle-Free Returns
          </div>
        </div>
      </div>
    </div>

    <!-- Product Grid & Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-0">Store Catalog</h4>
        <small class="text-muted">Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} items</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <form method="GET" action="{{ route('storefront.vendor.show', $vendor->slug) }}" class="d-flex gap-2">
          <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Sort by: Newest</option>
            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
          </select>
        </form>
      </div>
    </div>

    @if($products->isEmpty())
      <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
          <i class="bx bx-package text-muted display-4 mb-3"></i>
          <h5>No Products Found</h5>
          <p class="text-muted">This vendor does not have any active products listed right now.</p>
          <a href="{{ route('storefront.catalog.index') }}" class="btn btn-primary">Browse Marketplace Catalog</a>
        </div>
      </div>
    @else
      <div class="row g-4 mb-5">
        @foreach($products as $product)
          <div class="col-xl-3 col-lg-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm product-card">
              <div class="position-relative overflow-hidden text-center p-3">
                <a href="{{ route('storefront.product.show', $product->slug) }}">
                  <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid" style="height: 180px; object-fit: contain;">
                </a>
              </div>
              <div class="card-body d-flex flex-column pt-0">
                <div class="mb-1">
                  @if($product->category)
                    <small class="text-muted">{{ $product->category->name }}</small>
                  @endif
                </div>
                <h6 class="card-title fw-bold mb-2">
                  <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading text-decoration-none text-truncate d-block">
                    {{ $product->name }}
                  </a>
                </h6>
                <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                  <div>
                    <span class="fs-5 fw-bold text-primary">{{ money($product->price) }}</span>
                  </div>
                  <a href="{{ route('storefront.product.show', $product->slug) }}" class="btn btn-sm btn-outline-primary">
                    View Details
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-center">
        {{ $products->links() }}
      </div>
    @endif

  </div>
</section>
@endsection

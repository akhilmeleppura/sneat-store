@extends('layouts/layoutFront')

@section('title', 'Marketplace Storefront — Premium Multi-Tenant Products')

@section('content')
<!-- Hero Section: Start -->
<section class="section-py landing-hero position-relative bg-body pt-5 pb-5">
  <div class="container">
    <div class="row align-items-center g-4 py-4">
      <div class="col-lg-7">
        <span class="badge bg-label-primary mb-3 px-3 py-2"><i class="bx bx-store me-1"></i> Multi-Vendor Enterprise Marketplace</span>
        <h1 class="display-4 fw-bold mb-3">Discover Curated Products from Top Marketplace Sellers</h1>
        <p class="lead text-muted mb-4">Explore thousands of authentic electronics, apparel, and home essentials with verified stock, transparent pricing, and rapid multi-branch fulfillment.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="{{ route('storefront.catalog') }}" class="btn btn-primary btn-lg">
            <i class="bx bx-shopping-bag me-1"></i> Browse Catalog
          </a>
          <a href="{{ route('store.cart.index') }}" class="btn btn-outline-secondary btn-lg">
            <i class="bx bx-cart me-1"></i> View Cart
          </a>
        </div>
      </div>
      <div class="col-lg-5 text-center">
        <div class="card shadow-lg border-0 bg-primary text-white p-4">
          <div class="card-body">
            <i class="bx bx-shield-quarter fs-1 mb-3"></i>
            <h3 class="text-white fw-bold">100% Guaranteed Stock</h3>
            <p class="text-white-50">Zero-client-trust pricing engine & real-time branch inventory reservation ensure zero overselling and immediate dispatch.</p>
            <div class="d-flex justify-content-around mt-4 pt-2 border-top border-white border-opacity-25">
              <div>
                <h4 class="text-white mb-0">{{ $featuredProducts->count() }}+</h4>
                <small class="text-white-50">Trending Items</small>
              </div>
              <div>
                <h4 class="text-white mb-0">{{ $featuredCategories->count() }}</h4>
                <small class="text-white-50">Categories</small>
              </div>
              <div>
                <h4 class="text-white mb-0">{{ $featuredVendors->count() }}</h4>
                <small class="text-white-50">Top Sellers</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Hero Section: End -->

<!-- Featured Categories: Start -->
<section class="section-py bg-body-tertiary py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-label-primary mb-2">Explore Departments</span>
      <h2 class="fw-bold">Popular Categories</h2>
      <p class="text-muted">Find exactly what you are looking for by browsing our curated departments.</p>
    </div>

    <div class="row g-4">
      @forelse($featuredCategories as $cat)
        <div class="col-md-4 col-sm-6">
          <a href="{{ route('storefront.catalog', ['category' => $cat->slug]) }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm transition-hover">
              <div class="card-body text-center p-4">
                <div class="avatar avatar-lg bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                  <i class="bx bx-folder fs-2"></i>
                </div>
                <h5 class="card-title text-heading fw-bold mb-1">{{ $cat->name }}</h5>
                <span class="text-muted small">{{ $cat->products_count }} Products Available</span>
              </div>
            </div>
          </a>
        </div>
      @empty
        <div class="col-12 text-center text-muted py-4">No categories configured yet.</div>
      @endforelse
    </div>
  </div>
</section>
<!-- Featured Categories: End -->

<!-- Featured Products: Start -->
<section class="section-py py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
      <div>
        <span class="badge bg-label-success mb-2">Trending Deals</span>
        <h2 class="fw-bold mb-0">Featured Products</h2>
      </div>
      <a href="{{ route('storefront.catalog') }}" class="btn btn-outline-primary">
        View All <i class="bx bx-right-arrow-alt ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      @forelse($featuredProducts as $product)
        <div class="col-lg-3 col-md-6">
          <div class="card h-100 border-0 shadow-sm product-card">
            <div class="position-relative">
              <img src="{{ $product->thumbnail_url }}" class="card-img-top p-3" alt="{{ $product->name }}" style="height: 220px; object-fit: contain;">
              @if($product->vendor)
                <span class="badge bg-label-info position-absolute top-0 start-0 m-3">
                  <i class="bx bx-store me-1"></i>{{ $product->vendor->name }}
                </span>
              @endif
            </div>
            <div class="card-body d-flex flex-column">
              <small class="text-muted mb-1">{{ $product->category?->name ?? 'General' }}</small>
              <h5 class="card-title mb-2">
                <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading fw-semibold text-decoration-none text-truncate d-block">
                  {{ $product->name }}
                </a>
              </h5>
              <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
                <div>
                  <span class="fs-4 fw-bold text-primary">{{ money($product->price) }}</span>
                </div>
                <form action="{{ route('store.cart.add') }}" method="POST">
                  @csrf
                  <input type="hidden" name="product_variant_id" value="{{ $product->variants->first()?->id }}">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="btn btn-sm btn-primary" title="Add to Cart">
                    <i class="bx bx-cart-add fs-5"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12 text-center text-muted py-5">No products listed in this store yet.</div>
      @endforelse
    </div>
  </div>
</section>
<!-- Featured Products: End -->

@if($featuredVendors->isNotEmpty())
<!-- Marketplace Vendors Spotlight: Start -->
<section class="section-py bg-body-tertiary py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-label-info mb-2">Verified Sellers</span>
      <h2 class="fw-bold">Top Marketplace Stores</h2>
      <p class="text-muted">Shop directly from top verified independent sellers with dedicated customer service.</p>
    </div>

    <div class="row g-4">
      @foreach($featuredVendors as $vendor)
        <div class="col-md-3 col-sm-6">
          <div class="card h-100 text-center border-0 shadow-sm p-3">
            <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto my-2 d-flex align-items-center justify-content-center">
              <i class="bx bx-store-alt fs-1"></i>
            </div>
            <h5 class="fw-bold mb-1">{{ $vendor->name }}</h5>
            <small class="text-muted mb-3 d-block">{{ $vendor->products_count }} Listed Products</small>
            <a href="{{ route('storefront.catalog', ['vendor' => $vendor->slug]) }}" class="btn btn-sm btn-outline-primary mt-auto">
              Visit Store
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
<!-- Marketplace Vendors Spotlight: End -->
@endif
@endsection

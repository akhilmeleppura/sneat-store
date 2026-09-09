@extends('layouts/layoutFront')

@section('title', 'My Saved Wishlist')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1">My Wishlist</h3>
        <p class="text-muted mb-0">Products you've saved to purchase later.</p>
      </div>
      <a href="{{ route('storefront.catalog') }}" class="btn btn-outline-primary">
        <i class="bx bx-shopping-bag me-1"></i> Continue Shopping
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($wishlistItems->isEmpty())
      <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
          <div class="avatar avatar-xl bg-label-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bx bx-heart fs-1"></i>
          </div>
          <h4 class="fw-bold mb-2">Your wishlist is currently empty</h4>
          <p class="text-muted mb-4">Explore our catalog and click the heart icon on items you love to save them here!</p>
          <a href="{{ route('storefront.catalog') }}" class="btn btn-primary px-4 py-2">
            <i class="bx bx-compass me-1"></i> Explore Catalog
          </a>
        </div>
      </div>
    @else
      <div class="row g-4">
        @foreach($wishlistItems as $item)
          @php $product = $item->product; @endphp
          @if($product)
            <div class="col-sm-6 col-lg-4 col-xl-3" id="wishlist-item-{{ $item->id }}">
              <div class="card h-100 border-0 shadow-sm hover-shadow transition-all position-relative">
                <!-- Remove Wishlist Button -->
                <form action="{{ route('account.wishlist.toggle') }}" method="POST" class="position-absolute top-0 end-0 m-2 z-3">
                  @csrf
                  <input type="hidden" name="product_id" value="{{ $product->id }}">
                  <button type="submit" class="btn btn-sm btn-icon btn-light rounded-circle shadow-sm text-danger" title="Remove from wishlist">
                    <i class="bx bxs-heart fs-5"></i>
                  </button>
                </form>

                <!-- Product Thumbnail -->
                <div class="p-3 text-center bg-body-tertiary rounded-top">
                  <a href="{{ route('storefront.product.show', $product->slug) }}">
                    <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                  </a>
                </div>

                <div class="card-body d-flex flex-column justify-content-between p-3">
                  <div>
                    <span class="badge bg-label-primary mb-2">{{ $product->category?->name ?? 'Product' }}</span>
                    <h6 class="fw-bold mb-1">
                      <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading text-decoration-none text-truncate d-block">
                        {{ $product->name }}
                      </a>
                    </h6>
                    <small class="text-muted d-block mb-2">SKU: {{ $product->sku }}</small>
                  </div>

                  <div class="mt-3">
                    <div class="d-flex align-items-baseline justify-content-between mb-3">
                      <span class="fs-5 fw-bold text-primary">{{ money($product->price) }}</span>
                      @if($product->compare_at_price > $product->price)
                        <span class="text-muted text-decoration-line-through small">{{ money($product->compare_at_price) }}</span>
                      @endif
                    </div>

                    <!-- Move to Cart Action -->
                    <form action="{{ route('account.wishlist.move_to_cart', $item->id) }}" method="POST">
                      @csrf
                      <button type="submit" class="btn btn-primary w-100 btn-sm">
                        <i class="bx bx-cart-add me-1"></i> Move to Cart
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection

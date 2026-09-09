@props(['title' => 'Recommended Products', 'products' => collect(), 'badge' => 'Curated Picks'])

@if($products->isNotEmpty())
  <div class="card border-0 shadow-sm mt-5">
    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
      <div>
        @if($badge)
          <span class="badge bg-label-primary px-2 py-1 mb-1" style="font-size: 11px;">{{ $badge }}</span>
        @endif
        <h5 class="fw-bold mb-0 text-heading">{{ $title }}</h5>
      </div>
      <a href="{{ route('storefront.catalog') }}" class="btn btn-sm btn-outline-secondary">
        Browse All <i class="bx bx-chevron-right ms-1"></i>
      </a>
    </div>
    <div class="card-body p-3">
      <div class="row g-3">
        @foreach($products as $p)
          <div class="col-6 col-md-3">
            <div class="card h-100 border rounded shadow-none product-card p-2">
              <div class="position-relative overflow-hidden rounded mb-2" style="height: 140px;">
                <img src="{{ $p->thumbnail_url ?: asset('assets/img/ecommerce-images/product-1.png') }}"
                     alt="{{ $p->name }}" class="w-100 h-100" style="object-fit: cover;">
                <button type="button" class="btn btn-xs btn-icon btn-light rounded-circle position-absolute top-0 end-0 m-1 shadow-sm"
                        onclick="toggleWishlist({{ $p->id }})" title="Wishlist">
                  <i class="bx bx-heart text-danger"></i>
                </button>
              </div>
              <div class="d-flex flex-column flex-grow-1">
                <small class="text-muted d-block text-truncate">{{ $p->category?->name ?? 'General' }}</small>
                <h6 class="fw-semibold mb-1 text-truncate" style="font-size: 14px;">
                  <a href="{{ route('storefront.product.show', $p->slug) }}" class="text-heading">{{ $p->name }}</a>
                </h6>
                <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                  <strong class="text-primary">{{ money($p->price) }}</strong>
                  <a href="{{ route('storefront.product.show', $p->slug) }}" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 2px 6px;">
                    View
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

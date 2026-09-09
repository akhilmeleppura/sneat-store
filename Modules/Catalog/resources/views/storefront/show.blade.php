@extends('layouts/layoutFront')

@section('title', $product->name . ' — Storefront')

@section('content')
<div class="container py-5">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}">Home</a></li>
      <li class="breadcrumb-item"><a href="{{ route('storefront.catalog') }}">Catalog</a></li>
      @if($product->category)
        <li class="breadcrumb-item"><a href="{{ route('storefront.catalog', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
      @endif
      <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
    </ol>
  </nav>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <a href="{{ route('store.cart.index') }}" class="fw-bold ms-2">View Cart & Checkout &rarr;</a>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row g-5">
    <!-- Image Gallery (Left Column) -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-4 text-center">
        <img id="mainProductImage" src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 420px; object-fit: contain;">

        @if($product->images->count() > 1)
          <div class="d-flex justify-content-center gap-2 mt-3 overflow-auto">
            @foreach($product->images as $img)
              <img src="{{ $img->url }}" class="rounded border p-1" style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;" onclick="document.getElementById('mainProductImage').src='{{ $img->url }}'">
            @endforeach
          </div>
        @endif
      </div>
    </div>

    <!-- Product Details & Purchase Form (Right Column) -->
    <div class="col-lg-6">
      <div class="ps-lg-3">
        <!-- Category & Vendor Badges -->
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge bg-label-primary">{{ $product->category?->name ?? 'General Department' }}</span>
          @if($product->brand)
            <span class="badge bg-label-secondary">{{ $product->brand->name }}</span>
          @endif
          @if($product->vendor)
            <a href="{{ route('storefront.vendor.show', $product->vendor->slug) }}" class="badge bg-label-info text-decoration-none" title="Visit {{ $product->vendor->name }} Storefront">
              <i class="bx bx-store-alt me-1"></i> Sold by {{ $product->vendor->name }}
              <i class="bx bx-check-shield text-success ms-1"></i>
            </a>
          @endif
        </div>

        <h2 class="fw-bold mb-2">{{ $product->name }}</h2>
        <div class="d-flex align-items-center gap-3 mb-3">
          <small class="text-muted">Model SKU: <strong>{{ $product->sku }}</strong></small>
          <span class="text-muted">|</span>
          <div class="d-flex align-items-center gap-1">
            <span class="text-warning fs-6">{!! $product->stars_html !!}</span>
            <span class="fw-bold text-heading ms-1">{{ number_format($product->rating_cache, 1) }}</span>
            <a href="#reviews-section" class="text-muted text-decoration-none small">({{ $product->rating_count }} {{ Str::plural('review', $product->rating_count) }})</a>
          </div>
        </div>

        <!-- Price -->
        <div class="d-flex align-items-baseline gap-3 mb-4">
          <h3 class="fw-bold text-primary mb-0" id="productPriceDisplay">{{ money($product->price) }}</h3>
          @if($product->compare_at_price > $product->price)
            <span class="text-muted text-decoration-line-through">{{ money($product->compare_at_price) }}</span>
            <span class="badge bg-label-danger">Sale</span>
          @endif
        </div>

        @if($product->vendor)
          <div class="card bg-lighter border-0 mb-4 p-3 rounded">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-sm bg-label-primary rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bx bx-store fs-5"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-1">
                    <span class="small text-muted">Sold by</span>
                    <a href="{{ route('storefront.vendor.show', $product->vendor->slug) }}" class="fw-bold text-heading text-decoration-none">
                      {{ $product->vendor->name }}
                    </a>
                    <i class="bx bx-check-shield text-success small" title="Verified Merchant"></i>
                  </div>
                  <small class="text-muted d-block">Verified Marketplace Merchant</small>
                </div>
              </div>
              <a href="{{ route('storefront.vendor.show', $product->vendor->slug) }}" class="btn btn-sm btn-outline-primary">
                View Store
              </a>
            </div>
          </div>
        @endif

        <!-- Add to Cart Form -->
        <form action="{{ route('store.cart.add') }}" method="POST">
          @csrf

          <!-- Variant Selector -->
          @if($product->variants->count() > 1)
            <div class="mb-4">
              <label class="form-label fw-semibold">Select Edition / Variant:</label>
              <div class="row g-2">
                @foreach($product->variants as $index => $variant)
                  @php $stock = $variantStocks[$variant->id] ?? 0; @endphp
                  <div class="col-sm-6">
                    <input type="radio" class="btn-check" name="product_variant_id" id="variant_{{ $variant->id }}" value="{{ $variant->id }}" {{ $index === 0 ? 'checked' : '' }} onchange="updateVariantDetails('{{ $variant->price }}', {{ $stock }})" {{ $stock <= 0 ? 'disabled' : '' }}>
                    <label class="btn btn-outline-secondary w-100 text-start p-3 h-100 d-flex flex-column justify-content-between" for="variant_{{ $variant->id }}">
                      <div>
                        <span class="fw-semibold d-block">{{ $variant->name }}</span>
                        <small class="text-muted">SKU: {{ $variant->sku }}</small>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="fw-bold">{{ money($variant->price) }}</span>
                        <small class="{{ $stock > 0 ? 'text-success' : 'text-danger' }}">
                          {{ $stock > 0 ? "{$stock} In Stock" : "Sold Out" }}
                        </small>
                      </div>
                    </label>
                  </div>
                @endforeach
              </div>
            </div>
          @else
            @php 
              $singleVariant = $product->variants->first(); 
              $stock = $singleVariant ? ($variantStocks[$singleVariant->id] ?? 0) : 0;
            @endphp
            <input type="hidden" name="product_variant_id" value="{{ $singleVariant?->id }}">
            <div class="mb-3">
              @if($stock > 5)
                <span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i> In Stock ({{ $stock }} units available)</span>
              @elseif($stock > 0)
                <span class="badge bg-label-warning"><i class="bx bx-time-five me-1"></i> Low Stock: Only {{ $stock }} left!</span>
              @else
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge bg-label-danger"><i class="bx bx-x-circle me-1"></i> Currently Out of Stock</span>
                  <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#backInStockModal">
                    <i class="bx bx-bell me-1"></i> Notify When Restocked
                  </button>
                </div>
              @endif
            </div>
          @endif

          <!-- Quantity Stepper & Add Button -->
          <div class="row g-3 align-items-center mb-4">
            <div class="col-sm-4">
              <label class="form-label small text-muted">Quantity</label>
              <div class="input-group">
                <button type="button" class="btn btn-outline-secondary" onclick="stepQty(-1)" {{ $stock <= 0 ? 'disabled' : '' }}>-</button>
                <input type="number" name="quantity" id="productQuantity" class="form-control text-center" value="1" min="1" max="100" {{ $stock <= 0 ? 'disabled' : '' }}>
                <button type="button" class="btn btn-outline-secondary" onclick="stepQty(1)" {{ $stock <= 0 ? 'disabled' : '' }}>+</button>
              </div>
            </div>
            <div class="col-sm-8 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary btn-lg flex-grow-1" {{ $stock <= 0 ? 'disabled' : '' }}>
                <i class="bx {{ $stock > 0 ? 'bx-cart-add' : 'bx-x-circle' }} me-2"></i> {{ $stock > 0 ? 'Add to Shopping Cart' : 'Currently Sold Out' }}
              </button>
              @php
                $inWishlist = auth()->check() ? app(\Modules\Catalog\Services\WishlistService::class)->isInWishlist(auth()->id(), $product->id) : false;
              @endphp
              <button type="button" class="btn btn-outline-danger btn-lg" onclick="document.getElementById('pdp-wishlist-form').submit();" title="{{ $inWishlist ? 'Remove from Wishlist' : 'Save to Wishlist' }}">
                <i class="bx {{ $inWishlist ? 'bxs-heart text-danger' : 'bx-heart' }} fs-4"></i>
              </button>
            </div>
          </div>
        </form>

        <form id="pdp-wishlist-form" action="{{ route('store.wishlist.toggle') }}" method="POST" class="d-none">
          @csrf
          <input type="hidden" name="product_id" value="{{ $product->id }}">
        </form>

        <!-- Feature Highlights -->
        <div class="card bg-body-tertiary border-0 p-3 mb-4">
          <div class="row g-3 text-center">
            <div class="col-4 border-end">
              <i class="bx bx-check-shield text-primary fs-3"></i>
              <small class="d-block text-muted">Authentic Guarantee</small>
            </div>
            <div class="col-4 border-end">
              <i class="bx bx-package text-primary fs-3"></i>
              <small class="d-block text-muted">Express Dispatch</small>
            </div>
            <div class="col-4">
              <i class="bx bx-refresh text-primary fs-3"></i>
              <small class="d-block text-muted">30-Day Returns</small>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent">
            <h5 class="mb-0 fw-bold">Product Description</h5>
          </div>
          <div class="card-body">
            <p class="text-muted mb-0">{!! nl2br(e($product->description ?? 'No description provided.')) !!}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Customer Ratings & Reviews Section -->
  <div id="reviews-section" class="mt-5 pt-4 border-top">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h3 class="fw-bold mb-1">Customer Reviews &amp; Ratings</h3>
        <small class="text-muted">Authentic feedback from verified platform buyers</small>
      </div>
      @auth
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#writeReviewModal">
          <i class="bx bx-edit me-1"></i> Write a Customer Review
        </button>
      @else
        <a href="{{ route('login') }}" class="btn btn-outline-primary">
          <i class="bx bx-log-in me-1"></i> Log In to Write a Review
        </a>
      @endauth
    </div>

    <!-- Rating Summary Overview Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="row g-4 align-items-center">
          <div class="col-md-4 text-center border-end-md">
            <h1 class="display-3 fw-bold text-primary mb-0">{{ number_format($ratingBreakdown['average'], 1) }}</h1>
            <div class="text-warning fs-4 my-2">
              @for($i = 1; $i <= 5; $i++)
                <i class="bx {{ $i <= round($ratingBreakdown['average']) ? 'bxs-star' : 'bx-star text-muted opacity-50' }}"></i>
              @endfor
            </div>
            <span class="text-muted">Based on {{ $ratingBreakdown['total_count'] }} {{ Str::plural('rating', $ratingBreakdown['total_count']) }}</span>
          </div>

          <div class="col-md-8">
            @foreach($ratingBreakdown['breakdown'] as $star => $data)
              <div class="d-flex align-items-center gap-3 mb-2">
                <span class="small fw-semibold text-nowrap" style="width: 45px;">{{ $star }} Star</span>
                <div class="progress flex-grow-1" style="height: 8px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $data['percentage'] }}%" aria-valuenow="{{ $data['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <span class="small text-muted" style="width: 70px; text-align: right;">{{ $data['percentage'] }}% ({{ $data['count'] }})</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Reviews Feed -->
    <div class="row g-3">
      @forelse($reviews as $rev)
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm bg-label-primary rounded-circle d-flex align-items-center justify-content-center fw-bold">
                    {{ strtoupper(substr($rev->user->name ?? 'User', 0, 2)) }}
                  </div>
                  <div>
                    <h6 class="mb-0 fw-semibold">{{ $rev->user->name ?? 'Anonymous Buyer' }}</h6>
                    <small class="text-muted">{{ $rev->created_at->diffForHumans() }}</small>
                  </div>
                </div>
                <div>
                  @if($rev->is_verified_buyer)
                    <span class="badge bg-label-success"><i class="bx bx-check-shield me-1"></i>Verified Buyer</span>
                  @endif
                </div>
              </div>

              <div class="text-warning mb-2 fs-6">
                {!! $rev->stars_html !!}
                @if($rev->title)
                  <span class="fw-bold text-dark ms-2">{{ $rev->title }}</span>
                @endif
              </div>

              <p class="text-secondary mb-0">{!! nl2br(e($rev->comment)) !!}</p>

              @if($rev->admin_reply)
                <div class="mt-3 p-3 bg-light rounded border-start border-primary border-3">
                  <div class="d-flex align-items-center gap-1 mb-1">
                    <i class="bx bx-store text-primary"></i>
                    <span class="fw-bold text-primary small">Merchant Response</span>
                    @if($rev->replied_at)
                      <small class="text-muted ms-2">{{ $rev->replied_at->diffForHumans() }}</small>
                    @endif
                  </div>
                  <small class="text-muted d-block">{!! nl2br(e($rev->admin_reply)) !!}</small>
                </div>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="col-12 text-center py-5 bg-light rounded">
          <i class="bx bx-message-rounded-dots fs-1 text-secondary mb-2"></i>
          <h5 class="fw-bold text-muted">No Customer Reviews Yet</h5>
          <p class="text-muted mb-0">Have you tried this product? Share your experience with shoppers worldwide!</p>
        </div>
      @endforelse
    </div>
  </div>

  <!-- Write a Review Modal -->
  @auth
    <div class="modal fade" id="writeReviewModal" tabindex="-1" aria-labelledby="writeReviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('storefront.reviews.store', $product->slug) }}" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title fw-bold" id="writeReviewModalLabel">Write a Review for {{ $product->name }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3 text-center">
                <label class="form-label d-block fw-semibold mb-2">Overall Rating</label>
                <div class="d-flex justify-content-center gap-2 fs-3 text-warning" id="ratingStarsSelector">
                  @for($star = 1; $star <= 5; $star++)
                    <i class="bx bx-star star-selectable cursor-pointer" data-rating="{{ $star }}" onclick="setReviewRating({{ $star }})"></i>
                  @endfor
                </div>
                <input type="hidden" name="rating" id="reviewRatingInput" value="5" required>
                <small class="text-muted d-block mt-1" id="ratingLabel">5 Stars — Excellent</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Review Headline / Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Exceptional build quality and sound clarity">
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Your Review <span class="text-danger">*</span></label>
                <textarea name="comment" class="form-control" rows="4" placeholder="What did you like or dislike? How does it fit your use case?" required minlength="5"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    const ratingTexts = {
      1: '1 Star — Poor',
      2: '2 Stars — Fair',
      3: '3 Stars — Average',
      4: '4 Stars — Good',
      5: '5 Stars — Excellent'
    };

    function setReviewRating(rating) {
      document.getElementById('reviewRatingInput').value = rating;
      document.getElementById('ratingLabel').innerText = ratingTexts[rating] || (rating + ' Stars');
      const stars = document.querySelectorAll('#ratingStarsSelector .star-selectable');
      stars.forEach(star => {
        const starVal = parseInt(star.getAttribute('data-rating'));
        if (starVal <= rating) {
          star.classList.remove('bx-star', 'text-muted', 'opacity-50');
          star.classList.add('bxs-star', 'text-warning');
        } else {
          star.classList.remove('bxs-star', 'text-warning');
          star.classList.add('bx-star', 'text-muted', 'opacity-50');
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      setReviewRating(5);
    });
    </script>
  @endauth

  @if($relatedProducts->isNotEmpty())
    <!-- Related Products -->
    <div class="mt-5 pt-4 border-top">
      <h3 class="fw-bold mb-4">You May Also Like</h3>
      <div class="row g-4">
        @foreach($relatedProducts as $rel)
          <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
              <img src="{{ $rel->thumbnail_url }}" class="card-img-top p-3" alt="{{ $rel->name }}" style="height: 180px; object-fit: contain;">
              <div class="card-body d-flex flex-column pt-0">
                <h6 class="card-title text-truncate mb-2">
                  <a href="{{ route('storefront.product.show', $rel->slug) }}" class="text-heading text-decoration-none">
                    {{ $rel->name }}
                  </a>
                </h6>
                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <span class="fw-bold text-primary">{{ money($rel->price) }}</span>
                  <a href="{{ route('storefront.product.show', $rel->slug) }}" class="btn btn-xs btn-outline-primary">View</a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if(isset($customersAlsoBought) && $customersAlsoBought->isNotEmpty())
    @include('catalog::storefront.components.recommendations', ['title' => 'Customers Also Bought', 'products' => $customersAlsoBought, 'badge' => 'Frequently Paired'])
  @endif

  @if(isset($recentlyViewed) && $recentlyViewed->isNotEmpty())
    @include('catalog::storefront.components.recommendations', ['title' => 'Recently Viewed By You', 'products' => $recentlyViewed, 'badge' => 'Your History'])
  @endif
</div>

<!-- Back-in-Stock Subscription Modal -->
<div class="modal fade" id="backInStockModal" tabindex="-1" aria-labelledby="backInStockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="backInStockModalLabel"><i class="bx bx-bell me-2 text-warning"></i>Restock Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="backInStockForm" onsubmit="submitBackInStock(event)">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="variant_id" id="modalVariantId" value="{{ $product->variants->first()?->id }}">
        <div class="modal-body">
          <p class="text-muted">Enter your email and we will notify you immediately once <strong>{{ $product->name }}</strong> is back in stock.</p>
          <div id="bisAlertArea"></div>
          <div class="mb-3">
            <label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="{{ auth()->check() ? auth()->user()->email : '' }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number (Optional for SMS alert)</label>
            <input type="tel" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" id="bisSubmitBtn">
            <i class="bx bx-bell me-1"></i> Notify Me
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function stepQty(change) {
  const input = document.getElementById('productQuantity');
  let val = parseInt(input.value) || 1;
  val = Math.max(1, val + change);
  input.value = val;
}

function updateVariantDetails(price, stock) {
  document.getElementById('productPriceDisplay').innerText = '$' + parseFloat(price).toFixed(2);
}

function submitBackInStock(e) {
  e.preventDefault();
  const form = document.getElementById('backInStockForm');
  const btn = document.getElementById('bisSubmitBtn');
  const alertArea = document.getElementById('bisAlertArea');
  const formData = new FormData(form);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Subscribing...';

  fetch('{{ route("store.back_in_stock.subscribe") }}', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json'
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bx bx-bell me-1"></i> Notify Me';
    const alertType = data.status === 'success' ? 'alert-success' : 'alert-info';
    alertArea.innerHTML = `<div class="alert ${alertType} py-2 mb-3"><i class="bx bx-check-circle me-1"></i> ${data.message}</div>`;
    if (data.status === 'success') {
      setTimeout(() => {
        const modalEl = document.getElementById('backInStockModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
      }, 2500);
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bx bx-bell me-1"></i> Notify Me';
    alertArea.innerHTML = '<div class="alert alert-danger py-2 mb-3"><i class="bx bx-error me-1"></i> Failed to subscribe. Please try again.</div>';
  });
}
</script>
@endsection

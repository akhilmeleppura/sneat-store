@extends('layouts/layoutFront')

@section('title', 'Search Products — AK-Mart')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('storefront.catalog') }}">Catalog</a></li>
            <li class="breadcrumb-item active">Search</li>
          </ol>
        </nav>
        <h3 class="fw-bold mb-0">
          @if(!empty($filters['q']))
            Results for <span class="text-primary">"{{ $filters['q'] }}"</span>
          @else
            Browse and Discover Catalog
          @endif
          <span class="badge bg-label-secondary ms-2 fs-6">{{ $products->total() }} items found</span>
        </h3>
      </div>
      <div>
        <a href="{{ route('store.compare.index') }}" class="btn btn-outline-primary btn-sm">
          <i class="bx bx-git-compare me-1"></i> View Compared Items
        </a>
      </div>
    </div>

    <div class="row g-4">
      <!-- Faceted Filters (Left) -->
      <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-1"></i> Filters</h5>
            @if(request()->hasAny(['q', 'category', 'brand', 'min_price', 'max_price', 'in_stock']))
              <a href="{{ route('store.search') }}" class="small text-danger">Reset All</a>
            @endif
          </div>
          <div class="card-body">
            <form method="GET" action="{{ route('store.search') }}" id="searchFilterForm">
              <!-- Live Search Input -->
              <div class="mb-4 position-relative">
                <label class="form-label fw-semibold">Search Term</label>
                <div class="input-group">
                  <input type="text" name="q" id="searchKeywordInput" class="form-control"
                         placeholder="Keywords, SKU..." value="{{ $filters['q'] ?? '' }}" autocomplete="off">
                  <button type="submit" class="btn btn-primary"><i class="bx bx-search"></i></button>
                </div>
                <!-- Autocomplete Dropdown Container -->
                <div id="autocompleteResults" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; display: none; max-height: 320px; overflow-y: auto;"></div>
              </div>

              <!-- Categories -->
              <div class="mb-4">
                <label class="form-label fw-semibold">Categories</label>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="category" value="" id="cat_all"
                         {{ empty($filters['category_slug']) ? 'checked' : '' }} onchange="this.form.submit()">
                  <label class="form-check-label" for="cat_all">All Categories</label>
                </div>
                @foreach($categories as $cat)
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="category" value="{{ $cat->slug }}" id="cat_{{ $cat->id }}"
                           {{ ($filters['category_slug'] ?? '') === $cat->slug ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label d-flex justify-content-between" for="cat_{{ $cat->id }}">
                      <span>{{ $cat->name }}</span>
                      <small class="text-muted">({{ $cat->products_count }})</small>
                    </label>
                  </div>
                @endforeach
              </div>

              <hr class="my-3">

              <!-- Price Range -->
              <div class="mb-4">
                <label class="form-label fw-semibold">Price Range</label>
                <div class="row g-2">
                  <div class="col-6">
                    <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min $"
                           value="{{ $filters['min_price'] ?? '' }}">
                  </div>
                  <div class="col-6">
                    <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max $"
                           value="{{ $filters['max_price'] ?? '' }}">
                  </div>
                </div>
              </div>

              <!-- In-Stock Filter -->
              <div class="mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStockCheck"
                         {{ !empty($filters['in_stock']) ? 'checked' : '' }} onchange="this.form.submit()">
                  <label class="form-check-label" for="inStockCheck">
                    In Stock Only
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                <i class="bx bx-check me-1"></i> Apply Filters
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Search Results (Right) -->
      <div class="col-lg-9">
        <!-- Sorting bar -->
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-body-tertiary rounded border">
          <small class="text-muted">Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} of {{ $products->total() }}</small>
          <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Sort By:</label>
            <select name="sort" class="form-select form-select-sm" style="width: auto;"
                    onchange="document.getElementById('searchFilterForm').elements['sort'].value = this.value; document.getElementById('searchFilterForm').submit();">
              <option value="latest" {{ ($filters['sort'] ?? '') === 'latest' ? 'selected' : '' }}>Newest</option>
              <option value="price_low_high" {{ ($filters['sort'] ?? '') === 'price_low_high' ? 'selected' : '' }}>Price: Low to High</option>
              <option value="price_high_low" {{ ($filters['sort'] ?? '') === 'price_high_low' ? 'selected' : '' }}>Price: High to Low</option>
              <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
            </select>
          </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-4">
          @forelse($products as $product)
            <div class="col-md-6 col-xl-4">
              <div class="card h-100 border-0 shadow-sm product-card position-relative">
                <div class="position-relative overflow-hidden" style="height: 200px;">
                  <img src="{{ $product->thumbnail_url ?: asset('assets/img/ecommerce-images/product-1.png') }}"
                       class="card-img-top w-100 h-100" style="object-fit: cover;" alt="{{ $product->name }}">
                  <button type="button" class="btn btn-sm btn-icon btn-light rounded-circle position-absolute top-0 end-0 m-2 shadow-sm"
                          onclick="toggleWishlist({{ $product->id }})" title="Add to Wishlist">
                    <i class="bx bx-heart text-danger"></i>
                  </button>
                  <button type="button" class="btn btn-xs btn-outline-secondary position-absolute bottom-0 start-0 m-2 bg-white"
                          onclick="addToCompare({{ $product->id }})" style="font-size: 11px; padding: 2px 6px;">
                    <i class="bx bx-git-compare me-1"></i> Compare
                  </button>
                </div>
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">{{ $product->category?->name ?? 'General' }}</small>
                    @if($product->is_in_stock)
                      <span class="badge bg-label-success" style="font-size: 10px;">In Stock</span>
                    @else
                      <span class="badge bg-label-danger" style="font-size: 10px;">Out of Stock</span>
                    @endif
                  </div>
                  <h6 class="fw-bold mb-2">
                    <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading">{{ $product->name }}</a>
                  </h6>
                  <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="fs-5 fw-bold text-primary">{{ money($product->price) }}</span>
                    <a href="{{ route('storefront.product.show', $product->slug) }}" class="btn btn-sm btn-primary">
                      View
                    </a>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12 py-5 text-center">
              <i class="bx bx-search-alt text-muted" style="font-size: 4rem;"></i>
              <h5 class="mt-3 fw-bold">No Products Found</h5>
              <p class="text-muted">Try adjusting your keyword, resetting filters, or browsing other categories.</p>
              <a href="{{ route('store.search') }}" class="btn btn-outline-primary btn-sm">Clear Search</a>
            </div>
          @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-4 d-flex justify-content-center">
          {{ $products->appends(request()->query())->links() }}
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Autocomplete JS -->
<script>
  const searchInput = document.getElementById('searchKeywordInput');
  const resultsBox = document.getElementById('autocompleteResults');
  let debounceTimer;

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      const query = this.value.trim();

      if (query.length < 2) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(() => {
        fetch(`{{ route('store.search.autocomplete') }}?q=${encodeURIComponent(query)}`)
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success' && data.data.products.length > 0) {
              let html = '';
              data.data.products.forEach(p => {
                html += `
                  <a href="${p.url}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2">
                    <img src="${p.image}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 4px;">
                    <div class="flex-grow-1 overflow-hidden">
                      <div class="small fw-semibold text-truncate">${p.name}</div>
                      <small class="text-muted">${p.category || ''}</small>
                    </div>
                    <div class="fw-bold text-primary small">${p.price_display}</div>
                  </a>
                `;
              });
              resultsBox.innerHTML = html;
              resultsBox.style.display = 'block';
            } else {
              resultsBox.style.display = 'none';
            }
          })
          .catch(() => { resultsBox.style.display = 'none'; });
      }, 250);
    });

    document.addEventListener('click', function(e) {
      if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
        resultsBox.style.display = 'none';
      }
    });
  }

  function addToCompare(productId) {
    fetch('{{ route('store.compare.add') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
    })
    .catch(err => console.error(err));
  }
</script>
@endsection

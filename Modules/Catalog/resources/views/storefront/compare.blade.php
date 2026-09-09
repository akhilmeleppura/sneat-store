@extends('layouts/layoutFront')

@section('title', 'Product Comparison — AK-Mart')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <span class="badge bg-label-primary px-3 py-1 mb-2">Side-by-Side</span>
        <h3 class="fw-bold mb-1">Product Comparison</h3>
        <p class="text-muted mb-0">Compare specifications, prices, ratings, and features across products.</p>
      </div>
      <div>
        @if($products->isNotEmpty())
          <form action="{{ route('store.compare.clear') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="bx bx-trash me-1"></i> Clear Comparison
            </button>
          </form>
        @endif
        <a href="{{ route('storefront.catalog') }}" class="btn btn-primary btn-sm ms-2">
          <i class="bx bx-store me-1"></i> Back to Catalog
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($products->isEmpty())
      <div class="card border-0 shadow-sm p-5 text-center">
        <div class="mb-3">
          <i class="bx bx-git-compare text-muted" style="font-size: 4rem;"></i>
        </div>
        <h4 class="fw-bold">No Products to Compare</h4>
        <p class="text-muted mb-4">Browse our catalog and click "Compare" on products you'd like to analyze side-by-side.</p>
        <div>
          <a href="{{ route('storefront.catalog') }}" class="btn btn-primary px-4">
            <i class="bx bx-search me-1"></i> Explore Products
          </a>
        </div>
      </div>
    @else
      <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 220px;" class="fw-bold text-uppercase">Attributes</th>
                @foreach($products as $product)
                  <th style="min-width: 240px;" class="text-center position-relative">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger position-absolute top-0 end-0 m-2"
                            onclick="removeCompare({{ $product->id }})" title="Remove from compare">
                      <i class="bx bx-x"></i>
                    </button>
                    <img src="{{ $product->thumbnail_url ?: asset('assets/img/ecommerce-images/product-1.png') }}"
                         alt="{{ $product->name }}" class="img-fluid rounded mb-2" style="max-height: 140px; object-fit: contain;">
                    <h6 class="fw-bold mb-1 text-heading">
                      <a href="{{ route('storefront.product.show', $product->slug) }}" class="text-heading">{{ $product->name }}</a>
                    </h6>
                    <div class="text-primary fw-bold fs-5 mb-2">{{ money($product->price) }}</div>
                    <a href="{{ route('storefront.product.show', $product->slug) }}" class="btn btn-sm btn-primary w-100">
                      <i class="bx bx-cart-add me-1"></i> View & Buy
                    </a>
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              <tr>
                <th class="fw-semibold">Category</th>
                @foreach($products as $product)
                  <td class="text-center">{{ $product->category?->name ?? 'General' }}</td>
                @endforeach
              </tr>
              <tr>
                <th class="fw-semibold">Brand</th>
                @foreach($products as $product)
                  <td class="text-center">{{ $product->brand?->name ?? 'Official' }}</td>
                @endforeach
              </tr>
              <tr>
                <th class="fw-semibold">SKU / Model</th>
                @foreach($products as $product)
                  <td class="text-center font-monospace small">{{ $product->sku ?: 'SKU-'.$product->id }}</td>
                @endforeach
              </tr>
              <tr>
                <th class="fw-semibold">Stock Availability</th>
                @foreach($products as $product)
                  <td class="text-center">
                    @if($product->is_in_stock)
                      <span class="badge bg-label-success"><i class="bx bx-check me-1"></i> In Stock</span>
                    @else
                      <span class="badge bg-label-danger"><i class="bx bx-x me-1"></i> Out of Stock</span>
                    @endif
                  </td>
                @endforeach
              </tr>
              <tr>
                <th class="fw-semibold">Rating</th>
                @foreach($products as $product)
                  @php
                    $avg = round($product->reviews()->avg('rating') ?: 5, 1);
                    $rcount = $product->reviews()->count();
                  @endphp
                  <td class="text-center">
                    <span class="text-warning"><i class="bx bxs-star"></i> {{ $avg }}</span>
                    <small class="text-muted">({{ $rcount }} reviews)</small>
                  </td>
                @endforeach
              </tr>
              <tr>
                <th class="fw-semibold">Description</th>
                @foreach($products as $product)
                  <td class="small text-muted text-start" style="vertical-align: top;">
                    {{ Str::limit($product->short_description ?: $product->description, 160) }}
                  </td>
                @endforeach
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>
</section>

<script>
  function removeCompare(productId) {
    fetch('{{ route('store.compare.remove') }}', {
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
      window.location.reload();
    })
    .catch(err => console.error(err));
  }
</script>
@endsection

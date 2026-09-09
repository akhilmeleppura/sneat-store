@extends('layouts/layoutMaster')

@section('title', 'Shopping Cart — AK-Mart')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Store /</span> Shopping Cart</h4>
    <a href="{{ route('catalog.products.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Continue Shopping
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Cart Items Table -->
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Cart Items ({{ $cart->total_quantity }})</h5>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($cart->items as $item)
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-md me-3 bg-label-primary rounded p-1 d-flex align-items-center justify-content-center">
                        <i class="bx bx-package fs-3"></i>
                      </div>
                      <div>
                        <h6 class="mb-0 text-body">{{ $item->product->name ?? 'Product' }}</h6>
                        <small class="text-muted">SKU: {{ $item->variant->sku ?? 'N/A' }} | {{ $item->variant->attribute_summary ?? '' }}</small>
                      </div>
                    </div>
                  </td>
                  <td><span class="fw-semibold">{{ money($item->unit_price) }}</span></td>
                  <td>
                    <form action="{{ route('store.cart.update') }}" method="POST" class="d-flex align-items-center gap-1" style="max-width: 140px;">
                      @csrf
                      <input type="hidden" name="item_id" value="{{ $item->id }}">
                      <input type="number" name="quantity" class="form-control form-control-sm text-center" value="{{ $item->quantity }}" min="1" max="99">
                      <button type="submit" class="btn btn-sm btn-outline-primary" title="Update quantity">
                        <i class="bx bx-refresh"></i>
                      </button>
                    </form>
                  </td>
                  <td><span class="fw-bold text-primary">{{ money($item->line_total) }}</span></td>
                  <td>
                    <form action="{{ route('store.cart.remove', $item->id) }}" method="POST" onsubmit="return confirm('Remove this item?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Remove item">
                        <i class="bx bx-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bx bx-cart fs-1 d-block mb-2 text-secondary"></i>
                    Your shopping cart is currently empty.
                    <div class="mt-3">
                      <a href="{{ route('catalog.products.index') }}" class="btn btn-primary btn-sm">Explore Catalog</a>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Order Summary Card -->
      <!-- Coupon Code Card -->
      <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
          @if(! empty($cart->coupon_code) && $pricing['discount_amount'] > 0)
            <div class="d-flex justify-content-between align-items-center p-3 bg-label-success rounded">
              <div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bx bxs-tag-alt text-success fs-5"></i>
                  <span class="fw-bold text-success">{{ strtoupper($cart->coupon_code) }}</span>
                  <span class="badge bg-success">Applied</span>
                </div>
                <small class="text-muted d-block mt-1">Saved {{ money($pricing['discount_amount']) }} on this order</small>
              </div>
              <form action="{{ route('store.cart.coupon.remove') }}" method="POST" class="m-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Remove coupon">
                  <i class="bx bx-x fs-5"></i>
                </button>
              </form>
            </div>
          @else
            <form action="{{ route('store.cart.coupon') }}" method="POST" class="d-flex gap-2">
              @csrf
              <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="Enter coupon code" value="{{ old('coupon_code') }}">
              <button type="submit" class="btn btn-outline-primary text-nowrap">Apply</button>
            </form>
          @endif
        </div>
      </div>

      <!-- Financial Totals Card -->
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Order Summary</h5></div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span class="fw-semibold">{{ money($pricing['subtotal']) }}</span>
          </div>

          @if($pricing['discount_amount'] > 0)
            <div class="d-flex justify-content-between mb-2 text-success">
              <span>Discount ({{ $pricing['coupon_code'] ?? $cart->coupon_code }})</span>
              <span class="fw-semibold">-{{ money($pricing['discount_amount']) }}</span>
            </div>
          @endif

          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Estimated Tax ({{ $pricing['tax_rate'] }}%)</span>
            <span class="fw-semibold">{{ money($pricing['tax_amount']) }}</span>
          </div>

          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Shipping</span>
            @if($pricing['shipping_amount'] == 0)
              <span class="badge bg-label-success">FREE</span>
            @else
              <span class="fw-semibold">{{ money($pricing['shipping_amount']) }}</span>
            @endif
          </div>

          <hr class="my-3">

          <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="fs-5 fw-bold">Grand Total</span>
            <span class="fs-4 fw-bold text-primary">{{ money($pricing['grand_total']) }}</span>
          </div>

          @if(! $cart->is_empty)
            <a href="{{ route('store.checkout.index') }}" class="btn btn-primary w-100 py-2 fs-6">
              Proceed to Checkout <i class="bx bx-right-arrow-alt ms-1"></i>
            </a>
          @else
            <button class="btn btn-primary w-100 py-2 fs-6" disabled>Cart is Empty</button>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

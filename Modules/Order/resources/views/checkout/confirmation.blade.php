@extends('layouts/layoutMaster')

@section('title', 'Order Received — AK-Mart')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card p-4 text-center mb-4">
    <div class="avatar avatar-xl bg-label-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
      <i class="bx bx-check-circle fs-1"></i>
    </div>
    <h3 class="fw-bold text-success mb-1">Thank You! Order Confirmed</h3>
    <p class="text-muted mb-3">Your order <strong>#{{ $order->order_number }}</strong> has been placed and inventory reserved.</p>
    <div>
      <a href="{{ route('catalog.products.index') }}" class="btn btn-outline-primary">Continue Shopping</a>
    </div>
  </div>

  <div class="row">
    <!-- Order Summary -->
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <h5 class="card-title mb-0">Order Items</h5>
          <span class="badge bg-label-primary">{{ ucfirst($order->status) }}</span>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $item)
                <tr>
                  <td>
                    <strong>{{ $item->product_name }}</strong>
                    <small class="text-muted d-block">SKU: {{ $item->variant_sku }}</small>
                  </td>
                  <td>{{ currency_format($item->unit_price, $order->currency) }}</td>
                  <td>{{ $item->quantity }}</td>
                  <td><span class="fw-bold">{{ currency_format($item->line_total, $order->currency) }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Customer & Payment Details -->
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Summary Breakdown</h5></div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span>{{ currency_format($order->subtotal, $order->currency) }}</span>
          </div>
          @if($order->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2 text-success">
              <span>Discount</span>
              <span>-{{ currency_format($order->discount_amount, $order->currency) }}</span>
            </div>
          @endif
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Tax</span>
            <span>{{ currency_format($order->tax_amount, $order->currency) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Shipping</span>
            <span>{{ currency_format($order->shipping_amount, $order->currency) }}</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold">Grand Total</span>
            <span class="fs-5 fw-bold text-primary">{{ currency_format($order->grand_total, $order->currency) }}</span>
          </div>
          <div>
            <small class="text-muted d-block">Payment Method: <strong>{{ strtoupper($order->payment_method) }}</strong></small>
            <small class="text-muted d-block">Payment Status: <span class="badge bg-label-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($order->payment_status) }}</span></small>
            @if($order->shippingMethod || $order->estimated_delivery_date)
              <div class="alert alert-light border p-2 mt-2 mb-0">
                @if($order->shippingMethod)
                  <small class="text-muted d-block">Carrier: <strong>{{ $order->shippingMethod->carrier }} ({{ $order->shippingMethod->name }})</strong></small>
                @endif
                @if($order->estimated_delivery_date)
                  <small class="text-muted d-block">Est. Arrival: <strong>{{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('M d, Y') }}</strong></small>
                @endif
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

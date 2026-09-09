@extends('layouts/layoutMaster')

@section('title', 'Order Details - #' . $order->order_number)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Vendor Orders /</span> #{{ $order->order_number }}</h4>
      <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y H:i') }}</small>
    </div>
    <a href="{{ route('vendor.orders.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Orders
    </a>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <h5 class="card-header">Your Line Items in this Order</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Line Total</th>
                <th>Platform Fee</th>
                <th>Your Earnings</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $item)
                <tr>
                  <td>
                    <span class="fw-semibold">{{ $item->product_name }}</span>
                    <small class="text-muted d-block">SKU: {{ $item->variant_sku }}</small>
                  </td>
                  <td>${{ number_format($item->unit_price, 2) }}</td>
                  <td>{{ $item->quantity }}</td>
                  <td>${{ number_format($item->line_total, 2) }}</td>
                  <td class="text-danger">-${{ number_format($item->vendor_commission_amount ?? ($item->line_total * ($vendor->commission_rate / 100)), 2) }}</td>
                  <td class="text-success fw-bold">+${{ number_format($item->vendor_earnings_amount ?? ($item->line_total * (1 - ($vendor->commission_rate / 100))), 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Shipping Destination -->
      <div class="card mb-4">
        <h5 class="card-header">Fulfillment & Shipping Destination</h5>
        <div class="card-body">
          <h6 class="fw-semibold mb-1">Customer Delivery Address</h6>
          @if(is_array($order->shipping_address))
            <p class="mb-0">{{ $order->shipping_address['street'] ?? '' }}</p>
            <p class="mb-0">{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['postal_code'] ?? '' }}</p>
            <p class="mb-0">{{ $order->shipping_address['country'] ?? '' }}</p>
          @else
            <p class="text-muted">Standard Customer Delivery</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <h5 class="card-header">Vendor Financial Breakdown</h5>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Your Gross Sales</span>
            <span>${{ number_format($vendorSubtotal, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2 text-danger">
            <span>Commission ({{ $vendor->commission_rate }}%)</span>
            <span>-${{ number_format($vendorSubtotal - $vendorEarnings, 2) }}</span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fs-5 fw-bold">Your Net Earnings</span>
            <span class="fs-4 fw-bold text-success">+${{ number_format($vendorEarnings, 2) }}</span>
          </div>
          <div class="alert alert-light border py-2 mb-0">
            <small class="text-muted d-block">Payment Status: <strong class="text-uppercase">{{ $order->payment_status }}</strong></small>
            <small class="text-muted d-block">Order Status: <strong class="text-uppercase">{{ $order->status }}</strong></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

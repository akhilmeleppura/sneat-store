@extends('layouts/layoutFront')

@section('title', 'Order Receipt - #' . $order->order_number)

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-0">Order #{{ $order->order_number }}</h3>
      <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y \a\t H:i') }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if(in_array($order->status, ['delivered', 'completed']))
        <a href="{{ route('account.rma.index') }}" class="btn btn-outline-warning">
          <i class="bx bx-revision me-1"></i> Request Return (RMA)
        </a>
      @endif
      <a href="{{ route('account.orders.invoice', $order->order_number) }}" target="_blank" class="btn btn-outline-primary">
        <i class="bx bx-printer me-1"></i> Print Invoice
      </a>
      <a href="{{ route('account.orders.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to Orders
      </a>
    </div>
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

  @php
    $shipment = $order->latestShipment;
  @endphp

  <!-- Order & Shipment Tracking Progress Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
          <span class="text-muted small">DELIVERY STATUS</span>
          <h5 class="fw-bold mb-0 text-heading">
            @if($shipment)
              <span class="badge {{ $shipment->status_badge_class }} fs-6">{{ $shipment->status_label }}</span>
            @else
              <span class="badge bg-label-secondary fs-6 text-uppercase">{{ $order->fulfillment_status }}</span>
            @endif
          </h5>
        </div>
        <div class="text-end">
          @if($shipment && $shipment->tracking_number)
            <span class="text-muted small d-block">TRACKING NUMBER</span>
            @if($shipment->tracking_url)
              <a href="{{ $shipment->tracking_url }}" target="_blank" class="fw-bold text-primary">
                <i class="bx bx-link-external me-1"></i>{{ $shipment->tracking_number }}
              </a>
            @else
              <code class="fw-bold fs-6">{{ $shipment->tracking_number }}</code>
            @endif
            <small class="text-muted d-block">via {{ $shipment->carrier ?: 'Courier' }}</small>
          @elseif($order->shippingMethod)
            <span class="text-muted small d-block">DELIVERY METHOD</span>
            <span class="fw-semibold text-heading">{{ $order->shippingMethod->name }} ({{ $order->shippingMethod->carrier }})</span>
          @endif
        </div>
      </div>

      @if($order->estimated_delivery_date)
        <div class="alert alert-light border d-flex align-items-center py-2 mb-3">
          <i class="bx bx-calendar-check text-primary fs-4 me-2"></i>
          <div>
            <small class="text-muted d-block">Estimated Arrival Date</small>
            <strong class="text-heading">{{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('l, F d, Y') }}</strong>
          </div>
        </div>
      @endif

      @if($shipment && !empty($shipment->timeline))
        <div class="border-top pt-3 mt-2">
          <h6 class="fw-bold mb-3"><i class="bx bx-time-five me-1 text-primary"></i> Tracking Updates</h6>
          <ul class="list-unstyled mb-0 ps-2">
            @foreach(array_reverse($shipment->timeline) as $event)
              <li class="border-start ps-3 pb-3 position-relative" style="border-left: 2px solid #696cff !important;">
                <span class="position-absolute translate-middle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                      style="width: 18px; height: 18px; left: -1px; top: 6px;">
                  <i class="bx bx-check" style="font-size: 11px;"></i>
                </span>
                <div class="d-flex justify-content-between">
                  <strong class="text-capitalize small text-heading">{{ str_replace('_', ' ', $event['status'] ?? '') }}</strong>
                  <small class="text-muted">{{ isset($event['timestamp']) ? \Carbon\Carbon::parse($event['timestamp'])->format('M d, g:i A') : '' }}</small>
                </div>
                <p class="small text-muted mb-0">{{ $event['description'] ?? '' }}</p>
                @if(!empty($event['location']))
                  <small class="text-secondary"><i class="bx bx-map-pin me-1"></i>{{ $event['location'] }}</small>
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>

  <div class="row g-4">
    <!-- Line Items Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <h5 class="card-header bg-transparent fw-bold">Order Items</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Line Total</th>
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
                  <td><span class="fw-bold">${{ number_format($item->line_total, 2) }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Shipping & Delivery Address -->
      <div class="card border-0 shadow-sm">
        <h5 class="card-header bg-transparent fw-bold">Delivery Details</h5>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="fw-semibold mb-1">Customer</h6>
              <p class="mb-0">{{ $order->customer_name }}</p>
              <p class="mb-0 text-muted">{{ $order->customer_email }}</p>
              <p class="mb-0 text-muted">{{ $order->customer_phone ?? 'No phone' }}</p>
            </div>
            <div class="col-md-6">
              <h6 class="fw-semibold mb-1">Shipping Address</h6>
              @if(is_array($order->shipping_address))
                <p class="mb-0">{{ $order->shipping_address['street'] ?? '' }}</p>
                <p class="mb-0">{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['postal_code'] ?? '' }}</p>
                <p class="mb-0">{{ $order->shipping_address['country'] ?? '' }}</p>
              @else
                <p class="text-muted">Standard Delivery</p>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial Breakdown & Receipt Summary -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <h5 class="card-header bg-transparent fw-bold">Payment Summary</h5>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span>${{ number_format($order->subtotal, 2) }}</span>
          </div>
          @if($order->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2 text-success">
              <span>Discount</span>
              <span>-${{ number_format($order->discount_amount, 2) }}</span>
            </div>
          @endif
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Taxes</span>
            <span>${{ number_format($order->tax_amount, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Shipping</span>
            <span>${{ number_format($order->shipping_amount, 2) }}</span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fs-5 fw-bold">Grand Total</span>
            <span class="fs-4 fw-bold text-primary">${{ number_format($order->grand_total, 2) }}</span>
          </div>

          <div class="alert alert-light border py-2 mb-0">
            <small class="text-muted d-block">Order Status: <strong class="text-uppercase">{{ $order->status }}</strong></small>
            <small class="text-muted d-block">Payment Method: <strong class="text-uppercase">{{ $order->payment_method }}</strong></small>
            <small class="text-muted d-block">Payment Status: <strong class="text-uppercase">{{ $order->payment_status }}</strong></small>
            @if(!empty($order->metadata['invoice_number']))
              <small class="text-success d-block fw-semibold mt-1"><i class="bx bx-check-circle me-1"></i>Invoice #{{ $order->metadata['invoice_number'] }}</small>
            @endif
          </div>

          @if($order->status === 'cancelled')
            <div class="alert alert-danger mt-3 mb-0" role="alert">
              <h6 class="alert-heading fw-bold mb-1"><i class="bx bx-x-circle me-1"></i> Order Cancelled</h6>
              <small>{{ $order->metadata['cancellation_reason'] ?? 'This order was cancelled.' }}</small>
            </div>
          @elseif(!empty($order->metadata['return_requested']))
            <div class="alert alert-warning mt-3 mb-0" role="alert">
              <h6 class="alert-heading fw-bold mb-1"><i class="bx bx-time-five me-1"></i> Return Requested</h6>
              <small>{{ $order->metadata['return_reason'] ?? 'Return requested and under review.' }}</small>
            </div>
          @elseif(in_array($order->status, ['pending', 'processing']))
            <button type="button" class="btn btn-outline-danger w-100 mt-3" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
              <i class="bx bx-x-circle me-1"></i> Cancel Order
            </button>
          @elseif(in_array($order->status, ['completed', 'delivered']))
            <button type="button" class="btn btn-outline-warning w-100 mt-3" data-bs-toggle="modal" data-bs-target="#returnOrderModal">
              <i class="bx bx-undo me-1"></i> Request Return / Refund
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>
  </div>
</section>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('account.orders.cancel', $order->order_number) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger"><i class="bx bx-error-circle me-1"></i> Cancel Order #{{ $order->order_number }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Are you sure you want to cancel this order? Any reserved inventory will be automatically released.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Cancellation <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Please let us know why you need to cancel..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Order</button>
          <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Return Order Modal -->
<div class="modal fade" id="returnOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('account.orders.return', $order->order_number) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-undo me-1 text-warning"></i> Request Return / Refund</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Please tell us why you are returning this order. Our customer care team will review and contact you with return shipping instructions.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Describe the issue (e.g. damaged item, wrong size, changed mind)..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-warning">Submit Return Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

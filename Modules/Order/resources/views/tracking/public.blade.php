@extends('layouts/layoutFront')

@section('title', isset($order) ? 'Track Order #' . $order->order_number : 'Track Your Order — Sneat Store')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <!-- Header -->
    <div class="text-center mb-5">
      <span class="badge bg-label-primary px-3 py-1 mb-2">Shipment Tracking</span>
      <h2 class="fw-bold mb-2">Track Your Order Status</h2>
      <p class="text-muted">Enter your Order Number and Billing Email address below to check live shipping status and tracking updates.</p>
    </div>

    <!-- Search Form Card -->
    <div class="row justify-content-center mb-5">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-3 p-md-4">
          <form action="{{ route('order.track.search') }}" method="POST">
            @csrf
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold">Order Number</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bx bx-receipt"></i></span>
                  <input type="text" name="order_number" class="form-control" placeholder="e.g. ORD-2026-..." value="{{ old('order_number', $order->order_number ?? request('order_number')) }}" required>
                </div>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Billing Email</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                  <input type="email" name="email" class="form-control" placeholder="your-email@example.com" value="{{ old('email', $order->customer_email ?? request('email')) }}" required>
                </div>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2">
                  <i class="bx bx-search me-1"></i> Track
                </button>
              </div>
            </div>
          </form>

          @if(session('error'))
            <div class="alert alert-danger alert-dismissible mt-3 mb-0" role="alert">
              <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Order Tracking Result -->
    @if(isset($order))
      @php
        $shipment = $order->latestShipment;
        $status = strtolower($order->status);
        $fulfillment = strtolower($order->fulfillment_status);

        // Calculate step progression
        // Step 1: Confirmed (always completed if order exists)
        // Step 2: Processing / Packing
        // Step 3: Shipped / In Transit
        // Step 4: Out for Delivery
        // Step 5: Delivered
        $currentStep = 1;
        if ($fulfillment === 'fulfilled' || $status === 'completed') {
            $currentStep = 5;
        } elseif ($shipment) {
            $shipStatus = strtolower($shipment->status);
            if ($shipStatus === 'delivered') $currentStep = 5;
            elseif ($shipStatus === 'out_for_delivery') $currentStep = 4;
            elseif ($shipStatus === 'in_transit' || $shipStatus === 'shipped') $currentStep = 3;
            else $currentStep = 2;
        } elseif (in_array($status, ['processing'])) {
            $currentStep = 2;
        }
      @endphp

      <div class="row justify-content-center">
        <div class="col-lg-10">
          <!-- Stepper Card -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3">
              <div>
                <h4 class="fw-bold mb-1">Order #{{ $order->order_number }}</h4>
                <small class="text-muted">Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</small>
              </div>
              <div class="text-end">
                <span class="badge bg-label-primary fs-6 text-uppercase px-3 py-2">{{ $order->status }}</span>
              </div>
            </div>

            <div class="card-body py-4">
              <!-- Visual Stepper Progress Bar -->
              <div class="position-relative m-4">
                <div class="progress" style="height: 4px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: {{ ($currentStep - 1) * 25 }}%;"></div>
                </div>
                <div class="d-flex justify-content-between position-absolute top-0 start-0 w-100 translate-middle-y">
                  <!-- Step 1 -->
                  <div class="text-center" style="width: 80px; margin-left: -40px;">
                    <div class="btn btn-sm btn-icon rounded-circle {{ $currentStep >= 1 ? 'btn-primary' : 'btn-light border' }} mx-auto mb-1">
                      <i class="bx bx-check"></i>
                    </div>
                    <small class="d-block fw-semibold text-nowrap">Confirmed</small>
                  </div>

                  <!-- Step 2 -->
                  <div class="text-center" style="width: 80px;">
                    <div class="btn btn-sm btn-icon rounded-circle {{ $currentStep >= 2 ? 'btn-primary' : 'btn-light border' }} mx-auto mb-1">
                      <i class="bx bx-package"></i>
                    </div>
                    <small class="d-block fw-semibold text-nowrap">Processing</small>
                  </div>

                  <!-- Step 3 -->
                  <div class="text-center" style="width: 80px;">
                    <div class="btn btn-sm btn-icon rounded-circle {{ $currentStep >= 3 ? 'btn-primary' : 'btn-light border' }} mx-auto mb-1">
                      <i class="bx bx-paper-plane"></i>
                    </div>
                    <small class="d-block fw-semibold text-nowrap">Dispatched</small>
                  </div>

                  <!-- Step 4 -->
                  <div class="text-center" style="width: 80px;">
                    <div class="btn btn-sm btn-icon rounded-circle {{ $currentStep >= 4 ? 'btn-primary' : 'btn-light border' }} mx-auto mb-1">
                      <i class="bx bx-cycling"></i>
                    </div>
                    <small class="d-block fw-semibold text-nowrap">Out for Delivery</small>
                  </div>

                  <!-- Step 5 -->
                  <div class="text-center" style="width: 80px; margin-right: -40px;">
                    <div class="btn btn-sm btn-icon rounded-circle {{ $currentStep >= 5 ? 'btn-success' : 'btn-light border' }} mx-auto mb-1">
                      <i class="bx bx-home-heart"></i>
                    </div>
                    <small class="d-block fw-semibold text-nowrap">Delivered</small>
                  </div>
                </div>
              </div>

              <!-- Carrier Info Box -->
              <div class="row g-3 mt-5 pt-3">
                <div class="col-md-6">
                  <div class="p-3 bg-body-tertiary rounded">
                    <small class="text-muted d-block text-uppercase fw-semibold">Shipping Carrier & Method</small>
                    <h6 class="fw-bold mb-1 mt-1 text-heading">
                      {{ $order->shippingMethod?->name ?? 'Standard Delivery' }}
                      @if($shipment && $shipment->carrier)
                        <span class="badge bg-label-info ms-1">{{ $shipment->carrier }}</span>
                      @endif
                    </h6>
                    @if($order->estimated_delivery_date)
                      <small class="text-primary"><i class="bx bx-calendar-event me-1"></i>Est. Arrival: {{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('M d, Y') }}</small>
                    @endif
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-3 bg-body-tertiary rounded">
                    <small class="text-muted d-block text-uppercase fw-semibold">Tracking Number</small>
                    @if($shipment && $shipment->tracking_number)
                      <div class="d-flex align-items-center justify-content-between mt-1">
                        <code class="fs-6 fw-bold text-primary">{{ $shipment->tracking_number }}</code>
                        @if($shipment->tracking_url)
                          <a href="{{ $shipment->tracking_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-link-external me-1"></i> Track on Carrier
                          </a>
                        @endif
                      </div>
                    @else
                      <span class="text-muted small">Tracking number will be assigned once dispatched.</span>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Shipment Timeline Events -->
              @if($shipment && !empty($shipment->timeline))
                <div class="mt-4 pt-3 border-top">
                  <h6 class="fw-bold mb-3"><i class="bx bx-history me-1 text-primary"></i> Activity Log</h6>
                  <ul class="list-unstyled mb-0 ps-2">
                    @foreach(array_reverse($shipment->timeline) as $event)
                      <li class="border-start ps-3 pb-3 position-relative" style="border-left: 2px solid #696cff !important;">
                        <span class="position-absolute translate-middle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 16px; height: 16px; left: -1px; top: 6px;">
                          <i class="bx bx-check" style="font-size: 10px;"></i>
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

          <!-- Items in this Order -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold border-bottom">Order Items ({{ $order->items->count() }})</div>
            <div class="table-responsive text-nowrap">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->items as $item)
                    <tr>
                      <td>
                        <span class="fw-semibold text-heading">{{ $item->product_name }}</span>
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
        </div>
      </div>
    @endif
  </div>
</section>
@endsection

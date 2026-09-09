@extends('layouts/layoutMaster')

@section('title', 'Order Details - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Orders /</span> #{{ $order->order_number }}</h4>
      <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y \a\t H:i') }}</small>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Orders
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Left Column: Line items and addresses -->
    <div class="col-lg-8 mb-4">
      <!-- Order Items Card -->
      <div class="card mb-4">
        <h5 class="card-header">Order Items ({{ $order->items->count() }})</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $item)
                <tr>
                  <td>
                    <span class="fw-semibold text-body d-block">{{ $item->product_name }}</span>
                    <small class="text-muted">SKU: {{ $item->variant_sku }}</small>
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

      <!-- Shipping & Customer Details Card -->
      <div class="card mb-4">
        <h5 class="card-header">Customer & Delivery Information</h5>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="fw-semibold mb-1">Customer Details</h6>
              <p class="mb-0">{{ $order->customer_name }}</p>
              <p class="mb-0 text-muted">{{ $order->customer_email }}</p>
              <p class="mb-0 text-muted">{{ $order->customer_phone ?? 'No phone provided' }}</p>
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
          @if($order->notes)
            <hr class="my-3">
            <h6 class="fw-semibold mb-1">Customer Notes</h6>
            <p class="mb-0 text-muted">{{ $order->notes }}</p>
          @endif
        </div>
      </div>

      <!-- Fulfillment & Shipment Tracking Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bx bx-package me-1 text-primary"></i> Fulfillment & Shipment Tracking ({{ $order->shipments->count() }})</h5>
          <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newShipmentModal">
            <i class="bx bx-plus me-1"></i> New Shipment
          </button>
        </div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <span class="text-muted d-block small">SHIPPING METHOD</span>
              <strong class="text-heading">{{ $order->shippingMethod?->name ?? 'Standard Shipping' }}</strong>
              <small class="text-muted d-block">Carrier: {{ $order->shippingMethod?->carrier ?? 'Standard' }}</small>
            </div>
            <div class="col-md-6">
              <span class="text-muted d-block small">ESTIMATED DELIVERY</span>
              <strong class="text-heading">
                {{ $order->estimated_delivery_date ? \Carbon\Carbon::parse($order->estimated_delivery_date)->format('M d, Y') : 'Pending Dispatch' }}
              </strong>
            </div>
          </div>

          @if($order->shipments->isNotEmpty())
            <div class="table-responsive text-nowrap border rounded">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Shipment #</th>
                    <th>Carrier</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->shipments as $shp)
                    <tr>
                      <td>
                        <a href="{{ route('admin.shipments.show', $shp->id) }}" class="fw-semibold text-primary">
                          {{ $shp->shipment_number }}
                        </a>
                      </td>
                      <td>{{ $shp->carrier ?: 'Standard' }}</td>
                      <td>
                        @if($shp->tracking_number)
                          <code>{{ $shp->tracking_number }}</code>
                        @else
                          <span class="text-muted small">Not set</span>
                        @endif
                      </td>
                      <td><span class="badge {{ $shp->status_badge_class }}">{{ $shp->status_label }}</span></td>
                      <td class="text-end">
                        <a href="{{ route('admin.shipments.show', $shp->id) }}" class="btn btn-xs btn-outline-info">
                          <i class="bx bx-show me-1"></i> Track
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0 small">No shipment records generated for this order yet.</p>
          @endif
        </div>
      </div>

      <!-- Payment Transactions Card -->
      <div class="card mb-4">
        <h5 class="card-header d-flex justify-content-between align-items-center">
          <span>Payment Transactions ({{ $order->paymentTransactions->count() }})</span>
          <a href="{{ route('admin.payments.index') }}?search={{ $order->order_number }}" class="btn btn-xs btn-outline-primary">View in Ledger</a>
        </h5>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Gateway</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($order->paymentTransactions as $txn)
                <tr>
                  <td><code>{{ $txn->transaction_reference }}</code></td>
                  <td><span class="badge bg-label-info">{{ strtoupper($txn->gateway) }}</span></td>
                  <td>${{ number_format($txn->amount, 2) }} {{ $txn->currency }}</td>
                  <td>{!! $txn->status_badge !!}</td>
                  <td>{{ $txn->created_at->format('M d, Y H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-3 text-muted">No gateway transactions recorded for this order yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Financial Settlement & General Ledger Audit Card -->
      <div class="card mb-4">
        <h5 class="card-header d-flex justify-content-between align-items-center">
          <span>Enterprise Financial Settlement</span>
          @if(!empty($order->metadata['invoice_id']))
            <span class="badge bg-label-success"><i class="bx bx-check-double me-1"></i> Fully Settled</span>
          @else
            <span class="badge bg-label-warning"><i class="bx bx-time-five me-1"></i> Awaiting Settlement</span>
          @endif
        </h5>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="d-flex align-items-center p-3 border rounded">
                <div class="avatar avatar-md me-3 bg-label-primary rounded d-flex align-items-center justify-content-center">
                  <i class="bx bx-receipt fs-3"></i>
                </div>
                <div>
                  <h6 class="mb-0">Billing Module Invoice</h6>
                  @if(!empty($order->metadata['invoice_number']))
                    <p class="mb-0 text-success fw-bold">{{ $order->metadata['invoice_number'] }}</p>
                    <small class="text-muted">Status: Official Paid Invoice</small>
                  @else
                    <p class="mb-0 text-muted">Not yet generated</p>
                    <small class="text-muted">Generates automatically upon payment</small>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="d-flex align-items-center p-3 border rounded">
                <div class="avatar avatar-md me-3 bg-label-info rounded d-flex align-items-center justify-content-center">
                  <i class="bx bx-book fs-3"></i>
                </div>
                <div>
                  <h6 class="mb-0">General Ledger Journal</h6>
                  @if(!empty($order->metadata['journal_number']))
                    <p class="mb-0 text-info fw-bold">{{ $order->metadata['journal_number'] }}</p>
                    <small class="text-muted">Double-Entry Bookkeeping Posted</small>
                  @else
                    <p class="mb-0 text-muted">Not yet posted</p>
                    <small class="text-muted">Debits Cash/Bank & Credits Revenue</small>
                  @endif
                </div>
              </div>
            </div>
          </div>

          @if(empty($order->metadata['invoice_id']) && $order->payment_status !== 'paid')
            <hr class="my-3">
            <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
              @csrf
              <input type="hidden" name="status" value="processing">
              <input type="hidden" name="payment_status" value="paid">
              <input type="hidden" name="fulfillment_status" value="fulfilled">
              <button type="submit" class="btn btn-success">
                <i class="bx bx-check-shield me-1"></i> Settle Order Now (Commit Stock, Create Invoice & Post Journal)
              </button>
            </form>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Status updates & Financial Summary -->
    <div class="col-lg-4">
      <!-- Status Management Card -->
      <div class="card mb-4">
        <h5 class="card-header">Order Lifecycle</h5>
        <div class="card-body">
          <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Order Status</label>
              <select name="status" class="form-select">
                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="refunded" {{ $order->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Payment Status</label>
              <select name="payment_status" class="form-select">
                <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="partially_paid" {{ $order->payment_status === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Fulfillment Status</label>
              <select name="fulfillment_status" class="form-select">
                <option value="unfulfilled" {{ $order->fulfillment_status === 'unfulfilled' ? 'selected' : '' }}>Unfulfilled (Reserved Stock)</option>
                <option value="fulfilled" {{ $order->fulfillment_status === 'fulfilled' ? 'selected' : '' }}>Fulfilled (Commit & Deduct Stock)</option>
                <option value="cancelled" {{ $order->fulfillment_status === 'cancelled' ? 'selected' : '' }}>Cancelled (Release Stock)</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-save me-1"></i> Update Status</button>
          </form>
        </div>
      </div>

      <!-- Payment Breakdown Card -->
      <div class="card">
        <h5 class="card-header">Financial Breakdown</h5>
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
          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fs-5 fw-bold">Grand Total</span>
            <span class="fs-4 fw-bold text-primary">${{ number_format($order->grand_total, 2) }}</span>
          </div>
          <small class="text-muted d-block">Method: <strong>{{ strtoupper($order->payment_method) }}</strong></small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- New Shipment Modal -->
<div class="modal fade" id="newShipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('admin.shipments.create_for_order', $order->id) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title"><i class="bx bx-package me-1 text-primary"></i> Create Fulfillment Shipment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" for="new_carrier">Carrier Name</label>
          <input type="text" id="new_carrier" name="carrier" class="form-control" value="{{ $order->shippingMethod?->carrier ?: 'FedEx' }}">
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_tracking">Initial Tracking Code</label>
          <input type="text" id="new_tracking" name="tracking_number" class="form-control" placeholder="e.g. TRK-12345678">
          <small class="text-muted">If provided, shipment will immediately transition to Dispatched.</small>
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_tracking_url">Public Tracking URL</label>
          <input type="url" id="new_tracking_url" name="tracking_url" class="form-control" placeholder="https://...">
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_est_date">Estimated Delivery Date</label>
          <input type="date" id="new_est_date" name="estimated_delivery_at" class="form-control" value="{{ $order->estimated_delivery_date ? \Carbon\Carbon::parse($order->estimated_delivery_date)->format('Y-m-d') : '' }}">
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_notes">Fulfillment Notes</label>
          <textarea id="new_notes" name="notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Create Shipment</button>
      </div>
    </form>
  </div>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Shipment #' . $shipment->shipment_number . ' - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0">
        <a href="{{ route('admin.shipments.index') }}" class="text-muted fw-light">Shipments /</a> {{ $shipment->shipment_number }}
      </h4>
      <small class="text-muted">
        Created on {{ $shipment->created_at->format('M d, Y h:i A') }}
        @if($shipment->order)
          for Order <a href="{{ route('admin.orders.show', $shipment->order->id) }}" class="fw-semibold">{{ $shipment->order->order_number }}</a>
        @endif
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.shipments.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to Shipments
      </a>
      @if($shipment->status === 'pending' || $shipment->status === 'processing')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dispatchModal">
          <i class="bx bx-send me-1"></i> Dispatch Package
        </button>
      @endif
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
        <i class="bx bx-sync me-1"></i> Update Milestone
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Progress Bar Header Card -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <span class="text-muted small">CURRENT STATUS:</span>
          <span class="badge ms-2 {{ $shipment->status_badge_class }} fs-6">
            {{ $shipment->status_label }}
          </span>
        </div>
        @if($shipment->estimated_delivery_at)
          <div class="text-muted small">
            <i class="bx bx-time-five me-1"></i> Est. Delivery:
            <strong class="text-heading">{{ $shipment->estimated_delivery_at->format('M d, Y') }}</strong>
          </div>
        @endif
      </div>

      <!-- Milestone steps indicator -->
      @php
        $milestoneOrder = [
          'pending' => 1,
          'processing' => 2,
          'dispatched' => 3,
          'in_transit' => 4,
          'out_for_delivery' => 5,
          'delivered' => 6
        ];
        $currentStep = $milestoneOrder[$shipment->status] ?? 1;
      @endphp

      <div class="row text-center position-relative py-2">
        <div class="col {{ $currentStep >= 1 ? 'text-primary fw-bold' : 'text-muted' }}">
          <i class="bx bx-check-circle fs-3 d-block mb-1"></i>
          <small>Order Placed</small>
        </div>
        <div class="col {{ $currentStep >= 2 ? 'text-primary fw-bold' : 'text-muted' }}">
          <i class="bx bx-package fs-3 d-block mb-1"></i>
          <small>Processing</small>
        </div>
        <div class="col {{ $currentStep >= 3 ? 'text-primary fw-bold' : 'text-muted' }}">
          <i class="bx bx-send fs-3 d-block mb-1"></i>
          <small>Dispatched</small>
        </div>
        <div class="col {{ $currentStep >= 4 ? 'text-primary fw-bold' : 'text-muted' }}">
          <i class="bx bx-trip fs-3 d-block mb-1"></i>
          <small>In Transit</small>
        </div>
        <div class="col {{ $currentStep >= 5 ? 'text-primary fw-bold' : 'text-muted' }}">
          <i class="bx bx-cycling fs-3 d-block mb-1"></i>
          <small>Out for Delivery</small>
        </div>
        <div class="col {{ $currentStep >= 6 ? 'text-success fw-bold' : 'text-muted' }}">
          <i class="bx bx-check-double fs-3 d-block mb-1"></i>
          <small>Delivered</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Left Column: Milestone Activity Timeline -->
    <div class="col-lg-7">
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bx bx-time me-1 text-primary"></i> Tracking History & Timeline</h5>
          <span class="badge bg-label-secondary">{{ count($shipment->timeline ?? []) }} Milestone(s)</span>
        </div>
        <div class="card-body pt-4">
          @if(!empty($shipment->timeline))
            <ul class="timeline mb-0" style="list-style: none; padding-left: 0;">
              @foreach(array_reverse($shipment->timeline) as $event)
                <li class="timeline-item border-start ps-4 pb-4 position-relative" style="border-left: 2px solid #696cff !important;">
                  <span class="position-absolute translate-middle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width: 24px; height: 24px; left: -1px; top: 8px;">
                    <i class="bx bx-check" style="font-size: 14px;"></i>
                  </span>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-heading text-capitalize">{{ str_replace('_', ' ', $event['status'] ?? 'Milestone') }}</span>
                    <small class="text-muted">
                      {{ isset($event['timestamp']) ? \Carbon\Carbon::parse($event['timestamp'])->format('M d, Y h:i A') : '' }}
                    </small>
                  </div>
                  <p class="mb-1 text-secondary">{{ $event['description'] ?? '' }}</p>
                  @if(!empty($event['location']))
                    <small class="text-muted"><i class="bx bx-map-pin me-1"></i>{{ $event['location'] }}</small>
                  @endif
                </li>
              @endforeach
            </ul>
          @else
            <div class="text-center py-4 text-muted">
              <i class="bx bx-info-circle fs-3 mb-2"></i>
              <p class="mb-0">No timeline events recorded yet.</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Shipment Details, Recipient, Order Summary -->
    <div class="col-lg-5">
      <!-- Carrier Card -->
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0"><i class="bx bx-bus me-1 text-primary"></i> Courier & Tracking</h5>
        </div>
        <div class="card-body pt-3">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Carrier Service:</span>
            <strong class="text-heading">{{ $shipment->carrier ?: 'Standard Courier' }}</strong>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Tracking Code:</span>
            <span>
              @if($shipment->tracking_number)
                <code>{{ $shipment->tracking_number }}</code>
              @else
                <span class="text-muted">Not assigned</span>
              @endif
            </span>
          </div>
          @if($shipment->tracking_url)
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Live Tracking:</span>
              <a href="{{ $shipment->tracking_url }}" target="_blank" class="btn btn-xs btn-outline-info">
                <i class="bx bx-link-external me-1"></i> Carrier Portal
              </a>
            </div>
          @endif
          @if($shipment->shipped_at)
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Dispatched At:</span>
              <span>{{ $shipment->shipped_at->format('M d, Y h:i A') }}</span>
            </div>
          @endif
          @if($shipment->delivered_at)
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Delivered At:</span>
              <span class="text-success fw-bold">{{ $shipment->delivered_at->format('M d, Y h:i A') }}</span>
            </div>
          @endif
        </div>
      </div>

      <!-- Recipient Destination Card -->
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0"><i class="bx bx-map me-1 text-primary"></i> Destination & Recipient</h5>
        </div>
        <div class="card-body pt-3">
          <h6 class="mb-1 text-heading">{{ $shipment->recipient_name }}</h6>
          @if(!empty($shipment->delivery_address))
            <p class="text-secondary small mb-2">
              {{ $shipment->delivery_address['street'] ?? '' }}<br>
              {{ $shipment->delivery_address['city'] ?? '' }}, {{ $shipment->delivery_address['state'] ?? '' }} {{ $shipment->delivery_address['postal_code'] ?? '' }}<br>
              <strong>{{ $shipment->delivery_address['country'] ?? '' }}</strong>
            </p>
          @endif
          @if($shipment->notes)
            <div class="alert alert-light border small mb-0">
              <strong>Notes:</strong> {{ $shipment->notes }}
            </div>
          @endif
        </div>
      </div>

      <!-- Parent Order Summary Card -->
      @if($shipment->order)
        <div class="card mb-4">
          <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bx bx-cart me-1 text-primary"></i> Order Items</h5>
            <a href="{{ route('admin.orders.show', $shipment->order->id) }}" class="btn btn-xs btn-outline-primary">
              View Order
            </a>
          </div>
          <div class="card-body pt-3">
            <div class="table-responsive text-nowrap">
              <table class="table table-sm">
                <tbody>
                  @foreach($shipment->order->items as $item)
                    <tr>
                      <td>
                        <span class="fw-semibold text-heading small">{{ $item->product_name }}</span>
                        <br><small class="text-muted">SKU: {{ $item->variant_sku }}</small>
                      </td>
                      <td class="text-end">x{{ $item->quantity }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Dispatch Modal -->
<div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('admin.shipments.dispatch', $shipment->id) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title"><i class="bx bx-send me-1 text-primary"></i> Dispatch Shipment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" for="carrier">Carrier Name <span class="text-danger">*</span></label>
          <input type="text" id="carrier" name="carrier" class="form-control" value="{{ $shipment->carrier ?: 'FedEx' }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="tracking_number">Tracking Code <span class="text-danger">*</span></label>
          <input type="text" id="tracking_number" name="tracking_number" class="form-control" placeholder="e.g. TRK-992384729" value="{{ $shipment->tracking_number }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="tracking_url">Public Tracking URL</label>
          <input type="url" id="tracking_url" name="tracking_url" class="form-control" placeholder="https://track.carrier.com/..." value="{{ $shipment->tracking_url }}">
        </div>
        <div class="mb-3">
          <label class="form-label" for="dispatch_notes">Dispatch Notes</label>
          <textarea id="dispatch_notes" name="notes" class="form-control" rows="2" placeholder="Driver notes or package condition...">{{ $shipment->notes }}</textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Confirm Dispatch</button>
      </div>
    </form>
  </div>
</div>

<!-- Update Status & Milestone Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('admin.shipments.status', $shipment->id) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title"><i class="bx bx-sync me-1 text-primary"></i> Record Milestone / Update Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" for="status">Shipment Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="form-select" required>
            <option value="processing" {{ $shipment->status === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="dispatched" {{ $shipment->status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
            <option value="in_transit" {{ $shipment->status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
            <option value="out_for_delivery" {{ $shipment->status === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
            <option value="delivered" {{ $shipment->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="failed" {{ $shipment->status === 'failed' ? 'selected' : '' }}>Failed Delivery Attempt</option>
            <option value="returned" {{ $shipment->status === 'returned' ? 'selected' : '' }}>Returned to Origin</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="description">Milestone Activity Description</label>
          <input type="text" id="description" name="description" class="form-control" placeholder="e.g. Scanned at regional sorting facility">
        </div>
        <div class="mb-3">
          <label class="form-label" for="location">Current Location / Hub</label>
          <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Chicago, IL Sorting Hub">
        </div>
        <div class="mb-3">
          <label class="form-label" for="update_tracking">Tracking Number</label>
          <input type="text" id="update_tracking" name="tracking_number" class="form-control" value="{{ $shipment->tracking_number }}">
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Update Milestone</button>
      </div>
    </form>
  </div>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Shipments & Tracking - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Orders & Sales /</span> Shipments & Fulfillment</h4>
      <small class="text-muted">Track parcel dispatches, live delivery milestones, and carrier statuses</small>
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

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Total Shipments</span>
              <h4 class="mb-0 my-1">{{ number_format($totalShipments) }}</h4>
              <small class="text-muted">All fulfillment records</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-box fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Pending Dispatch</span>
              <h4 class="mb-0 my-1 text-warning">{{ number_format($pendingShipments) }}</h4>
              <small class="text-muted">Awaiting packaging / courier</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-loader-circle fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">In Transit</span>
              <h4 class="mb-0 my-1 text-info">{{ number_format($inTransitShipments) }}</h4>
              <small class="text-muted">On the way with carrier</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-transfer-alt fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-heading">Delivered</span>
              <h4 class="mb-0 my-1 text-success">{{ number_format($deliveredShipments) }}</h4>
              <small class="text-muted">Successfully fulfilled</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-double fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Shipments Table Card -->
  <div class="card">
    <div class="card-header border-bottom">
      <form method="GET" action="{{ route('admin.shipments.index') }}" class="row g-3 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search shipment #, tracking #, recipient, order #..." value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
            <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
            <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed Attempt</option>
            <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="carrier" class="form-select">
            <option value="">All Carriers</option>
            @foreach($carriersList as $carrier)
              <option value="{{ $carrier }}" {{ request('carrier') === $carrier ? 'selected' : '' }}>{{ $carrier }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm px-3" title="Filter"><i class="bx bx-filter-alt"></i></button>
          <a href="{{ route('admin.shipments.index') }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset"><i class="bx bx-reset"></i></a>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Shipment #</th>
            <th>Order</th>
            <th>Recipient & Destination</th>
            <th>Carrier & Tracking</th>
            <th>Status</th>
            <th>Timeline Date</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($shipments as $shipment)
            <tr>
              <td>
                <a href="{{ route('admin.shipments.show', $shipment->id) }}" class="fw-semibold text-primary">
                  {{ $shipment->shipment_number }}
                </a>
              </td>
              <td>
                @if($shipment->order)
                  <a href="{{ route('admin.orders.show', $shipment->order->id) }}" class="badge bg-label-secondary">
                    {{ $shipment->order->order_number }}
                  </a>
                @else
                  <span class="text-muted">N/A</span>
                @endif
              </td>
              <td>
                <span class="fw-semibold text-heading">{{ $shipment->recipient_name }}</span>
                <br>
                <small class="text-muted">
                  <i class="bx bx-map-pin me-1"></i>{{ $shipment->delivery_address['city'] ?? '' }}{{ !empty($shipment->delivery_address['country']) ? ', ' . $shipment->delivery_address['country'] : '' }}
                </small>
              </td>
              <td>
                <span class="fw-semibold">{{ $shipment->carrier ?: 'Standard' }}</span>
                <br>
                @if($shipment->tracking_number)
                  @if($shipment->tracking_url)
                    <a href="{{ $shipment->tracking_url }}" target="_blank" class="small text-info text-decoration-underline">
                      <i class="bx bx-link-external me-1"></i>{{ $shipment->tracking_number }}
                    </a>
                  @else
                    <code class="small">{{ $shipment->tracking_number }}</code>
                  @endif
                @else
                  <small class="text-muted">Not assigned</small>
                @endif
              </td>
              <td>
                <span class="badge {{ $shipment->status_badge_class }}">
                  {{ $shipment->status_label }}
                </span>
              </td>
              <td>
                @if($shipment->status === 'delivered')
                  <small class="text-success"><i class="bx bx-check me-1"></i>{{ $shipment->delivered_at?->format('M d, Y') }}</small>
                @elseif($shipment->shipped_at)
                  <small class="text-muted"><i class="bx bx-send me-1"></i>{{ $shipment->shipped_at->format('M d, Y') }}</small>
                @else
                  <small class="text-muted"><i class="bx bx-calendar me-1"></i>{{ $shipment->created_at->format('M d, Y') }}</small>
                @endif
              </td>
              <td class="text-end">
                <a href="{{ route('admin.shipments.show', $shipment->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-show me-1"></i> Details
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">
                <div class="text-muted">
                  <i class="bx bx-box fs-1 mb-2"></i>
                  <p class="mb-0">No shipments found.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($shipments->hasPages())
      <div class="card-footer border-top d-flex justify-content-end">
        {{ $shipments->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Back In Stock Alerts & Demand - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog /</span> Back-in-Stock Notifications</h4>
      <small class="text-muted">Monitor unfulfilled shopper demand for out-of-stock inventory and broadcast restock availability</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Customers Waiting</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ number_format($stats['total_waiting']) }}</h4>
              </div>
              <small class="text-warning">Active unnotified leads</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-bell bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Out of Stock Products</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-primary">{{ number_format($stats['unique_products']) }}</h4>
              </div>
              <small class="text-primary">Products with active waitlists</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-package bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Notifications Sent</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['total_notified']) }}</h4>
              </div>
              <small class="text-success">Restock emails dispatched</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-double bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- High Demand Products Restock Actions -->
  @if($productsDemand->isNotEmpty())
    <div class="card mb-4 border-warning">
      <div class="card-header border-bottom bg-label-warning d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 text-dark fw-bold">
          <i class="bx bx-error-alt me-1"></i> High Demand Restock Radar
        </h5>
        <span class="badge bg-warning text-dark">{{ $productsDemand->count() }} Product(s) In Demand</span>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Product</th>
              <th>SKU</th>
              <th>Waiting Buyers</th>
              <th>Quick Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($productsDemand as $product)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                    <div>
                      <a href="{{ route('catalog.products.edit', $product->id) }}" class="fw-bold text-heading">
                        {{ $product->name }}
                      </a>
                    </div>
                  </div>
                </td>
                <td><code>{{ $product->sku }}</code></td>
                <td>
                  <span class="badge bg-label-warning fs-6 px-3 py-1">
                    <i class="bx bx-user me-1"></i> {{ $product->waiting_count }} shoppers waiting
                  </span>
                </td>
                <td>
                  <form action="{{ route('catalog.back_in_stock.notify', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Broadcast restock notification email to all {{ $product->waiting_count }} waiting shoppers?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                      <i class="bx bx-broadcast me-1"></i> Broadcast Restock Alert
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  <!-- All Subscriptions Ledger Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Subscriber Waitlist ({{ $subscriptions->total() }})</h5>
      <form method="GET" action="{{ route('catalog.back_in_stock.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search email, product..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Waiting (Unnotified)</option>
          <option value="notified" {{ request('status') === 'notified' ? 'selected' : '' }}>Notified (Restocked)</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Product</th>
            <th>Customer Contact</th>
            <th>Status</th>
            <th>Subscribed Date</th>
            <th>Notified At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($subscriptions as $sub)
            <tr>
              <td>
                <span class="fw-bold text-heading">{{ $sub->product?->name ?? 'Product #' . $sub->product_id }}</span>
                <br><small class="text-muted">SKU: {{ $sub->product?->sku ?? 'N/A' }}</small>
              </td>
              <td>
                <span class="fw-semibold">{{ $sub->email }}</span>
                @if($sub->phone)
                  <br><small class="text-muted"><i class="bx bx-phone me-1"></i>{{ $sub->phone }}</small>
                @endif
              </td>
              <td>
                @if($sub->is_notified)
                  <span class="badge bg-label-success"><i class="bx bx-check me-1"></i> Notified</span>
                @else
                  <span class="badge bg-label-warning"><i class="bx bx-time me-1"></i> Waiting</span>
                @endif
              </td>
              <td>{{ $sub->created_at->format('M d, Y H:i') }}</td>
              <td>
                @if($sub->notified_at)
                  <span class="text-success small">{{ $sub->notified_at->format('M d, Y H:i') }}</span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                @if(!$sub->is_notified && $sub->product)
                  <form action="{{ route('catalog.back_in_stock.notify', $sub->product_id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline-primary" title="Notify this product group">
                      <i class="bx bx-bell me-1"></i> Notify
                    </button>
                  </form>
                @else
                  <span class="text-muted small"><i class="bx bx-check text-success"></i> Sent</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bx bx-bell-off bx-lg d-block mb-2 text-secondary"></i>
                No back-in-stock alerts found matching criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($subscriptions->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $subscriptions->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

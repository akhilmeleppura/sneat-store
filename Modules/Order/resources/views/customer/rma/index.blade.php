@extends('layouts/layoutFront')

@section('title', 'Returns & Replacements (RMA) - My Account')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="row g-4">
      <!-- Account Sidebar Navigation -->
      <div class="col-lg-3">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body text-center p-4">
            <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
              <span class="fs-2 fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
            <h5 class="fw-bold mb-1">{{ auth()->user()->name }}</h5>
            <small class="text-muted d-block">{{ auth()->user()->email }}</small>
          </div>
          <div class="list-group list-group-flush">
            <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-home-alt me-2"></i> Account Dashboard
            </a>
            <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-package me-2"></i> My Orders
            </a>
            <a href="{{ route('account.rma.index') }}" class="list-group-item list-group-item-action active">
              <i class="bx bx-revision me-2"></i> Returns & RMA
            </a>
            <a href="{{ route('account.loyalty') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-star me-2"></i> Loyalty Points
            </a>
            <a href="{{ route('account.referrals.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-share-alt me-2"></i> Referral Program
            </a>
            <a href="{{ route('account.payment_methods.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-credit-card me-2"></i> Payment Methods
            </a>
            <a href="{{ route('account.notifications') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-bell me-2"></i> Notifications
            </a>
            <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-user me-2"></i> Profile & Settings
            </a>
            <a href="{{ route('store.cart.index') }}" class="list-group-item list-group-item-action">
              <i class="bx bx-cart me-2"></i> Shopping Cart
            </a>
          </div>
        </div>
      </div>

      <!-- Main RMA Content Area -->
      <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <h3 class="fw-bold mb-1">Return Requests & RMA</h3>
            <p class="text-muted mb-0">Track active return authorizations, download return labels, or submit a return request for eligible delivered items.</p>
          </div>
          @if($eligibleOrders->isNotEmpty())
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRmaModal">
              <i class="bx bx-plus me-1"></i> Request a Return
            </button>
          @endif
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <!-- Existing RMA Requests List -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-bottom">
            <h5 class="mb-0 fw-bold">Return History</h5>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>RMA Number</th>
                  <th>Order #</th>
                  <th>Reason</th>
                  <th>Resolution</th>
                  <th>Status</th>
                  <th>Return Tracking</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rmaRequests as $rma)
                  <tr>
                    <td>
                      <span class="badge bg-label-primary fw-bold">{{ $rma->rma_number }}</span>
                    </td>
                    <td>
                      <a href="{{ route('account.orders.show', $rma->order?->order_number ?? $rma->order_id) }}" class="fw-semibold">
                        #{{ $rma->order?->order_number ?? $rma->order_id }}
                      </a>
                    </td>
                    <td>
                      <span>{{ $rma->reason }}</span>
                      <br><small class="text-muted">Item condition: <strong>{{ ucfirst($rma->condition) }}</strong></small>
                    </td>
                    <td>
                      <span class="badge bg-label-info text-capitalize">{{ str_replace('_', ' ', $rma->resolution_type) }}</span>
                    </td>
                    <td>
                      @php
                        $badgeMap = [
                          'pending'      => 'warning',
                          'approved'     => 'primary',
                          'label_issued' => 'info',
                          'received'     => 'secondary',
                          'inspected'    => 'dark',
                          'resolved'     => 'success',
                          'rejected'     => 'danger',
                        ];
                      @endphp
                      <span class="badge bg-label-{{ $badgeMap[$rma->status] ?? 'secondary' }} text-uppercase">
                        {{ str_replace('_', ' ', $rma->status) }}
                      </span>
                    </td>
                    <td>
                      @if($rma->return_tracking_number)
                        <code class="text-primary">{{ $rma->return_tracking_number }}</code>
                      @else
                        <span class="text-muted small">Pending dispatch</span>
                      @endif
                    </td>
                    <td>{{ $rma->created_at->format('M d, Y') }}</td>
                  </tr>
                  @if($rma->admin_notes)
                    <tr class="table-light">
                      <td colspan="7" class="py-2 px-3 small">
                        <i class="bx bx-info-circle text-primary me-1"></i>
                        <strong>Merchant Note:</strong> {{ $rma->admin_notes }}
                      </td>
                    </tr>
                  @endif
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="bx bx-package bx-lg d-block mb-2 text-secondary"></i>
                      You currently have no return authorization requests.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($rmaRequests->hasPages())
            <div class="card-footer d-flex justify-content-end">
              {{ $rmaRequests->links() }}
            </div>
          @endif
        </div>

        <!-- Help Info Banner -->
        <div class="card border-0 bg-label-secondary shadow-none">
          <div class="card-body p-4 d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-white rounded-circle d-flex align-items-center justify-content-center text-primary shadow-sm flex-shrink-0">
              <i class="bx bx-help-circle fs-3"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Return Policy & Guarantee</h6>
              <p class="mb-0 small text-muted">Items in original packaging can be returned within 30 days of delivery. Once received at our fulfillment center, refund or exchange processing takes 2-3 business days.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Return Request Initiation Modal -->
@if($eligibleOrders->isNotEmpty())
<div class="modal fade" id="newRmaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('account.rma.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bx bx-revision me-1 text-primary"></i> Request Return Authorization</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Delivered Order</label>
            <select name="order_id" class="form-select" required>
              <option value="">-- Choose an order --</option>
              @foreach($eligibleOrders as $order)
                <option value="{{ $order->id }}">
                  #{{ $order->order_number }} - ${{ number_format($order->grand_total, 2) }} (Delivered {{ $order->updated_at->format('M d, Y') }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Return</label>
            <select name="reason" class="form-select" required>
              <option value="">-- Select reason --</option>
              <option value="Defective or does not work properly">Defective or does not work properly</option>
              <option value="Received wrong item">Received wrong item</option>
              <option value="Item arrived damaged">Item arrived damaged</option>
              <option value="Does not match product description">Does not match product description</option>
              <option value="Ordered by mistake / changed mind">Ordered by mistake / changed mind</option>
              <option value="Better price found elsewhere">Better price found elsewhere</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Current Item Condition</label>
            <select name="condition" class="form-select" required>
              <option value="unopened">Unopened (Original factory seal intact)</option>
              <option value="opened">Opened (Like new, tested only)</option>
              <option value="defective">Defective (Functional defect)</option>
              <option value="damaged">Damaged (Packaging or physical damage)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Preferred Resolution</label>
            <select name="resolution_type" class="form-select" required>
              <option value="refund">Refund to Original Payment Method</option>
              <option value="store_credit">Store Credit / Gift Card</option>
              <option value="exchange">Replacement / Exchange Item</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-paper-plane me-1"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

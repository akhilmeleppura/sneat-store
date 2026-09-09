@extends('layouts/layoutFront')

@section('title', 'Profile & Settings')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="row g-4">
    <!-- Account Sidebar Navigation -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center p-4">
          <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <span class="fs-2 fw-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
          </div>
          <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
          <small class="text-muted d-block">{{ $user->email }}</small>
        </div>
        <div class="list-group list-group-flush">
          <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-home-alt me-2"></i> Account Dashboard
          </a>
          <a href="{{ route('account.orders.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-package me-2"></i> My Orders
          </a>
          <a href="{{ route('account.rma.index') }}" class="list-group-item list-group-item-action">
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
          <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action active">
            <i class="bx bx-user me-2"></i> Profile & Settings
          </a>
          <a href="{{ route('store.cart.index') }}" class="list-group-item list-group-item-action">
            <i class="bx bx-cart me-2"></i> Shopping Cart
          </a>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="list-group-item list-group-item-action text-danger border-0 w-100 text-start">
              <i class="bx bx-log-out me-2"></i> Log Out
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Profile Form -->
    <div class="col-lg-9">
      <h3 class="fw-bold mb-4">Personal Information</h3>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <form action="{{ route('account.profile.update') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
              <small class="text-muted">Email address is managed through your primary login credentials.</small>
            </div>

            <div class="mb-4">
              <label class="form-label">Contact Phone</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}" placeholder="+1 (555) 000-0000">
            </div>

            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

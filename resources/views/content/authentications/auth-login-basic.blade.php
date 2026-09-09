@php
use Illuminate\Support\Facades\Route;
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login Basic — Sneat Enterprise Commerce')

@section('page-style')
<style>
  :root {
    --auth-primary: #6366f1;
    --auth-violet: #8b5cf6;
    --auth-magenta: #d946ef;
    --auth-cyan: #06b6d4;
  }

  .luxury-auth-canvas {
    background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.28) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(217, 70, 239, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 45% 60%, rgba(6, 182, 212, 0.15) 0%, transparent 50%),
                linear-gradient(140deg, #090818 0%, #110d2c 45%, #18113b 100%);
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .grid-pattern-overlay {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
    opacity: 0.85;
  }

  .ambient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.55;
    pointer-events: none;
    animation: floatOrb 14s ease-in-out infinite alternate;
  }
  .orb-1 {
    width: 480px;
    height: 480px;
    top: -120px;
    left: -100px;
    background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
  }
  .orb-2 {
    width: 520px;
    height: 520px;
    bottom: -150px;
    right: 10%;
    background: radial-gradient(circle, #d946ef 0%, rgba(217, 70, 239, 0) 70%);
    animation-delay: -5s;
  }
  @keyframes floatOrb {
    0% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-28px) scale(1.06); }
    100% { transform: translateY(22px) scale(0.96); }
  }

  .demo-role-btn {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.6rem 0.75rem;
    cursor: pointer;
    transition: all 0.22s ease;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }
  .demo-role-btn:hover {
    border-color: #6366f1;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.12);
  }
  .demo-role-btn.active {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.06);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
  }

  .form-control-luxury {
    border-radius: 0.75rem;
    border: 1.5px solid #e2e8f0;
    padding: 0.7rem 0.95rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
  }
  .form-control-luxury:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.18);
  }

  .btn-luxe-submit {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
    border: none;
    border-radius: 0.75rem;
    color: #ffffff;
    font-weight: 600;
    padding: 0.8rem 1.25rem;
    font-size: 1rem;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 24px -4px rgba(124, 58, 237, 0.45);
  }
  .btn-luxe-submit:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #c026d3 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 30px -4px rgba(124, 58, 237, 0.6);
    color: #ffffff;
  }
</style>
@endsection

@section('content')
<div class="luxury-auth-canvas p-4">
  <div class="ambient-orb orb-1"></div>
  <div class="ambient-orb orb-2"></div>
  <div class="grid-pattern-overlay"></div>

  <div class="card p-sm-5 p-4 shadow-2xl rounded-4 position-relative border-0" style="max-width: 460px; width: 100%; z-index: 2; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(20px);">
    <div class="text-center mb-4">
      <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo text-heading fw-bold fs-4">{{ config('variables.templateName') }}</span>
      </a>
      <h3 class="fw-bold mb-1 text-heading">Welcome Back 👋</h3>
      <p class="text-muted small">Sign in to manage multi-tenant commerce, orders &amp; stores</p>
    </div>

    <!-- Quick Demo Roles -->
    <div class="mb-4 p-3 rounded-3" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 0.72rem;">
          <i class="bx bx-flash text-warning me-1"></i> Quick Demo Roles
        </span>
        <span class="badge bg-label-primary font-monospace" style="font-size: 0.7rem;">Password: password</span>
      </div>

      <div class="row g-2">
        <div class="col-4">
          <div class="demo-role-btn" id="role-admin-basic" onclick="selectDemoRole('admin@sneat.test', 'password', 'role-admin-basic')">
            <span class="avatar avatar-xs rounded-circle bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center">
              <i class="bx bx-crown"></i>
            </span>
            <div>
              <div class="fw-bold small text-dark">Admin</div>
              <div class="text-muted" style="font-size: 0.68rem;">Executive</div>
            </div>
          </div>
        </div>

        <div class="col-4">
          <div class="demo-role-btn" id="role-vendor-basic" onclick="selectDemoRole('vendor@sneat.test', 'password', 'role-vendor-basic')">
            <span class="avatar avatar-xs rounded-circle bg-info bg-opacity-20 text-info d-flex align-items-center justify-content-center">
              <i class="bx bx-store"></i>
            </span>
            <div>
              <div class="fw-bold small text-dark">Vendor</div>
              <div class="text-muted" style="font-size: 0.68rem;">Merchant</div>
            </div>
          </div>
        </div>

        <div class="col-4">
          <div class="demo-role-btn" id="role-customer-basic" onclick="selectDemoRole('customer@sneat.test', 'password', 'role-customer-basic')">
            <span class="avatar avatar-xs rounded-circle bg-success bg-opacity-20 text-success d-flex align-items-center justify-content-center">
              <i class="bx bx-cart"></i>
            </span>
            <div>
              <div class="fw-bold small text-dark">Customer</div>
              <div class="text-muted" style="font-size: 0.68rem;">Shopper</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if (session('status'))
      <div class="alert alert-success alert-dismissible mb-4" role="alert">
        <i class="bx bx-check-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        <i class="bx bx-error-circle me-1"></i> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <form id="formAuthentication" action="{{ route('login') }}" method="POST">
      @csrf
      <div class="mb-3">
        <label for="login-email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group input-group-merge">
          <span class="input-group-text bg-light border-end-0"><i class="bx bx-envelope text-muted"></i></span>
          <input type="email" class="form-control form-control-luxury border-start-0 ps-0" id="login-email" name="email"
            value="admin@sneat.test" required autofocus />
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label fw-semibold" for="login-password">Password</label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="small text-muted text-decoration-none">
              Forgot Password?
            </a>
          @endif
        </div>
        <div class="input-group input-group-merge">
          <span class="input-group-text bg-light border-end-0"><i class="bx bx-lock-alt text-muted"></i></span>
          <input type="password" id="login-password" class="form-control form-control-luxury border-start-0 border-end-0 px-0"
            name="password" value="password" required />
          <span class="input-group-text bg-light border-start-0 cursor-pointer" onclick="togglePasswordVisibility()">
            <i class="bx bx-hide text-muted" id="pwdToggleIcon"></i>
          </span>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="remember-me" name="remember" checked />
          <label class="form-check-label text-muted small" for="remember-me">Keep me logged in</label>
        </div>
        <span class="badge bg-label-success small"><i class="bx bx-check-shield me-1"></i> 256-bit</span>
      </div>

      <button class="btn btn-luxe-submit d-grid w-100 py-2_5 mb-3" type="submit">
        <span>Sign In <i class="bx bx-right-arrow-alt ms-1"></i></span>
      </button>
    </form>

    <div class="text-center mt-3">
      <span class="text-muted small">New to the platform?</span>
      @if (Route::has('register'))
        <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-none small">Create an account</a>
      @endif
    </div>

    <div class="mt-4 pt-3 border-top text-center">
      <a href="{{ route('storefront.home') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="bx bx-arrow-back me-1"></i> Back to Public Storefront
      </a>
    </div>
  </div>
</div>

<script>
function selectDemoRole(email, password, elementId) {
  document.getElementById('login-email').value = email;
  document.getElementById('login-password').value = password;
  
  document.querySelectorAll('.demo-role-btn').forEach(btn => btn.classList.remove('active'));
  const el = document.getElementById(elementId);
  if (el) el.classList.add('active');
}

function togglePasswordVisibility() {
  const pwdInput = document.getElementById('login-password');
  const icon = document.getElementById('pwdToggleIcon');
  if (pwdInput.type === 'password') {
    pwdInput.type = 'text';
    icon.classList.remove('bx-hide');
    icon.classList.add('bx-show');
  } else {
    pwdInput.type = 'password';
    icon.classList.remove('bx-show');
    icon.classList.add('bx-hide');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const adminBtn = document.getElementById('role-admin-basic');
  if (adminBtn) adminBtn.classList.add('active');
});
</script>
@endsection

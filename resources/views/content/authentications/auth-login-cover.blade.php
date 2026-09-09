@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login Cover — Sneat Enterprise Commerce')

@section('page-style')
<style>
  :root {
    --auth-primary: #6366f1;
    --auth-violet: #8b5cf6;
    --auth-magenta: #d946ef;
    --auth-cyan: #06b6d4;
  }

  .luxury-auth-canvas {
    background: radial-gradient(circle at 12% 18%, rgba(99, 102, 241, 0.32) 0%, transparent 45%),
                radial-gradient(circle at 88% 82%, rgba(217, 70, 239, 0.26) 0%, transparent 45%),
                radial-gradient(circle at 45% 55%, rgba(6, 182, 212, 0.18) 0%, transparent 50%),
                linear-gradient(140deg, #070614 0%, #0d0a24 45%, #150f33 100%);
    position: relative;
    overflow: hidden;
    min-height: 100vh;
  }

  /* Dot Grid Pattern Overlay */
  .grid-pattern-overlay {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
    opacity: 0.9;
  }

  /* Animated Ambient Orbs */
  .ambient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(85px);
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
    width: 540px;
    height: 540px;
    bottom: -150px;
    left: 20%;
    background: radial-gradient(circle, #d946ef 0%, rgba(217, 70, 239, 0) 70%);
    animation-delay: -5s;
  }
  .orb-3 {
    width: 420px;
    height: 420px;
    top: 35%;
    right: 25%;
    background: radial-gradient(circle, #06b6d4 0%, rgba(6, 182, 212, 0) 70%);
    animation-delay: -9s;
  }
  @keyframes floatOrb {
    0% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-28px) scale(1.06); }
    100% { transform: translateY(22px) scale(0.96); }
  }

  /* Frosted Glass Cards (Left Showcase) */
  .glass-card {
    background: rgba(255, 255, 255, 0.04);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.15rem;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .glass-card:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.22);
    transform: translateY(-3px);
    box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.2);
  }

  /* Luminous Hero Text */
  .hero-gradient-text {
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 45%, #c7d2fe 80%, #e0e7ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Live Radar Pulse Dot */
  .pulse-dot {
    width: 9px;
    height: 9px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: radarPulse 2s infinite;
  }
  @keyframes radarPulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  /* Right Login Panel Container */
  .auth-form-container {
    background: rgba(14, 11, 33, 0.82);
    backdrop-filter: blur(28px);
    -webkit-backdrop-filter: blur(28px);
    border-left: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: -15px 0 45px rgba(0, 0, 0, 0.5);
  }

  /* Quick Demo Role Cards */
  .demo-role-card {
    background: rgba(255, 255, 255, 0.04);
    border: 1.5px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.85rem;
    padding: 0.65rem 0.8rem;
    cursor: pointer;
    transition: all 0.22s ease;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }
  .demo-role-card:hover {
    border-color: rgba(139, 92, 246, 0.6);
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(139, 92, 246, 0.2);
  }
  .demo-role-card.active {
    border-color: #8b5cf6;
    background: rgba(139, 92, 246, 0.16);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25), 0 8px 20px rgba(139, 92, 246, 0.25);
  }

  /* Dark Luxury Form Controls */
  .form-control-luxe {
    background: rgba(255, 255, 255, 0.05) !important;
    border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
    color: #ffffff !important;
    border-radius: 0.75rem;
    padding: 0.75rem 0.95rem;
    font-size: 0.95rem;
    transition: all 0.22s ease;
  }
  .form-control-luxe::placeholder {
    color: rgba(255, 255, 255, 0.35) !important;
  }
  .form-control-luxe:focus {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 3.5px rgba(139, 92, 246, 0.25) !important;
    color: #ffffff !important;
  }
  .input-group-text-luxe {
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
    color: #94a3b8 !important;
  }

  /* Ultra-Luxe Radiant CTA */
  .btn-luxe-submit {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
    border: none;
    border-radius: 0.75rem;
    color: #ffffff !important;
    font-weight: 600;
    padding: 0.85rem 1.25rem;
    font-size: 1rem;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 24px -4px rgba(139, 92, 246, 0.5);
  }
  .btn-luxe-submit:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #c026d3 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 32px -4px rgba(139, 92, 246, 0.7);
    color: #ffffff !important;
  }
  .btn-luxe-submit:active {
    transform: translateY(0);
  }

  .telemetry-badge {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #34d399;
  }
</style>
@endsection

@section('content')
<div class="luxury-auth-canvas">
  <!-- Dynamic Glowing Orbs -->
  <div class="ambient-orb orb-1"></div>
  <div class="ambient-orb orb-2"></div>
  <div class="ambient-orb orb-3"></div>
  <div class="grid-pattern-overlay"></div>

  <div class="row m-0 min-vh-100 position-relative" style="z-index: 2;">
    
    <!-- Left Hero: Enterprise Architecture & Platform Highlights -->
    <div class="d-none d-lg-flex col-lg-7 col-xl-7 p-5 flex-column justify-content-between text-white position-relative">
      
      <!-- Brand Header -->
      <div class="d-flex align-items-center justify-content-between">
        <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
          <span class="app-brand-logo demo">@include('_partials.macros')</span>
          <span class="fs-4 fw-bold tracking-wide" style="color: #ffffff !important;">{{ config('variables.templateName') }}</span>
          <span class="badge rounded-pill ms-2 px-2.5 py-1 border" style="background: rgba(99, 102, 241, 0.22); border-color: rgba(99, 102, 241, 0.45) !important; color: #c7d2fe;">
            Enterprise 2.0
          </span>
        </a>

        <div class="d-flex align-items-center gap-2 px-3 py-1.5 rounded-pill border" style="background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.14) !important;">
          <span class="pulse-dot"></span>
          <small class="fw-semibold" style="color: #e2e8f0;">Global Cluster: 99.99% Uptime</small>
        </div>
      </div>

      <!-- Main Showcase -->
      <div class="my-auto py-4" style="max-width: 640px;">
        <div class="mb-4">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-3" style="background: rgba(99, 102, 241, 0.16); border: 1px solid rgba(99, 102, 241, 0.35);">
            <i class="bx bx-shield-quarter text-info"></i>
            <span class="small fw-semibold text-info-light">Multi-Tenant • Multi-Store • Multi-Currency</span>
          </div>

          <h1 class="display-5 fw-bold hero-gradient-text mb-3">
            Intelligent Commerce &amp; Vendor Marketplace
          </h1>
          <p class="fs-6 text-white text-opacity-75 leading-relaxed mb-4">
            Engineered with strict tenant schema isolation, atomic inventory reservations, double-entry financial settlement, and automated currency conversions.
          </p>
        </div>

        <!-- 2x2 High-Tech Feature Grid -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6">
            <div class="glass-card p-3 d-flex align-items-center gap-3">
              <div class="avatar flex-shrink-0" style="width: 44px; height: 44px;">
                <span class="avatar-initial rounded-3" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                  <i class="bx bx-buildings fs-4"></i>
                </span>
              </div>
              <div>
                <div class="fw-bold text-white fs-6">Multi-Tenant Scoping</div>
                <small class="text-white-50">Isolated stores, branches &amp; data</small>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="glass-card p-3 d-flex align-items-center gap-3">
              <div class="avatar flex-shrink-0" style="width: 44px; height: 44px;">
                <span class="avatar-initial rounded-3" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                  <i class="bx bx-dollar-circle fs-4"></i>
                </span>
              </div>
              <div>
                <div class="fw-bold text-white fs-6">Global FX Engine</div>
                <small class="text-white-50">Real-time USD, EUR, GBP, JPY rates</small>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="glass-card p-3 d-flex align-items-center gap-3">
              <div class="avatar flex-shrink-0" style="width: 44px; height: 44px;">
                <span class="avatar-initial rounded-3" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                  <i class="bx bx-purchase-tag fs-4"></i>
                </span>
              </div>
              <div>
                <div class="fw-bold text-white fs-6">Coupons &amp; Discounts</div>
                <small class="text-white-50">Spend thresholds &amp; redemption caps</small>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="glass-card p-3 d-flex align-items-center gap-3">
              <div class="avatar flex-shrink-0" style="width: 44px; height: 44px;">
                <span class="avatar-initial rounded-3" style="background: linear-gradient(135deg, #06b6d4, #0284c7); color: white;">
                  <i class="bx bx-line-chart fs-4"></i>
                </span>
              </div>
              <div>
                <div class="fw-bold text-white fs-6">Apex Analytics</div>
                <small class="text-white-50">Real-time GMV, stock &amp; commission</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Live Telemetry Glass Card -->
        <div class="glass-card p-3 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm">
              <span class="avatar-initial rounded-circle bg-success bg-opacity-20 text-success">
                <i class="bx bx-check-double fs-5"></i>
              </span>
            </div>
            <div>
              <div class="small fw-semibold text-white">Automated Financial GL Settlement</div>
              <small class="text-white-50">Double-entry accounting journal posted on order fulfillment</small>
            </div>
          </div>
          <span class="badge telemetry-badge px-2.5 py-1 rounded-pill">
            <i class="bx bx-lock-alt me-1"></i> Idempotent
          </span>
        </div>
      </div>

      <!-- Footer -->
      <div class="d-flex justify-content-between align-items-center text-white-50 small pt-3 border-top border-white border-opacity-10">
        <span>&copy; {{ date('Y') }} {{ config('variables.templateName') }}. All rights reserved.</span>
        <a href="{{ route('storefront.home') }}" class="text-white text-opacity-80 text-decoration-none hover-white">
          <i class="bx bx-store-alt me-1"></i> Visit Public Storefront &rarr;
        </a>
      </div>
    </div>

    <!-- Right Login Column (Luxury Dark Surface) -->
    <div class="d-flex col-12 col-lg-5 col-xl-5 align-items-center justify-content-center p-sm-5 p-4 auth-form-container position-relative">
      <div class="w-100" style="max-width: 440px;">
        
        <!-- Mobile Header Brand -->
        <div class="d-lg-none mb-4 text-center">
          <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
            <span class="app-brand-logo demo">@include('_partials.macros')</span>
            <span class="app-brand-text demo text-white fw-bold fs-4">{{ config('variables.templateName') }}</span>
          </a>
        </div>

        <!-- Form Title -->
        <div class="mb-4">
          <h3 class="fw-bold mb-1 text-white">Welcome Back 👋</h3>
          <p style="color: #94a3b8;">Select a demo role below or enter your credentials to access the portal.</p>
        </div>

        <!-- Interactive 1-Click Role Selector -->
        <div class="mb-4 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
          <div class="d-flex justify-content-between align-items-center mb-2.5">
            <span class="small fw-bold text-uppercase" style="letter-spacing: 0.6px; font-size: 0.72rem; color: #94a3b8;">
              <i class="bx bx-flash text-warning me-1"></i> Quick Demo Roles
            </span>
            <span class="badge border font-monospace" style="background: rgba(99, 102, 241, 0.18); border-color: rgba(99, 102, 241, 0.35) !important; color: #c7d2fe; font-size: 0.7rem;">
              Password: password
            </span>
          </div>

          <div class="row g-2">
            <div class="col-4">
              <div class="demo-role-card" id="role-admin" onclick="selectDemoRole('admin@sneat.test', 'password', 'role-admin')">
                <span class="avatar avatar-xs rounded-circle bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center">
                  <i class="bx bx-crown"></i>
                </span>
                <div>
                  <div class="fw-bold small text-white">Admin</div>
                  <div style="font-size: 0.68rem; color: #94a3b8;">Executive</div>
                </div>
              </div>
            </div>

            <div class="col-4">
              <div class="demo-role-card" id="role-vendor" onclick="selectDemoRole('vendor@sneat.test', 'password', 'role-vendor')">
                <span class="avatar avatar-xs rounded-circle bg-info bg-opacity-20 text-info d-flex align-items-center justify-content-center">
                  <i class="bx bx-store"></i>
                </span>
                <div>
                  <div class="fw-bold small text-white">Vendor</div>
                  <div style="font-size: 0.68rem; color: #94a3b8;">Merchant</div>
                </div>
              </div>
            </div>

            <div class="col-4">
              <div class="demo-role-card" id="role-customer" onclick="selectDemoRole('customer@sneat.test', 'password', 'role-customer')">
                <span class="avatar avatar-xs rounded-circle bg-success bg-opacity-20 text-success d-flex align-items-center justify-content-center">
                  <i class="bx bx-cart"></i>
                </span>
                <div>
                  <div class="fw-bold small text-white">Customer</div>
                  <div style="font-size: 0.68rem; color: #94a3b8;">Shopper</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        @if (session('status'))
          <div class="alert alert-success alert-dismissible mb-4 border-0" role="alert" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7;">
            <i class="bx bx-check-circle me-1"></i> {{ session('status') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger alert-dismissible mb-4 border-0" role="alert" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5;">
            <i class="bx bx-error-circle me-1"></i> {{ $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <!-- Real Authentication Form -->
        <form id="formAuthentication" action="{{ route('login') }}" method="POST">
          @csrf
          
          <div class="mb-3">
            <label for="login-email" class="form-label" style="color: #cbd5e1; font-weight: 500;">Email Address</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text input-group-text-luxe border-end-0"><i class="bx bx-envelope"></i></span>
              <input type="email" class="form-control form-control-luxe border-start-0 ps-0 @error('email') is-invalid @enderror" 
                id="login-email" name="email" placeholder="name@example.com" autofocus value="{{ old('email', 'admin@sneat.test') }}" required />
            </div>
            @error('email')
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0" for="login-password" style="color: #cbd5e1; font-weight: 500;">Password</label>
              @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color: #a78bfa;">
                  Forgot Password?
                </a>
              @endif
            </div>
            <div class="input-group input-group-merge">
              <span class="input-group-text input-group-text-luxe border-end-0"><i class="bx bx-lock-alt"></i></span>
              <input type="password" id="login-password" class="form-control form-control-luxe border-start-0 border-end-0 px-0 @error('password') is-invalid @enderror"
                name="password" value="password" placeholder="••••••••••••" aria-describedby="password" required />
              <span class="input-group-text input-group-text-luxe border-start-0 cursor-pointer" onclick="togglePasswordVisibility()">
                <i class="bx bx-hide" id="pwdToggleIcon"></i>
              </span>
            </div>
            @error('password')
              <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember-me" name="remember" checked style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25);" />
              <label class="form-check-label small" for="remember-me" style="color: #94a3b8;">Keep me logged in</label>
            </div>
            <span class="badge small border" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3) !important; color: #34d399;">
              <i class="bx bx-check-shield me-1"></i> SSL 256-bit
            </span>
          </div>

          <button class="btn btn-luxe-submit d-grid w-100 mb-3" type="submit">
            <span>Sign In to Platform <i class="bx bx-right-arrow-alt ms-1"></i></span>
          </button>
        </form>

        <div class="text-center mt-3">
          <span style="color: #94a3b8;" class="small">New to the platform?</span>
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="fw-semibold text-decoration-none small ms-1" style="color: #a78bfa;">Create an account</a>
          @endif
        </div>

        <div class="mt-4 pt-3 border-top text-center" style="border-color: rgba(255, 255, 255, 0.08) !important;">
          <a href="{{ route('storefront.home') }}" class="btn btn-sm rounded-pill px-3" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.15); color: #cbd5e1;">
            <i class="bx bx-arrow-back me-1"></i> Back to Public Storefront
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function selectDemoRole(email, password, elementId) {
  document.getElementById('login-email').value = email;
  document.getElementById('login-password').value = password;
  
  // Highlight active role card
  document.querySelectorAll('.demo-role-card').forEach(btn => btn.classList.remove('active'));
  const el = document.getElementById(elementId);
  if (el) el.classList.add('active');
  
  // Subtle glow flash
  const emailInput = document.getElementById('login-email');
  emailInput.style.borderColor = '#8b5cf6';
  setTimeout(() => emailInput.style.borderColor = '', 600);
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

// Default select admin
document.addEventListener('DOMContentLoaded', () => {
  const adminBtn = document.getElementById('role-admin');
  if (adminBtn) adminBtn.classList.add('active');
});
</script>
@endsection

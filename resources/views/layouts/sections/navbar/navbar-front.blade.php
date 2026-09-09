@php
use Illuminate\Support\Facades\Route;
$currentRouteName = Route::currentRouteName();
$activeRoutes = ['front-pages-pricing', 'front-pages-payment', 'front-pages-checkout', 'front-pages-help-center'];
$activeClass = in_array($currentRouteName, $activeRoutes) ? 'active' : '';

$cartCount = 0;
$wishlistCount = 0;
try {
    $activeCart = app(\Modules\Cart\Services\CartService::class)->getActiveCart();
    $cartCount = $activeCart ? $activeCart->total_quantity : 0;
    if (auth()->check()) {
        $wishlistCount = app(\Modules\Catalog\Services\WishlistService::class)->getWishlistCount(auth()->id());
    }
} catch (\Throwable $e) {
    $cartCount = 0;
    $wishlistCount = 0;
}
@endphp

@section('vendor-script')
@vite(['resources/assets/vendor/js/dropdown-hover.js', 'resources/assets/vendor/js/mega-dropdown.js'])
@endsection

<!-- Navbar: Start -->
<nav class="layout-navbar shadow-none py-0">
  <div class="container">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
      <!-- Menu logo wrapper: Start -->
      <div class="navbar-brand app-brand demo d-flex py-0 me-4 me-xl-8">
        <!-- Mobile menu toggle: Start-->
        <button class="navbar-toggler border-0 px-0 me-4" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
          aria-label="Toggle navigation">
          <i class="icon-base bx bx-menu icon-lg align-middle text-heading fw-medium"></i>
        </button>
        <!-- Mobile menu toggle: End-->
        <a href="{{ route('storefront.home') }}" class="app-brand-link">
          <span class="app-brand-logo demo">@include('_partials.macros')</span>
          <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">{{ config('variables.templateName') }}</span>
        </a>
      </div>
      <!-- Menu logo wrapper: End -->

      <!-- Menu wrapper: Start -->
      <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
        <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl p-2"
          type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="icon-base bx bx-x icon-lg"></i>
        </button>
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link fw-medium {{ Route::is('storefront.home') ? 'active' : '' }}" aria-current="page"
              href="{{ route('storefront.home') }}">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium {{ Route::is('storefront.catalog*') ? 'active' : '' }}"
              href="{{ route('storefront.catalog') }}">Catalog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium {{ Route::is('store.cart.*') ? 'active' : '' }}"
              href="{{ route('store.cart.index') }}">Cart</a>
          </li>
          @auth
          <li class="nav-item">
            <a class="nav-link fw-medium {{ Route::is('account.*') ? 'active' : '' }}"
              href="{{ route('account.dashboard') }}">My Account</a>
          </li>
          @if(auth()->user()->vendor)
          <li class="nav-item">
            <a class="nav-link fw-medium text-warning" href="{{ route('vendor.dashboard') }}">
              <i class="icon-base bx bx-store-alt me-1"></i> Vendor Hub
            </a>
          </li>
          @endif
          @endauth

          @if(!auth()->check() || !auth()->user()->vendor)
          <li class="nav-item">
            <a class="nav-link fw-medium text-primary {{ Route::is('storefront.vendor.register') ? 'active' : '' }}"
              href="{{ route('storefront.vendor.register') }}">
              <i class="icon-base bx bx-store me-1"></i> Sell on Sneat
            </a>
          </li>
          @endif

          <li class="nav-item mega-dropdown {{ $activeClass }}">
            <a href="javascript:void(0);"
              class="nav-link dropdown-toggle navbar-ex-14-mega-dropdown mega-dropdown fw-medium" aria-expanded="false"
              data-bs-toggle="mega-dropdown" data-trigger="hover">
              <span>Pages</span>
            </a>
            <div class="dropdown-menu p-4 p-xl-8">
              <div class="row gy-4">
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-4">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-primary"><i
                          class="icon-base bx bx-grid-alt"></i></span>
                    </div>
                    <span class="ps-1">Storefront</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('storefront.catalog') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>All Products</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('store.cart.index') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>Shopping Cart</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('store.checkout.index') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>Checkout</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('order.track.page') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>Track Order</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ url('front-pages/pricing') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>Pricing</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ url('front-pages/help-center') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        <span>Help Center</span>
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-4">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-primary"><i
                          class="icon-base bx bx-user icon-lg"></i></span>
                    </div>
                    <span class="ps-1">Customer Portal</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('account.dashboard') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Dashboard
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('account.orders.index') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Order History
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('account.profile') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Profile Settings
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('account.payment_methods.index') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Payment Methods
                      </a>
                    </li>
                    @if(auth()->check() && auth()->user()->vendor)
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link text-warning" href="{{ route('vendor.dashboard') }}">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Vendor Dashboard
                      </a>
                    </li>
                    @endif
                  </ul>
                </div>
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-4">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-primary"><i
                          class="icon-base bx bx-shield-quarter icon-lg"></i></span>
                    </div>
                    <span class="ps-1">Administration</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ url('/admin/dashboard') }}" target="_blank">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Admin Console
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('catalog.products.index') }}" target="_blank">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Catalog Management
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('admin.orders.index') }}" target="_blank">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Order Processing
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{ route('admin.payments.index') }}" target="_blank">
                        <i class="icon-base bx bx-radio-circle me-1"></i>
                        Payment Settlement
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                  <div class="bg-body nav-img-col p-2">
                    <img src="{{ asset('assets/img/front-pages/misc/nav-item-col-img.png') }}" alt="nav item col image"
                      class="w-100" />
                  </div>
                </div>
              </div>
            </div>
          </li>

          @if(auth()->check() && (auth()->user()->is_supreme_admin ?? false))
          <li class="nav-item">
            <a class="nav-link fw-medium text-danger" href="{{ url('/admin/dashboard') }}" target="_blank">
              <i class="icon-base bx bx-shield me-1"></i> Admin
            </a>
          </li>
          @endif
        </ul>
      </div>
      <div class="landing-menu-overlay d-lg-none"></div>
      <!-- Menu wrapper: End -->

      <!-- Toolbar: Start -->
      <ul class="navbar-nav flex-row align-items-center ms-auto">
        <!-- Wishlist Icon with badge -->
        <li class="nav-item me-2">
          <a class="nav-link position-relative d-flex align-items-center p-2" href="{{ route('account.wishlist') }}" title="My Wishlist">
            <i class="icon-base bx bx-heart fs-4 text-heading"></i>
            @if($wishlistCount > 0)
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.3em 0.55em;">
                {{ $wishlistCount }}
              </span>
            @endif
          </a>
        </li>

        <!-- Cart Icon with badge -->
        <li class="nav-item me-3">
          <a class="nav-link position-relative d-flex align-items-center p-2" href="{{ route('store.cart.index') }}" title="Shopping Cart">
            <i class="icon-base bx bx-cart fs-4 text-heading"></i>
            @if($cartCount > 0)
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size: 0.65rem; padding: 0.3em 0.55em;">
                {{ $cartCount }}
              </span>
            @endif
          </a>
        </li>

        @if ($configData['hasCustomizer'] == true)
        <!-- Style Switcher -->
        <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-3">
          <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
            data-bs-toggle="dropdown">
            <i class="icon-base bx bx-sun icon-lg theme-icon-active"></i>
            <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
            <li>
              <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
                aria-pressed="false">
                <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i>Light</span>
              </button>
            </li>
            <li>
              <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark"
                aria-pressed="true">
                <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i>Dark</span>
              </button>
            </li>
            <li>
              <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
                aria-pressed="false">
                <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i>System</span>
              </button>
            </li>
          </ul>
        </li>
        <!-- / Style Switcher-->
        @endif

        <!-- Branch Switcher Dropdown: Start -->
        @php
          $allBranches = \Modules\Context\Models\Branch::withoutGlobalScopes()->where('status', 'active')->get();
          $activeBranch = \Modules\Context\Facades\Context::branch();
        @endphp
        @if($allBranches->isNotEmpty())
        <li class="nav-item dropdown me-2 me-xl-3 d-none d-sm-block">
          <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-1 p-2" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" title="Active Branch Location">
            <i class="icon-base bx bx-buildings text-primary fs-5"></i>
            <span class="d-none d-lg-inline text-heading small fw-semibold text-truncate" style="max-width: 130px;">
              {{ $activeBranch?->name ?? 'Branch' }}
            </span>
            <i class="icon-base bx bx-chevron-down small text-muted"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm py-1" style="min-width: 250px;">
            <li class="dropdown-header text-uppercase small text-muted pb-1 d-flex justify-content-between align-items-center">
              <span>Pickup & Stock Branch</span>
              <a href="{{ route('store.locations') }}" class="text-primary fw-semibold"><small>Map <i class="bx bx-right-arrow-alt"></i></small></a>
            </li>
            @foreach($allBranches as $br)
              <li>
                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $activeBranch && $activeBranch->id === $br->id ? 'active' : '' }}" href="{{ route('branch.switch', $br->id) }}">
                  <div>
                    <span class="fw-semibold d-block text-truncate" style="max-width: 180px;">{{ $br->name }}</span>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $br->city ? $br->city . ', ' . $br->state : $br->code }}</small>
                  </div>
                  @if($activeBranch && $activeBranch->id === $br->id)
                    <i class="bx bx-check text-primary"></i>
                  @endif
                </a>
              </li>
            @endforeach
            <li class="border-top mt-1 pt-1 text-center">
              <a href="{{ route('store.locations') }}" class="dropdown-item text-primary small py-1">
                <i class="bx bx-map me-1"></i> View All Locations on Map
              </a>
            </li>
          </ul>
        </li>
        @endif
        <!-- Branch Switcher Dropdown: End -->

        <!-- Language Switcher Dropdown: Start -->
        @php
          $currentLocale = app()->getLocale();
          $languages = [
            'en' => ['name' => 'English', 'flag' => '🇺🇸'],
            'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
            'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
            'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
          ];
        @endphp
        <li class="nav-item dropdown me-2 me-xl-3">
          <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-1 p-2" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" title="Switch Language">
            <span class="fs-6 me-1">{{ $languages[$currentLocale]['flag'] ?? '🌐' }}</span>
            <span class="text-uppercase small fw-bold text-heading">{{ $currentLocale }}</span>
            <i class="icon-base bx bx-chevron-down small text-muted"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm py-1" style="min-width: 150px;">
            <li class="dropdown-header text-uppercase small text-muted pb-1">Language</li>
            @foreach($languages as $code => $lang)
              <li>
                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $currentLocale === $code ? 'active' : '' }}" href="{{ url('lang/' . $code) }}">
                  <span>{{ $lang['flag'] }} {{ $lang['name'] }}</span>
                  @if($currentLocale === $code)
                    <i class="bx bx-check text-primary"></i>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        </li>
        <!-- Language Switcher Dropdown: End -->

        <!-- Currency Switcher Dropdown: Start -->
        @php
          $allCurrencies = app(\Modules\Context\Services\CurrencyService::class)->getActiveCurrencies();
          $currentCurrency = app(\Modules\Context\Services\CurrencyService::class)->getCurrentCurrency();
        @endphp
        <li class="nav-item dropdown me-2 me-xl-3">
          <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-1 p-2" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" title="Switch Currency">
            <span class="badge bg-label-primary px-2 py-1 fw-bold">
              {{ $currentCurrency->code }} {{ $currentCurrency->symbol }}
            </span>
            <i class="icon-base bx bx-chevron-down small text-muted"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm py-1" style="min-width: 190px;">
            <li class="dropdown-header text-uppercase small text-muted pb-1">Select Currency</li>
            @foreach($allCurrencies as $curr)
              <li>
                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $curr->code === $currentCurrency->code ? 'active' : '' }}" href="{{ route('currency.switch.get', $curr->code) }}">
                  <span class="d-flex align-items-center gap-2">
                    <span class="fw-bold">{{ $curr->symbol }}</span>
                    <span>{{ $curr->code }}</span>
                  </span>
                  <small class="text-muted">{{ $curr->name }}</small>
                </a>
              </li>
            @endforeach
          </ul>
        </li>
        <!-- Currency Switcher Dropdown: End -->

        <!-- Auth Button / Dropdown: Start -->
        @auth
        @php
          $customerUnreadCount = auth()->user()->unreadNotifications()->count();
          $customerRecentNotifications = auth()->user()->notifications()->take(5)->get();
        @endphp
        <!-- Customer Notifications Bell -->
        <li class="nav-item dropdown me-2 me-xl-3">
          <a class="nav-link dropdown-toggle hide-arrow position-relative p-2" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base bx bx-bell fs-4 text-heading"></i>
            @if($customerUnreadCount > 0)
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                {{ $customerUnreadCount }}
              </span>
            @endif
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="width: 320px;">
            <li class="dropdown-header d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
              <h6 class="mb-0 fw-bold">Notifications</h6>
              @if($customerUnreadCount > 0)
                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="m-0">
                  @csrf
                  <button type="submit" class="btn btn-text-secondary btn-sm p-0 border-0 bg-transparent text-primary" title="Mark all read">
                    <small>Mark all read</small>
                  </button>
                </form>
              @endif
            </li>
            <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
              @forelse($customerRecentNotifications as $n)
                @php
                  $nData = $n->data;
                  $isUnread = is_null($n->read_at);
                  $nColor = $nData['color'] ?? 'primary';
                  $nIcon = $nData['icon'] ?? 'bx-bell';
                @endphp
                <a href="{{ $nData['url'] ?? route('account.notifications') }}" class="list-group-item list-group-item-action p-3">
                  <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-label-{{ $nColor }} p-2 rounded-circle">
                      <i class="bx {{ $nIcon }}"></i>
                    </span>
                    <div class="flex-grow-1">
                      <h6 class="mb-0 small {{ $isUnread ? 'fw-bold text-primary' : '' }}">{{ $nData['title'] ?? 'Notice' }}</h6>
                      <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($nData['message'] ?? '', 45) }}</small>
                      <small class="text-secondary" style="font-size: 0.7rem;">{{ $n->created_at->diffForHumans() }}</small>
                    </div>
                  </div>
                </a>
              @empty
                <div class="text-center py-4 text-muted">
                  <small>No notifications</small>
                </div>
              @endforelse
            </div>
            <li class="border-top p-2 text-center">
              <a href="{{ route('account.notifications') }}" class="small fw-semibold text-primary">View All Notifications &rarr;</a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-sm me-2">
              <span class="avatar-initial rounded-circle bg-label-primary fw-bold">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
              </span>
            </div>
            <span class="d-none d-md-inline fw-semibold text-heading">{{ auth()->user()->name }}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.dashboard') }}">
                <i class="icon-base bx bx-user me-2 text-primary"></i> My Account
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.orders.index') }}">
                <i class="icon-base bx bx-package me-2 text-info"></i> My Orders
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.wishlist') }}">
                <i class="icon-base bx bx-heart me-2 text-danger"></i> My Wishlist
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.notifications') }}">
                <i class="icon-base bx bx-bell me-2 text-warning"></i> Notifications
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.profile') }}">
                <i class="icon-base bx bx-cog me-2 text-secondary"></i> Settings
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('account.payment_methods.index') }}">
                <i class="icon-base bx bx-credit-card me-2 text-success"></i> Payment Methods
              </a>
            </li>
            @if(auth()->user()->vendor)
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('vendor.dashboard') }}">
                <i class="icon-base bx bx-store me-2 text-warning"></i> Vendor Hub
              </a>
            </li>
            @else
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('storefront.vendor.register') }}">
                <i class="icon-base bx bx-store me-2 text-warning"></i> Become a Seller
              </a>
            </li>
            @endif
            @if(auth()->user()->is_supreme_admin ?? false)
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/dashboard') }}">
                <i class="icon-base bx bx-shield me-2 text-danger"></i> Admin Console
              </a>
            </li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item d-flex align-items-center text-danger border-0 bg-transparent w-100 text-start">
                  <i class="icon-base bx bx-power-off me-2"></i> Log Out
                </button>
              </form>
            </li>
          </ul>
        </li>
        @else
        <li class="nav-item">
          <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-3">
            <span class="icon-base bx bx-log-in-circle me-1"></span>
            <span>Sign In</span>
          </a>
        </li>
        @endauth
        <!-- Auth Button / Dropdown: End -->
      </ul>
      <!-- Toolbar: End -->
    </div>
  </div>
</nav>
<!-- Navbar: End -->

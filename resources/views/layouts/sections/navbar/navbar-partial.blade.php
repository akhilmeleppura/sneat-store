@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
  <a href="{{ url('/') }}" class="app-brand-link gap-2">
    <span class="app-brand-logo demo">@include('_partials.macros')</span>
    <span class="app-brand-text demo menu-text fw-bold text-heading">{{ config('variables.templateName') }}</span>
  </a>

  <!-- Display menu close icon only for horizontal-menu with navbar-full -->
  @if (isset($menuHorizontal))
  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
    <i class="icon-base bx bx-chevron-left d-flex align-items-center justify-content-center"></i>
  </a>
  @endif
</div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
<div
  class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
  <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
    <i class="icon-base bx bx-menu icon-md"></i>
  </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

  @if (!isset($menuHorizontal))
  <!-- Search -->
  <div class="navbar-nav align-items-center">
    <div class="nav-item navbar-search-wrapper mb-0">
      <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
        <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
      </a>
    </div>
  </div>
  <!-- /Search -->
  @endif

  <ul class="navbar-nav flex-row align-items-center ms-md-auto">
    @if (isset($menuHorizontal))
    <!-- Search -->
    <li class="nav-item navbar-search-wrapper me-2 me-xl-0">
      <a class="nav-link search-toggler px-0" href="javascript:void(0);">
        <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
      </a>
    </li>
    <!-- /Search -->
    @endif

    <!-- Context (Tenant & Store) Switcher -->
    @php
      $navTenant = \Modules\Context\Facades\Context::currentTenant();
      $navStore = \Modules\Context\Facades\Context::currentStore();
      $navAllTenants = \Modules\Context\Models\Tenant::active()->get();
      $navTenantStores = $navTenant ? $navTenant->stores()->active()->get() : collect();
    @endphp
    @if($navAllTenants->isNotEmpty())
    <li class="nav-item dropdown me-2 me-xl-1">
      <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-1" href="javascript:void(0);" data-bs-toggle="dropdown" title="Active Tenant & Store">
        <span class="badge bg-label-info px-2 py-1 fw-bold d-flex align-items-center gap-1">
          <i class="bx bx-store-alt fs-6"></i>
          <span>{{ $navStore ? $navStore->name : ($navTenant ? $navTenant->name : 'Store Context') }}</span>
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end py-2 shadow" style="min-width: 250px;">
        <li class="dropdown-header text-uppercase small text-muted pb-1 d-flex justify-content-between align-items-center">
          <span>Active Context</span>
          @if($navTenant)
            <span class="badge bg-label-primary font-monospace text-lowercase" style="font-size: 10px;">{{ $navTenant->slug }}</span>
          @endif
        </li>

        @if($navAllTenants->count() > 1)
          <li class="px-3 py-1">
            <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px;">Tenants</small>
          </li>
          @foreach($navAllTenants as $t)
            <li>
              <a class="dropdown-item d-flex align-items-center justify-content-between py-1 {{ ($navTenant && $navTenant->id === $t->id) ? 'active' : '' }}" href="{{ route('context.switch.tenant', $t->id) }}">
                <span class="d-flex align-items-center gap-2">
                  <i class="bx bx-buildings"></i>
                  <span>{{ $t->name }}</span>
                </span>
                @if($navTenant && $navTenant->id === $t->id)
                  <i class="bx bx-check text-primary"></i>
                @endif
              </a>
            </li>
          @endforeach
          <li><hr class="dropdown-divider my-1"></li>
        @endif

        @if($navTenantStores->isNotEmpty())
          <li class="px-3 py-1">
            <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px;">Stores ({{ $navTenant->name }})</small>
          </li>
          @foreach($navTenantStores as $s)
            <li>
              <a class="dropdown-item d-flex align-items-center justify-content-between py-1 {{ ($navStore && $navStore->id === $s->id) ? 'active' : '' }}" href="{{ route('context.switch.store', $s->id) }}">
                <span class="d-flex align-items-center gap-2">
                  <i class="bx bx-store"></i>
                  <span>{{ $s->name }}</span>
                </span>
                @if($navStore && $navStore->id === $s->id)
                  <i class="bx bx-check text-success"></i>
                @endif
              </a>
            </li>
          @endforeach
          <li><hr class="dropdown-divider my-1"></li>
        @endif

        <li>
          <a class="dropdown-item text-primary small py-1" href="{{ route('admin.tenant.settings') }}">
            <i class="bx bx-slider me-1"></i> Tenant & Store Settings
          </a>
        </li>
      </ul>
    </li>
    @endif
    <!--/ Context Switcher -->

    <!-- Currency Switcher -->
    @php
      $adminAllCurrencies = app(\Modules\Context\Services\CurrencyService::class)->getActiveCurrencies();
      $adminCurrentCurrency = app(\Modules\Context\Services\CurrencyService::class)->getCurrentCurrency();
    @endphp
    <li class="nav-item dropdown me-2 me-xl-1">
      <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-1" href="javascript:void(0);" data-bs-toggle="dropdown" title="Active Currency">
        <span class="badge bg-label-primary px-2 py-1 fw-bold">
          {{ $adminCurrentCurrency->code }} {{ $adminCurrentCurrency->symbol }}
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end py-1" style="min-width: 190px;">
        <li class="dropdown-header text-uppercase small text-muted pb-1">Currency</li>
        @foreach($adminAllCurrencies as $c)
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $c->code === $adminCurrentCurrency->code ? 'active' : '' }}" href="{{ route('currency.switch.get', $c->code) }}">
              <span class="d-flex align-items-center gap-2">
                <span class="fw-bold">{{ $c->symbol }}</span>
                <span>{{ $c->code }}</span>
              </span>
              <small class="text-muted">{{ $c->name }}</small>
            </a>
          </li>
        @endforeach
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item text-primary small py-1" href="{{ route('admin.currencies.index') }}">
            <i class="bx bx-cog me-1"></i> Manage FX Rates
          </a>
        </li>
      </ul>
    </li>
    <!--/ Currency Switcher -->

    <!-- Language -->
    <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="icon-base bx bx-globe icon-md"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ url('lang/en') }}"
            data-language="en" data-text-direction="ltr">
            <span>English</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ url('lang/fr') }}"
            data-language="fr" data-text-direction="ltr">
            <span>French</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" href="{{ url('lang/ar') }}"
            data-language="ar" data-text-direction="rtl">
            <span>Arabic</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'de' ? 'active' : '' }}" href="{{ url('lang/de') }}"
            data-language="de" data-text-direction="ltr">
            <span>German</span>
          </a>
        </li>
      </ul>
    </li>
    <!--/ Language -->

    @if ($configData['hasCustomizer'] == true)
    <!-- Style Switcher -->
    <li class="nav-item dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
        data-bs-toggle="dropdown">
        <i class="icon-base bx bx-sun icon-md theme-icon-active"></i>
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
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
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

    <!-- Quick links  -->
    <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false">
        <i class="icon-base bx bx-grid-alt icon-md"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-end p-0">
        <div class="dropdown-menu-header border-bottom">
          <div class="dropdown-header d-flex align-items-center py-3">
            <h6 class="mb-0 me-auto">Shortcuts</h6>
            <a href="javascript:void(0)" class="dropdown-shortcuts-add py-2" data-bs-toggle="tooltip"
              data-bs-placement="top" title="Add shortcuts"><i class="icon-base bx bx-plus-circle text-heading"></i></a>
          </div>
        </div>
        <div class="dropdown-shortcuts-list scrollable-container">
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-calendar icon-26px text-heading"></i>
              </span>
              <a href="{{ url('app/calendar') }}" class="stretched-link">Calendar</a>
              <small>Appointments</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-food-menu icon-26px text-heading"></i>
              </span>
              <a href="{{ url('app/invoice/list') }}" class="stretched-link">Invoice App</a>
              <small>Manage Accounts</small>
            </div>
          </div>
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-user icon-26px text-heading"></i>
              </span>
              <a href="{{ url('app/user/list') }}" class="stretched-link">User App</a>
              <small>Manage Users</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-check-shield icon-26px text-heading"></i>
              </span>
              <a href="{{ url('app/access-roles') }}" class="stretched-link">Role Management</a>
              <small>Permission</small>
            </div>
          </div>
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-pie-chart-alt-2 icon-26px text-heading"></i>
              </span>
              <a href="{{ url('/') }}" class="stretched-link">Dashboard</a>
              <small>User Dashboard</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-cog icon-26px text-heading"></i>
              </span>
              <a href="{{ url('pages/account-settings-account') }}" class="stretched-link">Setting</a>
              <small>Account Settings</small>
            </div>
          </div>
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-help-circle icon-26px text-heading"></i>
              </span>
              <a href="{{ url('pages/faq') }}" class="stretched-link">FAQs</a>
              <small>FAQs & Articles</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="icon-base bx bx-window-open icon-26px text-heading"></i>
              </span>
              <a href="{{ url('modal-examples') }}" class="stretched-link">Modals</a>
              <small>Useful Popups</small>
            </div>
          </div>
        </div>
      </div>
    </li>
    <!-- Quick links -->

    @php
      $userNotifications = Auth::check() ? Auth::user()->notifications()->take(6)->get() : collect();
      $unreadNotificationCount = Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
    @endphp
    <!-- Notification -->
    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false">
        <span class="position-relative">
          <i class="icon-base bx bx-bell icon-md"></i>
          @if($unreadNotificationCount > 0)
            <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
          @endif
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end p-0">
        <li class="dropdown-menu-header border-bottom">
          <div class="dropdown-header d-flex align-items-center py-3">
            <h6 class="mb-0 me-auto">Notifications</h6>
            <div class="d-flex align-items-center h6 mb-0">
              @if($unreadNotificationCount > 0)
                <span class="badge bg-label-primary me-2">{{ $unreadNotificationCount }} New</span>
                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="m-0">
                  @csrf
                  <button type="submit" class="btn btn-text-secondary btn-sm p-0 border-0 bg-transparent" title="Mark all as read">
                    <i class="icon-base bx bx-envelope-open text-heading"></i>
                  </button>
                </form>
              @endif
            </div>
          </div>
        </li>
        <li class="dropdown-notifications-list scrollable-container">
          <ul class="list-group list-group-flush">
            @forelse($userNotifications as $n)
              @php
                $nData = $n->data;
                $isUnread = is_null($n->read_at);
                $nColor = $nData['color'] ?? 'primary';
                $nIcon = $nData['icon'] ?? 'bx-bell';
              @endphp
              <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $isUnread ? '' : 'marked-as-read' }}">
                <a href="{{ $nData['url'] ?? route('admin.notifications') }}" class="text-reset text-decoration-none d-flex">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar">
                      <span class="avatar-initial rounded-circle bg-label-{{ $nColor }}">
                        <i class="icon-base bx {{ $nIcon }}"></i>
                      </span>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="small mb-0 {{ $isUnread ? 'fw-bold' : '' }}">{{ $nData['title'] ?? 'Notification' }}</h6>
                    <small class="mb-1 d-block text-body">{{ \Illuminate\Support\Str::limit($nData['message'] ?? '', 45) }}</small>
                    <small class="text-body-secondary">{{ $n->created_at->diffForHumans() }}</small>
                  </div>
                  @if($isUnread)
                    <div class="flex-shrink-0 dropdown-notifications-actions ms-2 align-self-center">
                      <span class="badge badge-dot bg-{{ $nColor }}"></span>
                    </div>
                  @endif
                </a>
              </li>
            @empty
              <li class="list-group-item text-center py-4 text-muted">
                <small>No notifications yet</small>
              </li>
            @endforelse
          </ul>
        </li>
        <li class="border-top">
          <div class="d-grid p-3">
            <a class="btn btn-primary btn-sm d-flex justify-content-center" href="{{ route('admin.notifications') }}">
              <small class="align-middle">View all notifications</small>
            </a>
          </div>
        </li>
      </ul>
    </li>
    <!--/ Notification -->
    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}" alt
            class="rounded-circle" />
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="avatar avatar-online">
                  <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"
                    alt class="w-px-40 h-auto rounded-circle" />
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">
                  @if (Auth::check())
                  {{ Auth::user()->name }}
                  @else
                  John Doe
                  @endif
                </h6>
                <small class="text-body-secondary">Admin</small>
              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
            <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
          </a>
        </li>
        @if (Auth::check() && Laravel\Jetstream\Jetstream::hasApiFeatures())
        <li>
          <a class="dropdown-item" href="{{ route('api-tokens.index') }}">
            <i class="icon-base bx bx-key icon-md me-3"></i><span>API Tokens</span>
          </a>
        </li>
        @endif
        <li>
          <a class="dropdown-item" href="{{ url('pages/account-settings-billing') }}">
            <span class="d-flex align-items-center align-middle">
              <i class="flex-shrink-0 icon-base bx bx-credit-card icon-md me-3"></i>
              <span class="flex-grow-1 align-middle">Billing Plan</span>
              <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
            </span>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="{{ route('admin.tenant.settings') }}">
            <span class="d-flex align-items-center align-middle">
              <i class="flex-shrink-0 icon-base bx bx-slider icon-md me-3"></i>
              <span class="flex-grow-1 align-middle">Tenant Settings</span>
            </span>
          </a>
        </li>
        @if (Auth::User() && Laravel\Jetstream\Jetstream::hasTeamFeatures())
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        <li>
          <h6 class="dropdown-header">Manage Team</h6>
        </li>
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        <li>
          <a class="dropdown-item"
            href="{{ Auth::user() ? route('teams.show', Auth::user()->currentTeam->id) : 'javascript:void(0)' }}">
            <i class="icon-base bx bx-cog icon-md me-3"></i><span>Team Settings</span>
          </a>
        </li>
        @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
        <li>
          <a class="dropdown-item" href="{{ route('teams.create') }}">
            <i class="icon-base bx bx-user icon-md me-3"></i><span>Create New Team</span>
          </a>
        </li>
        @endcan
        @if (Auth::user()->allTeams()->count() > 1)
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        <li>
          <h6 class="dropdown-header">Switch Teams</h6>
        </li>
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        @endif
        @if (Auth::user())
        @foreach (Auth::user()->allTeams() as $team)
        {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}

        <x-switchable-team :team="$team" />
        @endforeach
        @endif
        @endif
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        @if (Auth::check())
        <li>
          <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Logout</span>
          </a>
        </li>
        <form method="POST" id="logout-form" action="{{ route('logout') }}">
          @csrf
        </form>
        @else
        <li>
          <a class="dropdown-item" href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
            <i class="icon-base bx bx-log-in icon-md me-3"></i><span>Login</span>
          </a>
        </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>

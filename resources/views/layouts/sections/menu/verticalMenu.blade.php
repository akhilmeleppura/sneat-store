@php
use Illuminate\Support\Facades\Route;
use App\Helpers\HS\ModuleHelper;
use App\Http\Controllers\module_menu\ModuleMenuController;
use App\Helpers\Helpers;

// Load Configurations
$configData = Helper::appClasses();
$settingsModules = ModuleHelper::getSettingsModules();
$moduleMenus = (new ModuleMenuController)->getModuleMenus();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme"
  @foreach ($configData['menuAttributes'] as $attribute => $value)
    {{ $attribute }}="{{ $value }}"
  @endforeach
>
  {{-- App Brand --}}
  @if (!isset($navbarFull))
    <div class="app-brand demo">
      <a href="{{ url('/') }}" class="app-brand-link">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span>
      </a>
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="icon-base bx bx-chevron-left"></i>
      </a>
    </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    {{-- 🧱 Static Menus --}}
    @foreach ($menuData[0]->menu as $menu)
      @if (isset($menu->menuHeader))
        <li class="menu-header small"><span class="menu-header-text">{{ __($menu->menuHeader) }}</span></li>
      @else
        @php
          $activeClass = '';
          $currentRoute = Route::currentRouteName();
          $slug = $menu->slug ?? '';
          if (is_array($slug)) {
              foreach ($slug as $s) {
                  if (str_starts_with($currentRoute, $s)) $activeClass = 'active open';
              }
          } else {
              if (str_starts_with($currentRoute, $slug)) $activeClass = 'active open';
          }
        @endphp

        <li class="menu-item {{ $activeClass }}">
          <a href="{{ $menu->url ?? 'javascript:void(0);' }}"
             class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
             @if (!empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)<i class="{{ $menu->icon }}"></i>@endisset
            <div>{{ __($menu->name ?? '') }}</div>
            @isset($menu->badge)
              <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
            @endisset
          </a>

          @isset($menu->submenu)
            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
          @endisset
        </li>
      @endif
    @endforeach

    {{-- ⚙️ Settings (with Dynamic Modules inside) --}}
    @if (count($settingsModules) || count($moduleMenus))
      <li class="menu-header small">
        <span class="menu-header-text">Settings</span>
      </li>
      <li class="menu-item {{ request()->is('laravel/*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base bx bx-cog"></i>
          <div>Settings</div>
        </a>
        <ul class="menu-sub">

          {{-- 📦 Dynamic Module Menus --}}
          @foreach ($moduleMenus as $moduleMenu)
            @foreach ($moduleMenu['menu'] as $menu)
              @php
                $activeClass = '';
                $slug = $menu['slug'] ?? '';
                if (is_array($slug)) {
                    foreach ($slug as $s) {
                        if (str_starts_with(Route::currentRouteName(), $s)) $activeClass = 'active open';
                    }
                } else {
                    if (str_starts_with(Route::currentRouteName(), $slug)) $activeClass = 'active open';
                }
              @endphp

              <li class="menu-item {{ $activeClass }}">
                <a href="{{ $menu['url'] ?? 'javascript:void(0);' }}"
                   class="{{ isset($menu['submenu']) ? 'menu-link menu-toggle' : 'menu-link' }}">
                  @isset($menu['icon'])<i class="{{ $menu['icon'] }}"></i>@endisset
                  <div>{{ $menu['name'] }}</div>
                </a>

                @isset($menu['submenu'])
                  <ul class="menu-sub">
                    @foreach ($menu['submenu'] as $submenu)
                      <li class="menu-item {{ request()->is($submenu['slug'] . '*') ? 'active' : '' }}">
                        <a href="{{ url($submenu['url']) }}" class="menu-link">
                          <div>{{ $submenu['name'] }}</div>
                        </a>
                      </li>
                    @endforeach
                  </ul>
                @endisset
              </li>
            @endforeach
          @endforeach

        </ul>
      </li>
    @endif
  </ul>
</aside>

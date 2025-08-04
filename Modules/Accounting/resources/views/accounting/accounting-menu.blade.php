<ul class="nav nav-align-left nav-pills flex-column">
  @foreach ($menu as $item)
    <li class="nav-item mb-1">
      <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">
        <i class="icon-base {{ $item['icon'] }} icon-18px me-1_5"></i>
        <span class="align-middle">{{ $item['label'] }}</span>
      </a>
    </li>
  @endforeach
</ul>


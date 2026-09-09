@extends($layout)

@section('title', $title ?? 'Notifications')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1">{{ $title ?? 'Notifications' }}</h3>
      <p class="text-muted mb-0">Stay updated on your orders, sales, payments, and system alerts.</p>
    </div>
    @if($notifications->count() > 0)
      <form action="{{ route('notifications.mark-all-read') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm">
          <i class="icon-base bx bx-check-double me-1"></i> Mark All as Read
        </button>
      </form>
    @endif
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
      @forelse($notifications as $notification)
        @php
          $data = $notification->data;
          $isUnread = is_null($notification->read_at);
          $icon = $data['icon'] ?? 'bx-bell';
          $color = $data['color'] ?? 'primary';
        @endphp
        <div class="list-group-item p-4 {{ $isUnread ? 'bg-label-' . $color . '-subtle' : '' }} d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-start gap-3">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded-circle bg-label-{{ $color }}">
                <i class="icon-base bx {{ $icon }} fs-4"></i>
              </span>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <h6 class="fw-bold mb-0 {{ $isUnread ? 'text-' . $color : 'text-heading' }}">{{ $data['title'] ?? 'Notification' }}</h6>
                @if($isUnread)
                  <span class="badge badge-dot bg-{{ $color }}"></span>
                @endif
              </div>
              <p class="mb-1 text-secondary">{{ $data['message'] ?? '' }}</p>
              <small class="text-muted"><i class="icon-base bx bx-time-five me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            @if(!empty($data['url']))
              <a href="{{ $data['url'] }}" class="btn btn-sm btn-primary">
                View <i class="icon-base bx bx-chevron-right ms-1"></i>
              </a>
            @endif
          </div>
        </div>
      @empty
        <div class="text-center py-5">
          <i class="icon-base bx bx-bell-off display-4 text-muted mb-3"></i>
          <h5 class="fw-semibold">No notifications yet</h5>
          <p class="text-muted mb-0">You're all caught up! Updates regarding your account and transactions will appear here.</p>
        </div>
      @endforelse
    </div>
    @if($notifications->hasPages())
      <div class="card-footer bg-transparent py-3">
        {{ $notifications->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

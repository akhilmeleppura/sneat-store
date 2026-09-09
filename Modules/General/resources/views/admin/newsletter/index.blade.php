@extends('layouts/layoutMaster')

@section('title', 'Newsletter Subscribers - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Marketing /</span> Newsletter Subscribers</h4>
      <small class="text-muted">Manage email marketing audience, review subscription channels, and export email campaigns</small>
    </div>
    <a href="{{ route('admin.newsletter.export') }}" class="btn btn-outline-primary">
      <i class="bx bx-download me-1"></i> Export Active CSV
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Audience</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($stats['total']) }}</h4>
              </div>
              <small class="text-muted">Registered contacts</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-envelope bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Active Subscribers</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['subscribed']) }}</h4>
              </div>
              <small class="text-success">Subscribed & receiving</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-user-check bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Unsubscribed</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-secondary">{{ number_format($stats['unsubscribed']) }}</h4>
              </div>
              <small class="text-muted">Opted out</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-secondary">
                <i class="bx bx-user-x bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Verified Emails</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($stats['verified']) }}</h4>
              </div>
              <small class="text-info">Double opt-in verified</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-check-shield bx-sm"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscribers Table Card -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="card-title mb-0">Subscriber Directory ({{ $subscribers->total() }})</h5>
      <form method="GET" action="{{ route('admin.newsletter.index') }}" class="d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search email or IP..." value="{{ request('search') }}">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
          <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Email Address</th>
            <th>Status</th>
            <th>Verification</th>
            <th>IP Address</th>
            <th>Subscribed On</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($subscribers as $sub)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ strtoupper(substr($sub->email, 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading">{{ $sub->email }}</span>
                  </div>
                </div>
              </td>
              <td>
                @if($sub->status === 'subscribed')
                  <span class="badge bg-label-success">Subscribed</span>
                @else
                  <span class="badge bg-label-secondary">Unsubscribed</span>
                @endif
              </td>
              <td>
                @if($sub->verified_at)
                  <span class="badge bg-label-info"><i class="bx bx-check me-1"></i> Verified</span>
                  <br><small class="text-muted">{{ $sub->verified_at->format('M d, Y') }}</small>
                @else
                  <span class="badge bg-label-warning">Unverified</span>
                @endif
              </td>
              <td>
                <code>{{ $sub->ip_address ?? 'N/A' }}</code>
              </td>
              <td>{{ $sub->created_at->format('M d, Y H:i') }}</td>
              <td>
                <div class="d-flex align-items-center gap-1">
                  <form action="{{ route('admin.newsletter.toggle', $sub->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon {{ $sub->status === 'subscribed' ? 'btn-label-warning' : 'btn-label-success' }}" title="{{ $sub->status === 'subscribed' ? 'Unsubscribe' : 'Resubscribe' }}">
                      <i class="bx {{ $sub->status === 'subscribed' ? 'bx-user-minus' : 'bx-user-check' }}"></i>
                    </button>
                  </form>
                  <form action="{{ route('admin.newsletter.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently remove {{ $sub->email }} from audience?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bx bx-envelope-open bx-lg d-block mb-2 text-secondary"></i>
                No newsletter subscribers found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($subscribers->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $subscribers->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

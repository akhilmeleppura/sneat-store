@extends('layouts/layoutMaster')

@section('title', 'Product Reviews & Ratings - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog /</span> Product Reviews</h4>
      <small class="text-muted">Moderate customer ratings, verify genuine buyers, and post official responses</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Overview Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Reviews</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalReviews) }}</h4>
              </div>
              <small class="text-muted">Customer submissions</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-message-square-dots fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Approved Reviews</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ number_format($approvedReviews) }}</h4>
              </div>
              <small class="text-muted">Live on storefront</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-circle fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Verified Buyers</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ number_format($verifiedBuyerReviews) }}</h4>
              </div>
              <small class="text-muted">Confirmed purchase history</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-check-shield fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="content-left">
              <span class="text-heading">Platform Rating</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-warning">{{ number_format($averagePlatformRating, 1) }} / 5.0</h4>
              </div>
              <small class="text-muted">Average customer score</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-star fs-4"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search & Filter Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('catalog.reviews.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Product, customer, or comment..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Rating</label>
          <select name="rating" class="form-select">
            <option value="">All Stars</option>
            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending / Hidden</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Verified</label>
          <select name="verified" class="form-select">
            <option value="">All Reviewers</option>
            <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Verified Buyers Only</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
          <a href="{{ route('catalog.reviews.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Reviews Datatable Card -->
  <div class="card">
    <h5 class="card-header">Customer Reviews ({{ $reviews->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th>Customer</th>
            <th>Rating &amp; Title</th>
            <th>Comment</th>
            <th>Merchant Reply</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reviews as $review)
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="{{ $review->product->thumbnail_url }}" alt="{{ $review->product->name }}" class="rounded" style="width: 42px; height: 42px; object-fit: cover;">
                  <div>
                    <a href="{{ route('storefront.product.show', $review->product->slug) }}" target="_blank" class="fw-semibold text-heading text-decoration-none">
                      {{ Str::limit($review->product->name, 25) }}
                    </a>
                    @if($review->vendor)
                      <small class="d-block text-muted"><i class="bx bx-store me-1"></i>{{ $review->vendor->name }}</small>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="fw-semibold text-heading">{{ $review->user->name ?? 'Customer' }}</div>
                <small class="text-muted d-block">{{ $review->user->email ?? '' }}</small>
                @if($review->is_verified_buyer)
                  <span class="badge bg-label-success mt-1"><i class="bx bx-check-shield me-1"></i>Verified Buyer</span>
                @endif
              </td>
              <td>
                <div class="text-warning mb-1">
                  {!! $review->stars_html !!}
                </div>
                @if($review->title)
                  <div class="fw-semibold small text-dark">{{ Str::limit($review->title, 25) }}</div>
                @endif
                <small class="text-muted">{{ $review->created_at->format('M d, Y') }}</small>
              </td>
              <td>
                <p class="mb-0 text-muted small text-wrap" style="max-width: 250px;">
                  {{ Str::limit($review->comment, 80) }}
                </p>
              </td>
              <td>
                @if($review->admin_reply)
                  <div class="text-wrap small" style="max-width: 200px;">
                    <span class="badge bg-label-info mb-1"><i class="bx bx-check me-1"></i>Replied</span>
                    <p class="text-muted mb-0 small">{{ Str::limit($review->admin_reply, 60) }}</p>
                  </div>
                @else
                  <span class="text-muted small">No reply yet</span>
                @endif
              </td>
              <td>
                {!! $review->status_badge !!}
              </td>
              <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <!-- Toggle Visibility Form -->
                  <form action="{{ route('catalog.reviews.toggle', $review->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon {{ $review->is_approved ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $review->is_approved ? 'Hide Review' : 'Approve Review' }}">
                      <i class="bx {{ $review->is_approved ? 'bx-hide' : 'bx-show' }}"></i>
                    </button>
                  </form>

                  <!-- Reply Modal Button -->
                  <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#replyModal{{ $review->id }}" title="Reply to Customer">
                    <i class="bx bx-reply"></i>
                  </button>

                  <!-- Delete Review Form -->
                  <form action="{{ route('catalog.reviews.destroy', $review->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this review?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Review">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </div>

                <!-- Reply Modal -->
                <div class="modal fade" id="replyModal{{ $review->id }}" tabindex="-1" aria-labelledby="replyModalLabel{{ $review->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered text-start">
                    <div class="modal-content">
                      <form action="{{ route('catalog.reviews.reply', $review->id) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title fw-bold" id="replyModalLabel{{ $review->id }}">Reply to {{ $review->user->name ?? 'Customer' }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3 p-3 bg-light rounded">
                            <div class="text-warning mb-1">{!! $review->stars_html !!}</div>
                            <p class="mb-0 text-muted small">"{{ $review->comment }}"</p>
                          </div>
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Your Official Response <span class="text-danger">*</span></label>
                            <textarea name="admin_reply" class="form-control" rows="4" placeholder="Thank the customer or address their feedback..." required>{{ $review->admin_reply }}</textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Response</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bx bx-message-rounded-dots fs-1 d-block mb-2 text-secondary"></i>
                No customer reviews found matching the search criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($reviews->hasPages())
      <div class="card-footer py-3">
        {{ $reviews->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

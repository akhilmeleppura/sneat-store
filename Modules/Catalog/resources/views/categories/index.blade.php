@extends('layouts/layoutMaster')

@section('title', 'Categories - Catalog')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog /</span> Categories</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Left: Add Category Form -->
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Add Category</h5></div>
        <div class="card-body">
          <form action="{{ route('catalog.categories.store') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Category Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Footwear" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Parent Category</label>
              <select name="parent_id" class="form-select">
                <option value="">None (Top Level)</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Category summary..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-plus me-1"></i> Add Category</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right: Categories Table -->
    <div class="col-md-8">
      <div class="card">
        <h5 class="card-header">All Categories ({{ count($categories) }})</h5>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Category</th>
                <th>Parent</th>
                <th>Total Products</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $cat)
                <tr>
                  <td><strong>{{ $cat->name }}</strong></td>
                  <td>{{ $cat->parent->name ?? '—' }}</td>
                  <td><span class="badge bg-label-info">{{ $cat->products_count }} Products</span></td>
                  <td>
                    <span class="badge bg-{{ $cat->status === 'active' ? 'success' : 'secondary' }}">
                      {{ ucfirst($cat->status) }}
                    </span>
                  </td>
                  <td>
                    <form action="{{ route('catalog.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No categories created yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layouts/layoutMaster')

@section('title', 'Brands - Catalog')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Catalog /</span> Brands</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Left: Add Brand Form -->
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Add Brand</h5></div>
        <div class="card-body">
          <form action="{{ route('catalog.brands.store') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Brand Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Nike" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Official Website</label>
              <input type="url" name="website" class="form-control" placeholder="https://nike.com">
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Brand story..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-plus me-1"></i> Add Brand</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right: Brands Table -->
    <div class="col-md-8">
      <div class="card">
        <h5 class="card-header">All Brands ({{ count($brands) }})</h5>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Brand</th>
                <th>Website</th>
                <th>Total Products</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($brands as $brand)
                <tr>
                  <td><strong>{{ $brand->name }}</strong></td>
                  <td>
                    @if($brand->website)
                      <a href="{{ $brand->website }}" target="_blank" class="text-muted"><i class="bx bx-link-external me-1"></i> Visit</a>
                    @else
                      —
                    @endif
                  </td>
                  <td><span class="badge bg-label-info">{{ $brand->products_count }} Products</span></td>
                  <td>
                    <span class="badge bg-{{ $brand->status === 'active' ? 'success' : 'secondary' }}">
                      {{ ucfirst($brand->status) }}
                    </span>
                  </td>
                  <td>
                    <form action="{{ route('catalog.brands.destroy', $brand->id) }}" method="POST" onsubmit="return confirm('Delete this brand?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No brands created yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

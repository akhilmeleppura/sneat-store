@extends('layouts/layoutMaster')

@section('title', 'My Products - ' . $vendor->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Vendor /</span> Products</h4>
      <small class="text-muted">Manage your product catalog, prices, and stock inventory.</small>
    </div>
    <a href="{{ route('vendor.products.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Add New Product
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header">Your Catalog ({{ $products->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Date Added</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($products as $product)
            <tr>
              <td>
                <span class="fw-semibold">{{ $product->name }}</span>
                <small class="text-muted d-block">SKU: {{ $product->sku }}</small>
              </td>
              <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
              <td><span class="fw-bold">${{ number_format($product->price, 2) }}</span></td>
              <td>
                @php $totalStock = $product->variants->sum('stock_quantity'); @endphp
                <span class="badge bg-label-{{ $totalStock > 0 ? 'success' : 'danger' }}">
                  {{ $totalStock }} in stock
                </span>
              </td>
              <td>
                <span class="badge bg-label-{{ $product->status === 'published' ? 'success' : 'secondary' }}">
                  {{ ucfirst($product->status) }}
                </span>
              </td>
              <td>{{ $product->created_at->format('M d, Y') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">You haven't listed any products yet. Click "Add New Product" to start selling.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($products->hasPages())
      <div class="card-footer">
        {{ $products->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

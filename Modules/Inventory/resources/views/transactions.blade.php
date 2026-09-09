@extends('layouts/layoutMaster')

@section('title', 'Inventory Audit Logs - Sneat')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Inventory /</span> Audit Logs</h4>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Back to Stock
    </a>
  </div>

  <div class="card">
    <h5 class="card-header">Immutable Stock Movement Log ({{ $transactions->total() }})</h5>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Product & Variant</th>
            <th>Branch</th>
            <th>Movement Type</th>
            <th>Quantity Delta</th>
            <th>Balance After</th>
            <th>Reference</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($transactions as $tx)
            <tr>
              <td><small class="text-muted">{{ $tx->created_at->format('Y-m-d H:i:s') }}</small></td>
              <td>
                <span class="fw-semibold">{{ $tx->variant->product->name ?? 'Product' }}</span>
                <small class="text-muted d-block">SKU: {{ $tx->variant->sku ?? 'N/A' }}</small>
              </td>
              <td><span class="badge bg-label-info">{{ $tx->branch->name ?? 'Primary' }}</span></td>
              <td>
                @php
                  $badgeClass = match($tx->type) {
                    'initial', 'stock_in', 'adjustment_positive' => 'bg-label-success',
                    'stock_out', 'adjustment_negative' => 'bg-label-danger',
                    'order_reserved' => 'bg-label-warning',
                    'order_released' => 'bg-label-info',
                    'order_fulfilled' => 'bg-label-primary',
                    default => 'bg-label-secondary'
                  };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', strtoupper($tx->type)) }}</span>
              </td>
              <td>
                <span class="fw-bold {{ $tx->quantity > 0 ? 'text-success' : 'text-danger' }}">
                  {{ $tx->quantity > 0 ? '+' . $tx->quantity : $tx->quantity }}
                </span>
              </td>
              <td><span class="fw-bold">{{ $tx->balance_after }}</span></td>
              <td>
                @if($tx->reference_id)
                  <code>{{ $tx->reference_type }}: {{ $tx->reference_id }}</code>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td><small class="text-muted">{{ $tx->note ?? '—' }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bx bx-history fs-3 mb-2 d-block"></i>
                No audit trail records found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $transactions->links() }}
    </div>
  </div>
</div>
@endsection

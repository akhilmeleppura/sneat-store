@extends('layouts/layoutMaster')

@section('title', 'Vendor Dashboard - ' . $vendor->name)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold py-1 mb-0">{{ $vendor->name }} <span class="text-muted fw-light">/ Vendor Portal</span></h4>
      <small class="text-muted">Welcome back! Manage your marketplace store, products, orders, and payout balances.</small>
    </div>
    <div>
      <a href="{{ route('vendor.products.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Add Product
      </a>
    </div>
  </div>

  <!-- Metric KPI Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">Available Balance</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">${{ number_format($availableBalance, 2) }}</h4>
              </div>
              <small class="text-success"><i class="bx bx-wallet me-1"></i>Ready for withdrawal</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-dollar fs-4"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">Lifetime Net Earnings</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">${{ number_format($totalEarnings, 2) }}</h4>
              </div>
              <small class="text-muted">After platform commission</small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-line-chart fs-4"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">Orders Fulfilled</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $totalOrders }}</h4>
              </div>
              <small class="text-info"><i class="bx bx-cart me-1"></i>Customer purchases</small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-shopping-bag fs-4"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">Listed Products</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $totalProducts }}</h4>
              </div>
              <small class="text-warning">Commission: {{ $vendor->commission_rate }}%</small>
            </div>
            <span class="badge bg-label-warning rounded p-2">
              <i class="bx bx-package fs-4"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Vendor Performance ApexChart -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="card-title mb-0">Sales & Net Earnings Velocity</h5>
        <small class="text-muted">30-day chronological earnings and orders trend</small>
      </div>
      <span class="badge bg-label-success">Direct Merchant Settlement</span>
    </div>
    <div class="card-body">
      <div id="vendorEarningsChart" style="min-height: 320px;"></div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Store Orders</h5>
          <a href="{{ route('vendor.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Your Items</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentOrders as $order)
                <tr>
                  <td>
                    <a href="{{ route('vendor.orders.show', $order->id) }}" class="fw-semibold">
                      #{{ $order->order_number }}
                    </a>
                  </td>
                  <td>{{ $order->customer_name }}</td>
                  <td><span class="badge bg-label-secondary">{{ $order->items->count() }} item(s)</span></td>
                  <td>
                    <span class="badge bg-label-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'primary' : 'warning') }}">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>{{ $order->created_at->format('M d') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No orders received yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent Earnings Allocations -->
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Earnings Stream</h5>
          <a href="{{ route('vendor.payouts.index') }}" class="btn btn-sm btn-outline-primary">Request Payout</a>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($recentEarnings as $earning)
              <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-3 bg-label-success rounded d-flex align-items-center justify-content-center">
                    <i class="bx bx-check fs-4"></i>
                  </div>
                  <div>
                    <span class="fw-semibold d-block text-truncate" style="max-width: 180px;">
                      {{ $earning->orderItem?->product_name ?? 'Order Item' }}
                    </span>
                    <small class="text-muted">Order #{{ $earning->order?->order_number }}</small>
                  </div>
                </div>
                <div class="text-end">
                  <span class="fw-bold text-success">+${{ number_format($earning->net_amount, 2) }}</span>
                  <small class="text-muted d-block">Fee: ${{ number_format($earning->commission_amount, 2) }}</small>
                </div>
              </li>
            @empty
              <li class="list-group-item text-center py-4 text-muted">No earnings accrued yet.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartLabels = @json($chartData['labels']);
  const chartEarnings = @json($chartData['net_earnings']);
  const chartSales = @json($chartData['sales_count']);

  if (typeof ApexCharts !== 'undefined' && document.querySelector('#vendorEarningsChart')) {
    const options = {
      series: [
        {
          name: 'Net Earnings (USD)',
          type: 'area',
          data: chartEarnings
        },
        {
          name: 'Units Sold',
          type: 'line',
          data: chartSales
        }
      ],
      chart: {
        height: 320,
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      colors: ['#71dd37', '#696cff'],
      stroke: {
        curve: 'smooth',
        width: [2, 3]
      },
      fill: {
        type: ['gradient', 'solid'],
        gradient: {
          shade: 'light',
          type: 'vertical',
          shadeIntensity: 0.5,
          opacityFrom: 0.45,
          opacityTo: 0.05,
          stops: [0, 90, 100]
        }
      },
      labels: chartLabels,
      yaxis: [
        {
          title: { text: 'Net Earnings ($)', style: { color: '#71dd37' } },
          labels: {
            formatter: function (val) {
              return '$' + Number(val).toFixed(2);
            }
          }
        },
        {
          opposite: true,
          title: { text: 'Units Sold', style: { color: '#696cff' } },
          labels: {
            formatter: function (val) {
              return Math.round(val);
            }
          }
        }
      ],
      xaxis: {
        categories: chartLabels
      },
      tooltip: {
        shared: true,
        intersect: false
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right'
      },
      grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 4
      }
    };

    const chart = new ApexCharts(document.querySelector('#vendorEarningsChart'), options);
    chart.render();
  }
});
</script>
@endpush
@endsection

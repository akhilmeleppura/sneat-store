@extends('layouts/layoutMaster')

@section('title', 'Sales & Revenue Analytics - Sneat Admin')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Filters -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold py-1 mb-1">
        <span class="text-muted fw-light">Reports & Analytics /</span> Sales Analytics
      </h4>
      <p class="text-muted mb-0">Overview of revenue streams, transaction volumes, and product velocity.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('admin.reports.export.sales', ['period' => $period]) }}" class="btn btn-outline-success">
        <i class="bx bx-download me-1"></i> Export CSV
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports.sales') }}" class="row g-3 align-items-end">
        <div class="col-md-4 col-sm-6">
          <label class="form-label fw-semibold">Reporting Timeframe</label>
          <select name="period" class="form-select" onchange="this.form.submit()">
            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
            <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
            <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Past Year</option>
          </select>
        </div>
        <div class="col-md-4 col-sm-6">
          <label class="form-label fw-semibold">Store Context</label>
          <select name="store_id" class="form-select" onchange="this.form.submit()">
            <option value="">All Stores (Platform Wide)</option>
            @foreach($stores as $st)
              <option value="{{ $st->id }}" {{ (string)$storeId === (string)$st->id ? 'selected' : '' }}>
                {{ $st->name }} ({{ $st->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bx bx-filter-alt me-1"></i> Apply Filter
          </button>
          <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-4 mb-4">
    <!-- Gross Sales -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Gross Sales</span>
            <div class="avatar bg-label-primary rounded p-2">
              <i class="bx bx-dollar fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['gross_sales'], 2) }}</h3>
          <small class="text-muted">Total collected before deductions</small>
        </div>
      </div>
    </div>

    <!-- Net Revenue -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Net Revenue</span>
            <div class="avatar bg-label-success rounded p-2">
              <i class="bx bx-trending-up fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['net_sales'], 2) }}</h3>
          <small class="text-muted">Subtotal minus discounts applied</small>
        </div>
      </div>
    </div>

    <!-- Total Orders -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Total Orders</span>
            <div class="avatar bg-label-info rounded p-2">
              <i class="bx bx-shopping-bag fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">{{ number_format($overview['total_orders']) }}</h3>
          <div class="d-flex gap-2">
            <span class="badge bg-label-success">{{ $overview['completed_orders'] }} Completed</span>
            <span class="badge bg-label-warning">{{ $overview['processing_orders'] }} Processing</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Average Order Value -->
    <div class="col-xl-3 col-md-6 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Avg. Order Value (AOV)</span>
            <div class="avatar bg-label-warning rounded p-2">
              <i class="bx bx-pie-chart-alt fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1">${{ number_format($overview['average_order_value'], 2) }}</h3>
          <small class="text-muted">Completion Rate: <strong class="text-primary">{{ $overview['completion_rate'] }}%</strong></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Breakdown Badges Card -->
  <div class="card mb-4 bg-lighter border-0">
    <div class="card-body py-3">
      <div class="row text-center gy-2">
        <div class="col-sm-4">
          <div class="text-muted small">Sales Tax Collected</div>
          <div class="fw-bold text-dark fs-5">${{ number_format($overview['tax_total'], 2) }}</div>
        </div>
        <div class="col-sm-4 border-start border-end">
          <div class="text-muted small">Shipping Charges</div>
          <div class="fw-bold text-dark fs-5">${{ number_format($overview['shipping_total'], 2) }}</div>
        </div>
        <div class="col-sm-4">
          <div class="text-muted small">Discounts Granted</div>
          <div class="fw-bold text-danger fs-5">-${{ number_format($overview['discount_total'], 2) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ApexChart Card -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="card-title mb-0">Sales & Order Volume Trend</h5>
        <small class="text-muted">Daily performance timeline ({{ $overview['start_date'] }} to {{ $overview['end_date'] }})</small>
      </div>
      <div class="dropdown">
        <span class="badge bg-label-primary px-3 py-2 text-uppercase">{{ str_replace('_', ' ', $period) }}</span>
      </div>
    </div>
    <div class="card-body">
      <div id="salesTrendChart" style="min-height: 350px;"></div>
    </div>
  </div>

  <!-- Bottom Row: Top Products & Recent Orders -->
  <div class="row g-4">
    <!-- Top Selling Products -->
    <div class="col-lg-6 col-12">
      <div class="card h-100 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Top Selling Products</h5>
          <span class="badge bg-label-info">By Velocity</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-center">Units Sold</th>
                <th class="text-end">Revenue</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topProducts as $product)
                <tr>
                  <td>
                    <div class="fw-semibold text-dark">{{ $product['product_name'] }}</div>
                  </td>
                  <td><code class="text-primary">{{ $product['sku'] }}</code></td>
                  <td class="text-center">
                    <span class="badge bg-label-primary rounded-pill">{{ $product['total_qty'] }}</span>
                  </td>
                  <td class="text-end fw-bold text-success">${{ number_format($product['total_revenue'], 2) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    <i class="bx bx-package fs-3 d-block mb-1"></i> No product sales recorded in this period.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-lg-6 col-12">
      <div class="card h-100 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Orders</h5>
          <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentOrders as $order)
                <tr>
                  <td>
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-semibold text-primary">
                      {{ $order->order_number }}
                    </a>
                    <div class="text-muted small">{{ $order->created_at->format('M d, H:i') }}</div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $order->customer_name }}</div>
                    <div class="text-muted small">{{ $order->customer_email }}</div>
                  </td>
                  <td>
                    @if($order->status === 'completed')
                      <span class="badge bg-label-success">Completed</span>
                    @elseif($order->status === 'processing')
                      <span class="badge bg-label-info">Processing</span>
                    @elseif($order->status === 'cancelled')
                      <span class="badge bg-label-danger">Cancelled</span>
                    @else
                      <span class="badge bg-label-warning">{{ ucfirst($order->status) }}</span>
                    @endif
                  </td>
                  <td class="text-end fw-bold">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    <i class="bx bx-cart fs-3 d-block mb-1"></i> No recent orders found.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chartLabels = @json($chartData['labels']);
  const chartRevenue = @json($chartData['revenue']);
  const chartOrders = @json($chartData['orders']);

  if (typeof ApexCharts !== 'undefined' && document.querySelector('#salesTrendChart')) {
    const options = {
      series: [
        {
          name: 'Revenue (USD)',
          type: 'area',
          data: chartRevenue
        },
        {
          name: 'Order Count',
          type: 'line',
          data: chartOrders
        }
      ],
      chart: {
        height: 360,
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      colors: ['#696cff', '#03c3ec'],
      stroke: {
        curve: 'smooth',
        width: [2, 3],
        dashArray: [0, 4]
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
      markers: {
        size: [0, 4],
        strokeWidth: 2,
        hover: { size: 6 }
      },
      yaxis: [
        {
          title: { text: 'Revenue ($)', style: { color: '#696cff' } },
          labels: {
            formatter: function (value) {
              return '$' + Number(value).toLocaleString();
            }
          }
        },
        {
          opposite: true,
          title: { text: 'Orders', style: { color: '#03c3ec' } },
          labels: {
            formatter: function (val) {
              return Math.round(val);
            }
          }
        }
      ],
      xaxis: {
        categories: chartLabels,
        labels: {
          rotate: -45,
          rotateAlways: chartLabels.length > 15
        }
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (y, { seriesIndex }) {
            if (seriesIndex === 0) {
              return '$' + Number(y).toFixed(2);
            }
            return y + ' orders';
          }
        }
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

    const chart = new ApexCharts(document.querySelector('#salesTrendChart'), options);
    chart.render();
  }
});
</script>
@endpush
@endsection

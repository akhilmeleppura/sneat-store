@extends('layouts/layoutMaster')

@section('title', 'Currencies & FX Rates - Sneat Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header & Actions -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold py-1 mb-1">
        <span class="text-muted fw-light">Store Settings /</span> Currencies & FX Exchange Rates
      </h4>
      <p class="text-muted mb-0">Manage global currencies, multi-currency pricing rules, and real-time exchange rates.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCurrencyModal">
        <i class="bx bx-plus me-1"></i> Add Currency
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- KPI Summary Cards -->
  <div class="row g-4 mb-4">
    <!-- Base Currency -->
    <div class="col-md-4 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Primary Base Currency</span>
            <div class="avatar bg-label-primary rounded p-2">
              <i class="bx bx-dollar-circle fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-primary">{{ $defaultCurrency->code }} ({{ $defaultCurrency->symbol }})</h3>
          <small class="text-muted">Catalog & general ledger accounting anchor</small>
        </div>
      </div>
    </div>

    <!-- Active Currencies -->
    <div class="col-md-4 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Active Currencies</span>
            <div class="avatar bg-label-success rounded p-2">
              <i class="bx bx-check-shield fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-success">{{ $activeCount }} <span class="fs-6 fw-normal text-muted">/ {{ $totalCurrencies }} Configured</span></h3>
          <small class="text-muted">Available to international customers</small>
        </div>
      </div>
    </div>

    <!-- Multi-Currency Engine -->
    <div class="col-md-4 col-12">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fw-semibold">Dynamic FX Engine</span>
            <div class="avatar bg-label-info rounded p-2">
              <i class="bx bx-transfer-alt fs-4"></i>
            </div>
          </div>
          <h3 class="fw-bold mb-1 text-info">Real-time</h3>
          <small class="text-muted">Auto-converted at storefront & checkout</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Currencies Table Card -->
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Configured Currencies & Exchange Rates</h5>
      <span class="badge bg-label-primary">Base: {{ $defaultCurrency->code }} = 1.000000</span>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Currency</th>
            <th>Symbol</th>
            <th style="min-width: 200px;">Exchange Rate (vs {{ $defaultCurrency->code }})</th>
            <th>Decimals</th>
            <th>Position</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($currencies as $currency)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2 bg-label-secondary rounded d-flex align-items-center justify-content-center fw-bold">
                    {{ $currency->code }}
                  </div>
                  <div>
                    <span class="fw-semibold text-dark">{{ $currency->name }}</span>
                    @if($currency->is_default)
                      <span class="badge bg-label-primary ms-1">Base Currency</span>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <span class="fw-bold fs-5 text-dark">{{ $currency->symbol }}</span>
              </td>
              <td>
                @if($currency->is_default)
                  <span class="fw-bold text-success">1.000000 (Fixed Anchor)</span>
                @else
                  <form method="POST" action="{{ route('admin.currencies.rate', $currency->id) }}" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="number" step="0.000001" min="0.000001" name="exchange_rate" value="{{ $currency->exchange_rate }}" class="form-control form-control-sm" style="width: 130px;" required>
                    <button type="submit" class="btn btn-sm btn-icon btn-outline-primary" title="Update Exchange Rate">
                      <i class="bx bx-check"></i>
                    </button>
                  </form>
                @endif
              </td>
              <td>
                <span class="badge bg-label-secondary">{{ $currency->decimal_places }} Decimals</span>
              </td>
              <td>
                <span class="badge bg-label-info">{{ ucfirst($currency->symbol_position) }}</span>
              </td>
              <td>
                @if($currency->is_active)
                  <span class="badge bg-label-success">Active</span>
                @else
                  <span class="badge bg-label-secondary">Disabled</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-flex justify-content-end gap-1">
                  @if(! $currency->is_default)
                    <!-- Set Default Button -->
                    <form method="POST" action="{{ route('admin.currencies.default', $currency->id) }}" onsubmit="return confirm('Set {{ $currency->code }} as primary base currency? Existing rates will need recalibration.')">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="Make Primary Base Currency">
                        <i class="bx bx-star me-1"></i> Make Base
                      </button>
                    </form>

                    <!-- Toggle Status Button -->
                    <form method="POST" action="{{ route('admin.currencies.toggle', $currency->id) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-{{ $currency->is_active ? 'danger' : 'success' }}">
                        {{ $currency->is_active ? 'Disable' : 'Enable' }}
                      </button>
                    </form>
                  @else
                    <span class="text-muted small fst-italic">Primary Anchor</span>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Currency Modal -->
<div class="modal fade" id="addCurrencyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Currency</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('admin.currencies.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4 col-12">
              <label class="form-label">Code (ISO-4217)</label>
              <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. CHF" maxlength="3" required>
            </div>
            <div class="col-md-8 col-12">
              <label class="form-label">Currency Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Swiss Franc" required>
            </div>
            <div class="col-md-4 col-12">
              <label class="form-label">Symbol</label>
              <input type="text" name="symbol" class="form-control" placeholder="e.g. CHF or Fr" required>
            </div>
            <div class="col-md-8 col-12">
              <label class="form-label">Exchange Rate (vs {{ $defaultCurrency->code }})</label>
              <input type="number" step="0.000001" min="0.000001" name="exchange_rate" class="form-control" placeholder="e.g. 0.910000" required>
            </div>
            <div class="col-md-6 col-12">
              <label class="form-label">Decimal Places</label>
              <select name="decimal_places" class="form-select" required>
                <option value="2" selected>2 (Standard: $10.99)</option>
                <option value="0">0 (Zero-decimal: ¥1500)</option>
                <option value="3">3 (e.g. KWD: 1.500)</option>
                <option value="4">4</option>
              </select>
            </div>
            <div class="col-md-6 col-12">
              <label class="form-label">Symbol Position</label>
              <select name="symbol_position" class="form-select" required>
                <option value="before" selected>Before ($100)</option>
                <option value="after">After (100 €)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Currency</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

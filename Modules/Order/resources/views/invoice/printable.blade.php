<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #{{ $order->order_number }} — Sneat Store</title>
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Sneat CSS -->
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />
  <style>
    body {
      font-family: 'Public Sans', sans-serif;
      background-color: #f5f5f9;
      color: #566a7f;
      padding: 30px 0;
    }
    .invoice-card {
      background: #ffffff;
      max-width: 850px;
      margin: 0 auto;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .invoice-header {
      border-bottom: 2px solid #eceef1;
      padding-bottom: 24px;
      margin-bottom: 24px;
    }
    .invoice-title {
      font-size: 28px;
      font-weight: 700;
      color: #696cff;
    }
    .table th {
      background-color: #f8f9fa !important;
      color: #566a7f !important;
      font-weight: 600;
    }
    @media print {
      body {
        background-color: #ffffff;
        padding: 0;
      }
      .invoice-card {
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- Floating Print Actions (Hidden when printing) -->
  <div class="container mb-4 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg shadow-sm px-4 me-2">
      <i class="bx bx-printer me-1"></i> Print Invoice
    </button>
    <a href="{{ url()->previous() ?: route('account.orders.index') }}" class="btn btn-outline-secondary btn-lg shadow-sm px-4">
      <i class="bx bx-arrow-back me-1"></i> Back
    </a>
  </div>

  <div class="container">
    <div class="invoice-card">
      <!-- Invoice Header -->
      <div class="invoice-header d-flex justify-content-between align-items-center">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="app-brand-logo demo">@include('_partials.macros')</span>
            <span class="invoice-title">{{ config('variables.templateName', 'Sneat Store') }}</span>
          </div>
          <small class="text-muted d-block">Global E-Commerce & Retail Solutions</small>
          <small class="text-muted d-block">Tax ID / VAT: <strong>VAT-89472-CORP</strong></small>
        </div>
        <div class="text-end">
          <h4 class="fw-bold text-dark mb-1">INVOICE</h4>
          <span class="fw-semibold text-primary fs-5">#{{ $order->order_number }}</span>
          <small class="text-muted d-block mt-1">Issue Date: <strong>{{ $order->created_at->format('M d, Y') }}</strong></small>
          <small class="text-muted d-block">Status: <span class="badge bg-label-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }} text-uppercase">{{ $order->payment_status }}</span></small>
        </div>
      </div>

      <!-- Bill To & Ship To Details -->
      <div class="row g-4 mb-4">
        <div class="col-6">
          <h6 class="fw-bold text-uppercase text-muted small mb-2">Billed To:</h6>
          <h5 class="fw-bold mb-1">{{ $order->customer_name }}</h5>
          <p class="mb-0 text-muted">{{ $order->customer_email }}</p>
          <p class="mb-0 text-muted">{{ $order->customer_phone ?? 'No phone provided' }}</p>
        </div>
        <div class="col-6 text-end">
          <h6 class="fw-bold text-uppercase text-muted small mb-2">Shipped To:</h6>
          @if(is_array($order->shipping_address))
            <p class="mb-0 fw-semibold">{{ $order->customer_name }}</p>
            <p class="mb-0 text-muted">{{ $order->shipping_address['street'] ?? '' }}</p>
            <p class="mb-0 text-muted">{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['postal_code'] ?? '' }}</p>
            <p class="mb-0 text-muted">{{ $order->shipping_address['country'] ?? '' }}</p>
          @else
            <p class="text-muted mb-0">Standard Courier Delivery</p>
          @endif
        </div>
      </div>

      <!-- Line Items Table -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Description / Product</th>
              <th>SKU</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Line Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($order->items as $idx => $item)
              <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                  <span class="fw-bold text-dark">{{ $item->product_name }}</span>
                </td>
                <td><code>{{ $item->variant_sku }}</code></td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end fw-bold">${{ number_format($item->line_total, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Financial Totals -->
      <div class="row">
        <div class="col-7">
          <div class="p-3 bg-light rounded">
            <h6 class="fw-bold mb-2">Payment Details</h6>
            <small class="text-muted d-block">Method: <strong class="text-uppercase">{{ $order->payment_method }}</strong></small>
            <small class="text-muted d-block">Fulfillment: <strong class="text-uppercase">{{ $order->fulfillment_status }}</strong></small>
            @if($order->shippingMethod)
              <small class="text-muted d-block">Carrier: <strong>{{ $order->shippingMethod->carrier }} ({{ $order->shippingMethod->name }})</strong></small>
            @endif
            @if(!empty($order->metadata['invoice_number']))
              <small class="text-success d-block fw-semibold mt-1"><i class="bx bx-check-shield me-1"></i>ERP Registered: {{ $order->metadata['invoice_number'] }}</small>
            @endif
          </div>
        </div>
        <div class="col-5">
          <table class="table table-sm table-borderless">
            <tr>
              <td class="text-muted">Subtotal:</td>
              <td class="text-end fw-semibold">${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->discount_amount > 0)
              <tr class="text-success">
                <td>Discount:</td>
                <td class="text-end fw-semibold">-${{ number_format($order->discount_amount, 2) }}</td>
              </tr>
            @endif
            <tr>
              <td class="text-muted">Taxes & Duties:</td>
              <td class="text-end fw-semibold">${{ number_format($order->tax_amount, 2) }}</td>
            </tr>
            <tr>
              <td class="text-muted">Shipping & Freight:</td>
              <td class="text-end fw-semibold">${{ number_format($order->shipping_amount, 2) }}</td>
            </tr>
            <tr class="border-top">
              <td class="fs-5 fw-bold text-dark pt-2">Total Amount:</td>
              <td class="fs-5 fw-bold text-primary text-end pt-2">${{ number_format($order->grand_total, 2) }}</td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Footer / Terms -->
      <div class="border-top pt-4 mt-4 text-center text-muted small">
        <p class="mb-1">Thank you for choosing {{ config('variables.templateName', 'Sneat Store') }}! For support or inquiries, please contact <a href="mailto:support@sneat-store.com">support@sneat-store.com</a>.</p>
        <p class="mb-0">This document is an authentic tax invoice generated electronically.</p>
      </div>
    </div>
  </div>

</body>
</html>

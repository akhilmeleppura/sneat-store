@extends($templateView)

@section('template-content')
    <div class="row invoice-preview">
        <!-- Invoice -->
        <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
            <div class="card invoice-preview-card p-sm-12 p-6">

                {{-- ========== Header ========== --}}
                <div class="card-body invoice-preview-header rounded">
                    <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
                        <div class="mb-xl-0 mb-6 text-heading">
                            <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                                @if ($branchLogo)
                                    <img src="{{ $branchLogo }}" alt="Branch Logo" class="app-brand-logo" style="height: 40px;">
                                @else
                                    <span class="app-brand-logo demo">@include('_partials.macros')</span>
                                @endif
                                <span class="app-brand-text demo fw-bold ms-50 lh-1">
                                    {{ $invoice->items->first()?->company?->name ?? config('variables.templateName') }}
                                </span>
                            </div>

                            {{-- Branch details --}}
                            @if ($invoice->items->first()?->branch)
                                @php $branch = $invoice->items->first()->branch; @endphp
                                <p class="mb-1"><strong>{{ $branch->name ?? 'N/A' }}</strong></p>
                                <p class="mb-2">{{ $branch->address ?? 'N/A' }}</p>
                                <p class="mb-2">
                                    {{ $branch->city ?? '' }}{{ $branch->city && $branch->state ? ', ' : '' }}{{ $branch->state ?? '' }}
                                    {{ $branch->zip_code ?? '' }}
                                </p>
                                <p class="mb-2">{{ $branch->country ?? '' }}</p>
                                <p class="mb-2">{{ $branch->email ?? 'N/A' }}</p>
                                <p class="mb-0">{{ $branch->phone ?? 'N/A' }}</p>
                            @else
                                {{-- fallback --}}
                                <p class="mb-2">Office 149, 450 South Brand Brooklyn</p>
                                <p class="mb-2">San Diego County, CA 91905, USA</p>
                                <p class="mb-2">support@demo.com</p>
                                <p class="mb-0">+1 (123) 456 7891</p>
                            @endif
                        </div>

                        {{-- Invoice info --}}
                        <div>
                            <h5 class="mb-6">Invoice #{{ $invoice->document_prefix }}-{{ $invoice->document_number }}</h5>
                            <div class="mb-1 text-heading">
                                <span>Date Issued:</span>
                                <span class="fw-medium">{{ $invoice->issue_date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>
                            <div class="text-heading">
                                <span>Date Due:</span>
                                <span class="fw-medium">{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== Customer + Bill To ========== --}}
                <div class="card-body px-0">
                    <div class="row">
                        <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-6 mb-sm-0 mb-6">
                            <h6>Invoice To:</h6>
                            <p class="mb-1">{{ $invoice->customer->name ?? 'N/A' }}</p>
                            <p class="mb-1">{{ $invoice->customer->address ?? 'N/A' }}</p>
                            <p class="mb-1">
                                {{ $invoice->customer->city ?? '' }}{{ $invoice->customer->city && $invoice->customer->state ? ', ' : '' }}{{ $invoice->customer->state ?? '' }}
                                {{ $invoice->customer->zip_code ?? '' }}
                            </p>
                            <p class="mb-1">{{ $invoice->customer->phone ?? 'N/A' }}</p>
                            <p class="mb-0">{{ $invoice->customer->email ?? 'N/A' }}</p>
                        </div>

                        <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                            <h6>Bill To:</h6>
                            <table>
                                <tbody>
                                    <tr>
                                        <td class="pe-4">Total Due:</td>
                                        <td class="fw-medium">
                                            ${{ number_format($invoice->sub_total - ($invoice->document_discount_amount ?? 0) + ($invoice->total_tax ?? 0), 2) }}
                                        </td>
                                    </tr>
                                    @if ($invoice->items->first()?->branch?->bank_name)
                                        <tr>
                                            <td class="pe-4">Bank name:</td>
                                            <td>{{ $invoice->items->first()->branch->bank_name }}</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->items->first()?->branch?->bank_country)
                                        <tr>
                                            <td class="pe-4">Country:</td>
                                            <td>{{ $invoice->items->first()->branch->bank_country }}</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->items->first()?->branch?->iban)
                                        <tr>
                                            <td class="pe-4">IBAN:</td>
                                            <td>{{ $invoice->items->first()->branch->iban }}</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->items->first()?->branch?->swift_code)
                                        <tr>
                                            <td class="pe-4">SWIFT code:</td>
                                            <td>{{ $invoice->items->first()->branch->swift_code }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ========== Items Table ========== --}}
                <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->billingItem->name ?? $item->item->name ?? 'N/A' }}</td>
                                    <td>{{ $item->description ?? $item->billingItem->description ?? $item->item->description ?? 'N/A' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->selling_unit_price, 2) }}</td>

                                    {{-- Discount --}}
                                    <td>
                                        @if($item->discount_amount > 0)
                                            -${{ number_format($item->discount_amount, 2) }}
                                            @if($item->discount_rate)
                                                <small class="text-muted">({{ $item->discount_rate }}%)</small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- Tax --}}
                                    <td>
                                        @if($item->tax_amount > 0)
                                            ${{ number_format($item->tax_amount, 2) }}
                                            @if($item->tax_rate)
                                                <small class="text-muted">({{ $item->tax_rate }}%)</small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- Total line --}}
                                    <td>${{ number_format($item->total_price ?? ($item->quantity * $item->selling_unit_price), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No items available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ========== Totals Section ========== --}}
                <div class="table-responsive">
                    <table class="table m-0 table-borderless">
                        <tbody>
                            <tr>
                                <td class="align-top pe-6 ps-0 py-6 text-body">
                                    <p class="mb-1">
                                        <span class="me-2 h6">Salesperson:</span>
                                        <span>{{ $invoice->salesperson->name ?? $invoice->createdBy->name ?? '-' }}</span>
                                    </p>
                                    <span>Thanks for your business</span>
                                </td>

                                <td class="px-0 py-6 w-px-100">
                                    <p class="mb-2">Subtotal:</p>
                                    @if ($invoice->document_discount_amount > 0)
                                        <p class="mb-2">Discount
                                            @if ($invoice->document_discount_type == 'percentage' && $invoice->document_discount_rate)
                                                ({{ $invoice->document_discount_rate }}%)
                                            @endif:
                                        </p>
                                    @endif
                                    <p class="mb-2">Tax:</p>
                                    <p class="mb-0 fw-bold">Total:</p>
                                </td>

                                <td class="text-end px-0 py-6 w-px-100 fw-medium text-heading">
                                    <p class="fw-medium mb-2">${{ number_format($invoice->sub_total, 2) }}</p>
                                    @if ($invoice->document_discount_amount > 0)
                                        <p class="fw-medium mb-2">-${{ number_format($invoice->document_discount_amount, 2) }}</p>
                                    @endif
                                    <p class="fw-medium mb-2">${{ number_format($invoice->total_tax ?? 0, 2) }}</p>
                                    <p class="fw-medium mb-0">
                                        ${{ number_format($invoice->sub_total - ($invoice->document_discount_amount ?? 0) + ($invoice->total_tax ?? 0), 2) }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="mt-0 mb-6">

                {{-- Notes --}}
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-medium text-heading">Note:</span>
                            <span>{{ $invoice->note ?? 'Thank you for your business!' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== Invoice Actions ========== --}}
        <div class="col-xl-3 col-md-4 col-12 invoice-actions">
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#sendInvoiceOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                            <i class="icon-base bx bx-paper-plane icon-sm me-2"></i>Send Invoice
                        </span>
                    </button>
                    <a href="{{ route('billing.invoices.download', $invoice->id) }}" class="btn btn-label-secondary d-grid w-100 mb-4">
                        Download
                    </a>
                    <div class="d-flex mb-4">
                        <a class="btn btn-label-secondary d-grid w-100 me-4" target="_blank" href="{{ route('billing.invoices.print', $invoice->id) }}">
                            Print
                        </a>
                        <a href="{{ route('billing.invoices.edit', $invoice->id) }}" class="btn btn-label-secondary d-grid w-100">
                            Edit
                        </a>
                    </div>
                    <button class="btn btn-success d-grid w-100" data-bs-toggle="offcanvas" data-bs-target="#addPaymentOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                            <i class="icon-base bx bx-dollar icon-sm me-2"></i>Add Payment
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Offcanvas --}}
        @include('_partials._offcanvas.offcanvas-send-invoice')
        @include('_partials._offcanvas.offcanvas-add-payment')
    </div>
@endsection

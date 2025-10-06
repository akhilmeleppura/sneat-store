@extends('layouts/layoutMaster')
@section('title', 'Edit - Credit Note')

{{-- Styles --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

{{-- Scripts --}}
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/offcanvas-send-invoice.js', 'resources/assets/js/app-invoice-add.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Flatpickr for Issued Date
            const issueDatePicker = flatpickr(".credit-note-date", {
                dateFormat: "m/d/Y",
                defaultDate: "{{ $creditNote->issue_date ? $creditNote->issue_date->format('m/d/Y') : '' }}",
            });

            // Flatpickr for Due Date
            const dueDatePicker = flatpickr(".due-date", {
                dateFormat: "m/d/Y",
                defaultDate: "{{ $creditNote->due_date ? $creditNote->due_date->format('m/d/Y') : '' }}",
                minDate: "today"
            });

            // Select2 Initialization
            $(document).ready(function() {
                const invoiceSelect = $('#invoiceSelect');
                if (invoiceSelect.length) {
                    invoiceSelect.select2({
                        placeholder: '-- Select Invoice --',
                        allowClear: true,
                        width: '100%'
                    });
                    invoiceSelect.on('select2:select', (e) => handleInvoiceSelection(e.currentTarget));
                    invoiceSelect.on('select2:clear', () => clearCustomerDetails());
                }

                function handleInvoiceSelection(selectElement) {
                    let selected = selectElement.options[selectElement.selectedIndex];
                    if (selected && selected.value) {
                        $('#customerCompany').text(selected.getAttribute('data-company') || selected
                            .getAttribute('data-customer') || '');
                        let address = selected.getAttribute('data-address') || '';
                        let city = selected.getAttribute('data-city') || '';
                        let state = selected.getAttribute('data-state') || '';
                        let zip = selected.getAttribute('data-zip') || '';
                        let fullAddress = [address, [city, state, zip].filter(Boolean).join(', ')].filter(
                            Boolean).join(', ');
                        $('#customerAddress').text(fullAddress);
                        $('#customerPhone').text(selected.getAttribute('data-phone') || '');
                        $('#customerEmail').text(selected.getAttribute('data-email') || '');
                        $('#invoice_id').val(selected.value);
                    } else {
                        clearCustomerDetails();
                    }
                }

                function clearCustomerDetails() {
                    $('#customerCompany').text('Select an invoice to view details');
                    $('#customerAddress, #customerPhone, #customerEmail').text('');
                    $('#invoice_id').val('');
                }

                function initializeItemSelect(element) {
                    const $element = $(element);
                    if ($element.hasClass('select2-hidden-accessible')) $element.select2('destroy');
                    $element.select2({
                        placeholder: '-- Select Item --',
                        allowClear: true,
                        width: '100%'
                    });
                    $element.on('select2:select', (e) => handleItemSelection(e.currentTarget));
                    $element.on('select2:clear', (e) => clearItemDetails(e.currentTarget));
                }

                function handleItemSelection(selectElement) {
                    let wrapper = selectElement.closest('.repeater-wrapper');
                    let selected = selectElement.options[selectElement.selectedIndex];
                    if (selected && selected.value) {
                        wrapper.querySelector('.selling-unit-price').value = (parseFloat(selected
                            .getAttribute('data-price')) || 0).toFixed(2);
                    } else {
                        wrapper.querySelector('.selling-unit-price').value = '0.00';
                    }
                    calculateItemTotal(wrapper);
                    calculateSubtotal();
                }

                function clearItemDetails(selectElement) {
                    let wrapper = selectElement.closest('.repeater-wrapper');
                    wrapper.querySelector('.selling-unit-price').value = '0.00';
                    calculateItemTotal(wrapper);
                    calculateSubtotal();
                }

                $('.item-details').each(function() {
                    initializeItemSelect(this);
                });

                $('.invoice-form-container').on('click', '[data-repeater-create]', function() {
                    setTimeout(function() {
                        const $lastRow = $('.repeater-wrapper').last();
                        const itemSelect = $lastRow.find('.item-details');
                        if (!itemSelect.hasClass('select2-hidden-accessible')) {
                            initializeItemSelect(itemSelect[0]);
                        }
                        if ($lastRow.length) {
                            $lastRow.find('.discount, .tax-1').text('0%');
                            $lastRow.find('.item-discount-amount, .item-tax-amount').text(
                                '$0.00');
                            $lastRow.find('.selling-unit-price').val('');
                            $lastRow.find('.quantity').val('1');
                            $lastRow.find('.total-price').val('0.00');
                            $lastRow.find('.item-tax-id').val('0');
                            $lastRow.find('.item-discount-input, .item-tax-select').val(
                            '0');
                            itemSelect.val('').trigger('change');
                        }
                    }, 150);
                });

                setTimeout(() => {
                    if (invoiceSelect.val()) {
                        handleInvoiceSelection(invoiceSelect[0]);
                    }
                    $('#tax').val("{{ $creditNote->document_tax_id ?? 0 }}");
                    $('#discount-type').val(
                        "{{ $creditNote->document_discount_type == 1 ? '%' : 'Amount' }}");
                    $('#discount').val(
                        "{{ $creditNote->document_discount_rate > 0 ? $creditNote->document_discount_rate : $creditNote->document_discount_amount }}"
                        );
                    document.querySelectorAll('.repeater-wrapper').forEach(wrapper =>
                        calculateItemTotal(wrapper));
                    calculateSubtotal();
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-apply-changes')) {
                    e.preventDefault();
                    const dropdown = e.target.closest('.dropdown-menu');
                    const repeaterWrapper = dropdown.closest('.repeater-wrapper');
                    const discountValue = parseFloat(dropdown.querySelector('.item-discount-input')
                        .value) || 0;
                    const taxSelect = dropdown.querySelector('.item-tax-select');
                    const selectedTaxOption = taxSelect.options[taxSelect.selectedIndex];
                    repeaterWrapper.querySelector('.discount').textContent = discountValue + '%';
                    repeaterWrapper.querySelector('.item-tax-id').value = selectedTaxOption.value;
                    repeaterWrapper.querySelector('.tax-1').textContent = selectedTaxOption.getAttribute(
                        'data-percentage') + '%';
                    calculateItemTotal(repeaterWrapper);
                    calculateSubtotal();
                    bootstrap.Dropdown.getInstance(repeaterWrapper.querySelector(
                        '[data-bs-toggle="dropdown"]'))?.hide();
                }
            });
        });

        // Calculation functions
        function calculateItemTotal(row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.selling-unit-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').textContent) || 0;
            const taxPercent = parseFloat(row.querySelector('.tax-1').textContent) || 0;
            const baseAmount = qty * unitPrice;
            const discountAmount = baseAmount * (discountPercent / 100);
            const taxableAmount = baseAmount - discountAmount;
            const taxAmount = taxableAmount * (taxPercent / 100);
            const totalAmount = taxableAmount + taxAmount;
            row.querySelector('.item-discount-amount').textContent = '$' + discountAmount.toFixed(2);
            row.querySelector('.item-tax-amount').textContent = '$' + taxAmount.toFixed(2);
            row.querySelector('.total-price').value = totalAmount.toFixed(2);
        }

        document.addEventListener('change', (e) => {
            if (e.target.id === "discount-type" || e.target.id === "tax") calculateSubtotal();
        });
        document.addEventListener("input", (e) => {
            if (e.target.matches('.quantity, .selling-unit-price, #discount')) {
                if (e.target.closest('.repeater-wrapper')) calculateItemTotal(e.target.closest(
                '.repeater-wrapper'));
                calculateSubtotal();
            }
        });

        function calculateSubtotal() {
            let subtotal = 0,
                itemLevelDiscountTotal = 0,
                itemLevelTaxTotal = 0;
            document.querySelectorAll('.repeater-wrapper').forEach(row => {
                subtotal += (parseFloat(row.querySelector('.selling-unit-price')?.value) || 0) * (parseFloat(row
                    .querySelector('.quantity')?.value) || 0);
                itemLevelDiscountTotal += parseFloat((row.querySelector('.item-discount-amount')?.textContent ||
                    '$0').replace('$', ''));
                itemLevelTaxTotal += parseFloat((row.querySelector('.item-tax-amount')?.textContent || '$0')
                    .replace('$', ''));
            });
            $("#subtotal-display").text('$' + subtotal.toFixed(2));
            $("#item-level-discount-total-display").text('-$' + itemLevelDiscountTotal.toFixed(2));
            $("#item-level-tax-total-display").text('+$' + itemLevelTaxTotal.toFixed(2));
            let subtotalAfterItemAdjustments = subtotal - itemLevelDiscountTotal + itemLevelTaxTotal;
            $("#subtotal-after-item-adjustments-display").text('$' + subtotalAfterItemAdjustments.toFixed(2));
            let discountValue = parseFloat($("#discount").val() || 0);
            let discountType = $("#discount-type").val() || "%";
            let discountAmount = (discountType === "%") ? (subtotalAfterItemAdjustments * discountValue) / 100 :
                discountValue;
            $("#discount-display").text('-$' + discountAmount.toFixed(2));
            let taxableAmount = Math.max(0, subtotalAfterItemAdjustments - discountAmount);
            $("#taxable-amount-display").text('$' + taxableAmount.toFixed(2));
            let taxSelect = document.getElementById("tax");
            let taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex]?.getAttribute('data-rate') || 0);
            let taxAmount = (taxableAmount * taxRate) / 100;
            $("#tax-amount-display").text('+$' + taxAmount.toFixed(2));
            let grandTotal = taxableAmount + taxAmount;
            $("#grand-total").text('$' + grandTotal.toFixed(2));
            $("#sub_total").val(subtotal.toFixed(2));
            $("#document_discount_amount").val(discountAmount.toFixed(2));
            $("#tax_amount").val(taxAmount.toFixed(2));
            $("#total_amount").val(grandTotal.toFixed(2));
            $("#tax_id").val(taxSelect.value);
            $("#document_discount_type").val(discountType === "%" ? 1 : 2);
            $("#document_discount_rate").val(discountValue.toFixed(2));
        }

        // AJAX Form Submission
        function updateCreditNote() {
            const form = document.getElementById('creditNoteForm');
            const saveButton = document.getElementById('updateCreditNoteBtn');

            if (!$('#invoiceSelect').val()) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select an associated invoice.'
                });
            }
            if (!Array.from(document.querySelectorAll('.item-details')).some(item => item.value)) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please add at least one item.'
                });
            }

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

            let formData = new FormData(form);
            formData.append('_method', 'PUT');

            let issueDate = $('.credit-note-date').val();
            if (issueDate) {
                let parts = issueDate.split('/');
                formData.set('issue_date', `${parts[2]}-${parts[0]}-${parts[1]}`);
            }

            let dueDate = $('.due-date').val();
            if (dueDate) {
                let parts = dueDate.split('/');
                formData.set('due_date', `${parts[2]}-${parts[0]}-${parts[1]}`);
            }

            formData.set('credit_note_number', $('#creditNoteId').val());

            // Manually structure repeater data
            document.querySelectorAll('.repeater-wrapper').forEach((row, index) => {
                const itemId = row.querySelector('.item-details')?.value;
                if (itemId) {
                    const itemDbId = row.querySelector('input[name="id"]')?.value;
                    const prefix = itemDbId ? `existing_items[${index}]` : `items[${index}]`;
                    if (itemDbId) formData.append(`${prefix}[id]`, itemDbId);
                    formData.append(`${prefix}[item_id]`, itemId);
                    formData.append(`${prefix}[quantity]`, parseFloat(row.querySelector('.quantity')?.value || 0));
                    formData.append(`${prefix}[unit_price]`, parseFloat(row.querySelector('.selling-unit-price')
                        ?.value || 0));
                    formData.append(`${prefix}[total_price]`, parseFloat(row.querySelector('.total-price')?.value ||
                        0));
                    formData.append(`${prefix}[discount_percent]`, parseFloat(row.querySelector('.discount')
                        .textContent) || 0);
                    formData.append(`${prefix}[tax_id]`, row.querySelector('.item-tax-id').value);
                }
            });

            // Submit the form
            fetch("{{ route('billing.credit-notes.update', $creditNote->id) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json().then(data => ({
                    ok: response.ok,
                    data
                })))
                .then(({
                    ok,
                    data
                }) => {
                    if (ok) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message
                            })
                            .then(() => window.location.href = "{{ route('billing.credit-notes.index') }}");
                    } else {
                        throw new Error(data.message || 'An unknown error occurred.');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message
                    });
                })
                .finally(() => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Update';
                });
        }

        function previewCreditNote() {
            Swal.fire({
                icon: 'info',
                title: 'Preview',
                text: 'Preview functionality will be implemented soon.'
            });
        }
    </script>
@endsection

@section('content')
    @php
        $branchLogo = null;
        if (isset($branch)) {
            $extensions = ['png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'];
            foreach ($extensions as $ext) {
                if (file_exists(public_path('storage/branch_logos/' . $branch->id . '.' . $ext))) {
                    $branchLogo = asset('storage/branch_logos/' . $branch->id . '.' . $ext);
                    break;
                }
            }
        }
        $company = $company ?? null;
        $branch = $branch ?? null;
        $invoices = $invoices ?? [];
        $items = $items ?? [];
        $taxes = $taxes ?? [];
        $creditNoteNumber = $creditNote->document_prefix . '-' . $creditNote->document_number;
    @endphp

    <div class="row invoice-add">
        <div class="col-lg-9 col-12 mb-lg-0 mb-6">
            <div class="card invoice-preview-card p-sm-12 p-6">
                {{-- Card Header --}}
                <div class="card-body invoice-preview-header rounded">
                    <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
                        <div class="mb-md-0 mb-6">
                            <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                                <span class="app-brand-logo demo">
                                    @if ($branchLogo)
                                        <img src="{{ $branchLogo }}" alt="Branch Logo" height="40">
                                    @else
                                        @include('_partials.macros')
                                        <span
                                            class="app-brand-text demo fw-bold ms-50">{{ config('variables.templateName') }}</span>
                                    @endif
                                </span>
                            </div>
                            @if ($company && $branch)
                                <p class="mb-2">{{ $company->name ?? 'Company Name' }}</p>
                                <p class="mb-2">{{ $company->city ?? '' }}@if ($company->city)
                                        ,
                                    @endif{{ $company->state ?? '' }} {{ $company->zip ?? '' }}</p>
                                <p class="mb-2">{{ $branch->name ?? 'Branch Name' }}</p>
                                <p class="mb-3">{{ $branch->address ?? 'Branch Address' }}</p>
                            @endif
                        </div>
                        <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
                            <dl class="row mb-0 gx-4">
                                <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end"><span
                                        class="h5 text-capitalize mb-0 text-nowrap">Credit Note</span></dt>
                                <dd class="col-sm-7"><input type="text" class="form-control" disabled
                                        value="{{ $creditNoteNumber }}" id="creditNoteId" /></dd>
                                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end"><span
                                        class="fw-normal">Date Issued:</span></dt>
                                <dd class="col-sm-7"><input type="text" class="form-control credit-note-date"
                                        name="issue_date" placeholder="MM/DD/YYYY" /></dd>
                                <dt class="col-sm-5 d-md-flex align-items-center justify-content-end"><span
                                        class="fw-normal">Due Date:</span></dt>
                                <dd class="col-sm-7 mb-0"><input type="text" class="form-control due-date"
                                        name="due_date" placeholder="MM/DD/YYYY" /></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Customer Details --}}
                <div class="card-body px-0">
                    <div class="row">
                        <div class="col-md-6 col-sm-5 col-12 mb-sm-0 mb-6">
                            <h6>Invoice To:</h6>
                            <select class="form-select mb-4" id="invoiceSelect">
                                <option value=""></option>
                                @foreach ($invoices as $invoice)
                                    <option value="{{ $invoice->id }}"
                                        data-customer="{{ $invoice->customer->name ?? '' }}"
                                        data-company="{{ $invoice->customer->company_name ?? '' }}"
                                        data-address="{{ $invoice->customer->address ?? '' }}"
                                        data-city="{{ $invoice->customer->city ?? '' }}"
                                        data-state="{{ $invoice->customer->state ?? '' }}"
                                        data-zip="{{ $invoice->customer->zip ?? '' }}"
                                        data-phone="{{ $invoice->customer->phone ?? '' }}"
                                        data-email="{{ $invoice->customer->email ?? '' }}"
                                        {{ $creditNote->invoice_id == $invoice->id ? 'selected' : '' }}>
                                        {{ $invoice->document_prefix }}-{{ $invoice->document_number }} -
                                        {{ $invoice->customer->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="customerDetails">
                                <p class="mb-1 text-nowrap text-truncate" id="customerCompany">Select an invoice to view
                                    details</p>
                                <p class="mb-1 text-nowrap text-truncate" id="customerAddress"></p>
                                <p class="mb-1" id="customerPhone"></p>
                                <p class="mb-0 text-nowrap text-truncate" id="customerEmail"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-6" />

                {{-- Items Repeater --}}
                <div class="card-body pt-0 px-0">
                    <form class="source-item" id="creditNoteForm" method="POST"
                        action="{{ route('billing.credit-notes.update', $creditNote->id) }}">
                        @csrf
                        <input type="hidden" name="invoice_id" id="invoice_id" value="{{ $creditNote->invoice_id }}">
                        <input type="hidden" name="sub_total" id="sub_total">
                        <input type="hidden" name="document_discount_type" id="document_discount_type">
                        <input type="hidden" name="document_discount_rate" id="document_discount_rate">
                        <input type="hidden" name="document_discount_amount" id="document_discount_amount">
                        <input type="hidden" name="tax_id" id="tax_id">
                        <input type="hidden" name="tax_amount" id="tax_amount">
                        <input type="hidden" name="total_amount" id="total_amount">

                        <div class="invoice-form-container">
                            <div class="mb-4" data-repeater-list="items">
                                @forelse($creditNote->items as $creditNoteItem)
                                    <div class="repeater-wrapper pt-0 pt-md-9" data-repeater-item>
                                        <div class="d-flex border rounded position-relative pe-0">
                                            <div class="row w-100 p-6 g-6">
                                                <input type="hidden" name="id"
                                                    value="{{ $creditNoteItem->id }}" />
                                                <div class="col-md-4 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Item</p>
                                                    <select class="form-select item-details" name="item_id">
                                                        <option value=""></option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-price="{{ number_format($item->selling_unit_price ?? 0, 2, '.', '') }}"
                                                                {{ $creditNoteItem->item_id == $item->id ? 'selected' : '' }}>
                                                                {{ $item->name ?? 'Unknown' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Selling Unit Price</p>
                                                    <input type="number" class="form-control selling-unit-price"
                                                        name="unit_price"
                                                        value="{{ number_format($creditNoteItem->selling_unit_price, 2, '.', '') }}"
                                                        step="0.01" min="0">
                                                    <div class="text-heading mt-2">
                                                        <div class="mb-1"><small
                                                                class="text-muted">Discount:</small><span
                                                                class="discount me-2">{{ $creditNoteItem->discount_rate ?? 0 }}%</span><small
                                                                class="text-muted">Amt:</small><span
                                                                class="item-discount-amount text-success fw-medium">$0.00</span>
                                                        </div>
                                                        <div class="mb-1"><small class="text-muted">Tax:</small><span
                                                                class="tax-1 me-2">{{ optional($creditNoteItem->tax)->percentage ?? 0 }}%</span><input
                                                                type="hidden" class="item-tax-id" name="tax_id"
                                                                value="{{ $creditNoteItem->tax_id ?? 0 }}"><small
                                                                class="text-muted">Amt:</small><span
                                                                class="item-tax-amount text-primary fw-medium">$0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Qty</p>
                                                    <input type="number" class="form-control quantity" name="quantity"
                                                        value="{{ $creditNoteItem->quantity }}" min="1">
                                                </div>
                                                <div class="col-md-3 col-12 pe-0">
                                                    <p class="h6 repeater-title">Price</p>
                                                    <input type="number" class="form-control total-price"
                                                        name="total_price" readonly>
                                                </div>
                                            </div>
                                            <div
                                                class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                                <i class="icon-base bx bx-x icon-lg cursor-pointer"
                                                    data-repeater-delete></i>
                                                <div class="dropdown">
                                                    <i class="icon-base bx bx-cog icon-lg cursor-pointer" role="button"
                                                        data-bs-toggle="dropdown" data-bs-auto-close="false"></i>
                                                    <div class="dropdown-menu dropdown-menu-end w-px-300 p-4">
                                                        <div class="row g-3">
                                                            <div class="col-12"><label class="form-label">Item Level
                                                                    Discount (%)</label><input type="number"
                                                                    class="form-control item-discount-input"
                                                                    value="{{ $creditNoteItem->discount_rate ?? 0 }}"
                                                                    min="0" max="100" /></div>
                                                            <div class="col-12"><label class="form-label">Item Level
                                                                    Tax</label><select class="form-select item-tax-select">
                                                                    <option value="0" data-percentage="0"
                                                                        {{ !$creditNoteItem->tax_id ? 'selected' : '' }}>0%
                                                                    </option>
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}"
                                                                            data-percentage="{{ $tax->percentage }}"
                                                                            {{ $creditNoteItem->tax_id == $tax->id ? 'selected' : '' }}>
                                                                            {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                        </option>
                                                                    @endforeach
                                                                </select></div>
                                                        </div>
                                                        <div class="dropdown-divider my-4"></div>
                                                        <button type="button"
                                                            class="btn btn-label-primary btn-apply-changes">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="repeater-wrapper pt-0 pt-md-9" data-repeater-item>
                                        <div class="d-flex border rounded position-relative pe-0">
                                            <div class="row w-100 p-6 g-6">
                                                <div class="col-md-4 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Item</p><select
                                                        class="form-select item-details" name="item_id">
                                                        <option value=""></option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-price="{{ number_format($item->selling_unit_price ?? 0, 2, '.', '') }}">
                                                                {{ $item->name ?? 'Unknown' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Selling Unit Price</p><input
                                                        type="number" class="form-control selling-unit-price"
                                                        name="unit_price" step="0.01" min="0">
                                                    <div class="text-heading mt-2">
                                                        <div class="mb-1"><small
                                                                class="text-muted">Discount:</small><span
                                                                class="discount me-2">0%</span><small
                                                                class="text-muted">Amt:</small><span
                                                                class="item-discount-amount text-success fw-medium">$0.00</span>
                                                        </div>
                                                        <div class="mb-1"><small class="text-muted">Tax:</small><span
                                                                class="tax-1 me-2">0%</span><input type="hidden"
                                                                class="item-tax-id" name="tax_id" value="0"><small
                                                                class="text-muted">Amt:</small><span
                                                                class="item-tax-amount text-primary fw-medium">$0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Qty</p><input type="number"
                                                        class="form-control quantity" name="quantity" value="1"
                                                        min="1">
                                                </div>
                                                <div class="col-md-3 col-12 pe-0">
                                                    <p class="h6 repeater-title">Price</p><input type="number"
                                                        class="form-control total-price" name="total_price" readonly>
                                                </div>
                                            </div>
                                            <div
                                                class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                                <i class="icon-base bx bx-x icon-lg cursor-pointer"
                                                    data-repeater-delete></i>
                                                <div class="dropdown">
                                                    <i class="icon-base bx bx-cog icon-lg cursor-pointer" role="button"
                                                        data-bs-toggle="dropdown" data-bs-auto-close="false"></i>
                                                    <div class="dropdown-menu dropdown-menu-end w-px-300 p-4">
                                                        <div class="row g-3">
                                                            <div class="col-12"><label class="form-label">Discount
                                                                    (%)</label><input type="number"
                                                                    class="form-control item-discount-input"
                                                                    value="0" min="0" max="100" /></div>
                                                            <div class="col-12"><label
                                                                    class="form-label">Tax</label><select
                                                                    class="form-select item-tax-select">
                                                                    <option value="0" data-percentage="0" selected>0%
                                                                    </option>
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}"
                                                                            data-percentage="{{ $tax->percentage }}">
                                                                            {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="dropdown-divider my-4"></div>
                                                        <button type="button"
                                                            class="btn btn-label-primary btn-apply-changes">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <div class="row">
                                <div class="col-12"><button type="button" class="btn btn-sm btn-primary"
                                        data-repeater-create><i class="icon-base bx bx-plus icon-xs me-1_5"></i>Add
                                        Item</button></div>
                            </div>
                        </div>
                    </form>
                </div>
                <hr class="my-0" />

                {{-- Calculation Summary --}}
                <div class="card-body px-0">
                    <div class="row row-gap-4">
                        <div class="col-md-6 mb-md-0 mb-4"></div>
                        <div class="col-md-6 d-flex justify-content-end">
                            <div class="invoice-calculations">
                                <div class="d-flex justify-content-between mb-2"><span
                                        class="w-px-100 text-nowrap">Subtotal:</span><span class="fw-medium text-heading"
                                        id="subtotal-display">$0.00</span></div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between mb-2"><span
                                        class="w-px-100 text-danger text-nowrap">Item Level Discount:</span><span
                                        class="fw-medium text-heading text-danger"
                                        id="item-level-discount-total-display">-$0.00</span></div>
                                <div class="d-flex justify-content-between mb-2"><span
                                        class="w-px-100 text-primary text-nowrap">Item Level Tax:</span><span
                                        class="fw-medium text-heading text-primary"
                                        id="item-level-tax-total-display">+$0.00</span></div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between mb-2"><span
                                        class="w-px-100 fw-medium text-nowrap">Document Net Amount:</span><span
                                        class="fw-medium text-heading"
                                        id="subtotal-after-item-adjustments-display">$0.00</span></div>
                                <div class="d-flex justify-content-between align-items-center mb-2"><span
                                        class="text-nowrap me-2">Document Discount:</span>
                                    <div class="d-flex align-items-center"><input type="number" id="discount"
                                            class="form-control form-control-sm" value="0" style="width: 80px;"
                                            min="0"><select class="form-select form-select-sm ms-2"
                                            style="width: 70px;" id="discount-type">
                                            <option value="%" selected>%</option>
                                            <option value="Amount">$</option>
                                        </select><span class="fw-medium text-heading ms-2" id="discount-display"
                                            style="width: 60px;">-$0.00</span></div>
                                </div>
                                <div class="d-flex justify-content-between mb-2"><span
                                        class="w-px-100 text-nowrap">Document Taxable Amount:</span><span
                                        class="fw-medium text-heading" id="taxable-amount-display">$0.00</span></div>
                                <div class="d-flex justify-content-between align-items-center mb-2"><span
                                        class="text-nowrap me-2">Document Tax:</span>
                                    <div class="d-flex align-items-center"><select id="tax"
                                            class="form-select form-control-sm" style="width: 120px;">
                                            <option value="0" data-rate="0" selected>No tax (0%)</option>
                                            @foreach ($taxes as $tax)
                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}"
                                                    {{ $creditNote->document_tax_id == $tax->id ? 'selected' : '' }}>
                                                    {{ $tax->name }} ({{ $tax->percentage }}%)</option>
                                            @endforeach
                                        </select><span class="fw-medium text-heading ms-2" id="tax-amount-display"
                                            style="width: 60px;">+$0.00</span></div>
                                </div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between"><span
                                        class="w-px-100 fw-bold text-nowrap">Total:</span><span
                                        class="fw-bold text-heading" id="grand-total">$0.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions Panel --}}
        <div class="col-lg-3 col-12 invoice-actions">
            <div class="card mb-6">
                <div class="card-body">
                    <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas"
                        data-bs-target="#sendInvoiceOffcanvas"><span
                            class="d-flex align-items-center justify-content-center text-nowrap"><i
                                class="icon-base bx bx-paper-plane icon-xs me-2"></i>Send Credit Note</span></button>
                    <button type="button" class="btn btn-label-secondary d-grid w-100 mb-4"
                        onclick="previewCreditNote()">Preview</button>
                    <button type="button" id="updateCreditNoteBtn" class="btn btn-label-secondary d-grid w-100"
                        onclick="updateCreditNote()">Update</button>
                </div>
            </div>
        </div>
    </div>
    @include('_partials/_offcanvas/offcanvas-send-invoice')
@endsection

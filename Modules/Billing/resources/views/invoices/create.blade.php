@extends('layouts/layoutMaster')
@section('title', 'Add - Invoice')
@section('vendor-style')
    @vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection
@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'])
@endsection
@section('page-script')
    @vite(['resources/assets/js/offcanvas-send-invoice.js', 'resources/assets/js/app-invoice-add.js'])
    <!-- Include SweetAlert if not already included -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Flatpickr for Issued Date
            const issueDatePicker = flatpickr(".invoice-date", {
                dateFormat: "m/d/Y",
                defaultDate: new Date(),
                onChange: function(selectedDates, dateStr) {
                    // Auto-update due date = issue date + 7 days
                    if (selectedDates.length > 0) {
                        let dueDate = new Date(selectedDates[0]);
                        dueDate.setDate(dueDate.getDate() + 7);
                        dueDatePicker.setDate(dueDate, true);
                    }
                }
            });
            // Flatpickr for Due Date
            const dueDatePicker = flatpickr(".due-date", {
                dateFormat: "m/d/Y",
                minDate: "today"
            });
            // Set default due date = today + 7
            let today = new Date();
            let defaultDue = new Date();
            defaultDue.setDate(today.getDate() + 7);
            dueDatePicker.setDate(defaultDue, true);
            // Initialize calculations
            calculateSubtotal();

            // Initialize jQuery Repeater event for new rows
            $(document).ready(function() {
                $('.invoice-form-container [data-repeater-list]').on('repeater-add', function(e, row) {
                    // Initialize tax display and hidden field for new rows
                    const taxDisplay = row.querySelector('.tax-1');
                    if (taxDisplay) {
                        taxDisplay.textContent = '0%';
                    }
                    const taxIdField = row.querySelector('.item-tax-id');
                    if (taxIdField) {
                        taxIdField.value = '0';
                    }
                });
            });

            // =========================================================================================
            // FIXED AND IMPROVED EVENT LISTENER FOR 'APPLY CHANGES'
            // =========================================================================================
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-apply-changes')) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdown = e.target.closest('.dropdown-menu');
                    const repeaterWrapper = dropdown.closest('.repeater-wrapper');

                    // 1. Get the new discount and tax values from the dropdown inputs
                    const discountValue = parseFloat(dropdown.querySelector('.item-discount-input').value) || 0;
                    
                    const taxSelect = dropdown.querySelector('.item-tax-select');
                    const selectedTaxOption = taxSelect.options[taxSelect.selectedIndex];
                    const taxId = taxSelect.value; // This is the tax ID (e.g., 5 or 9)
                    const taxPercentage = selectedTaxOption.getAttribute('data-percentage'); // This is the actual percentage (e.g., 20.00)

                    // 2. Update the percentage text displays in the main item row
                    const discountDisplay = repeaterWrapper.querySelector('.discount');
                    discountDisplay.textContent = discountValue + '%';

                    // Update the hidden field with the correct tax ID for form submission
                    repeaterWrapper.querySelector('.item-tax-id').value = taxId;

                    // THE FIX: This line now uses the 'taxPercentage' variable (e.g., "20.00")
                    // to update the display, ensuring the correct percentage is shown.
                    const taxDisplay = repeaterWrapper.querySelector('.tax-1');
                    taxDisplay.textContent = taxPercentage + '%';

                    // 3. Recalculate the totals for this specific item row
                    calculateItemTotal(repeaterWrapper);

                    // 4. Recalculate the grand totals for the entire invoice
                    calculateSubtotal();

                    // 5. Close the dropdown menu
                    const dropdownToggle = repeaterWrapper.querySelector('[data-bs-toggle="dropdown"]');
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }

                    // Optional: Add a temporary visual effect to confirm the update
                    const priceDisplays = [
                        repeaterWrapper.querySelector('.item-discount-amount'),
                        repeaterWrapper.querySelector('.item-tax-amount'),
                        repeaterWrapper.querySelector('.total-price')
                    ];
                    priceDisplays.forEach(el => el.classList.add('fw-bold'));
                    setTimeout(() => {
                        priceDisplays.forEach(el => el.classList.remove('fw-bold'));
                    }, 600);
                }
            });
            // =========================================================================================
            // END OF FIXED SECTION
            // =========================================================================================
        });

        // Calculate item total with discount and tax
        function calculateItemTotal(row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.selling-unit-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').textContent) || 0;

            // Read tax percent safely from the display (strip '%' if present)
            const taxText = row.querySelector('.tax-1').textContent.replace('%', '').trim();
            const taxPercent = parseFloat(taxText) || 0;

            // Calculate base amount
            const baseAmount = qty * unitPrice;

            // Discount
            const discountAmount = baseAmount * (discountPercent / 100);

            // Taxable
            const taxableAmount = baseAmount - discountAmount;

            // Tax
            const taxAmount = taxableAmount * (taxPercent / 100);

            // Final total
            const totalAmount = taxableAmount + taxAmount;

            // Update DOM
            row.querySelector('.item-discount-amount').textContent = '$' + discountAmount.toFixed(2);
            row.querySelector('.item-tax-amount').textContent = '$' + taxAmount.toFixed(2);
            row.querySelector('.total-price').value = totalAmount.toFixed(2);
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-details')) {
                let selected = e.target.options[e.target.selectedIndex];
                let wrapper = e.target.closest('.repeater-wrapper');
                if (selected.value && selected.value !== '') {
                    let price = parseFloat(selected.getAttribute('data-price')) || 0;
                    wrapper.querySelector('.selling-unit-price').value = price.toFixed(2);
                    calculateItemTotal(wrapper);
                } else {
                    // Clear fields if no item selected
                    wrapper.querySelector('.selling-unit-price').value = '0.00';
                    wrapper.querySelector('.total-price').value = '0.00';
                    wrapper.querySelector('.item-discount-amount').textContent = '$0.00';
                    wrapper.querySelector('.item-tax-amount').textContent = '$0.00';
                }
                // Update overall calculations
                calculateSubtotal();
            }

            // Handle client selection
            if (e.target.id === 'clientSelect') {
                let selected = e.target.options[e.target.selectedIndex];

                if (selected.value && selected.value !== '') {
                    let company = selected.getAttribute('data-company') || '';
                    let address = selected.getAttribute('data-address') || '';
                    let city = selected.getAttribute('data-city') || '';
                    let state = selected.getAttribute('data-state') || '';
                    let zip = selected.getAttribute('data-zip') || '';
                    let phone = selected.getAttribute('data-phone') || '';
                    let email = selected.getAttribute('data-email') || '';

                    document.getElementById('clientCompany').textContent = company || selected.textContent;

                    let fullAddress = '';
                    if (address) fullAddress += address;
                    if (city || state || zip) {
                        if (address) fullAddress += ', ';
                        fullAddress += [city, state, zip].filter(Boolean).join(', ');
                    }
                    document.getElementById('clientAddress').textContent = fullAddress;
                    document.getElementById('clientPhone').textContent = phone;
                    document.getElementById('clientEmail').textContent = email;

                    document.getElementById('client_id').value = selected.value;
                } else {
                    document.getElementById('clientCompany').textContent = 'Select a customer to view details';
                    document.getElementById('clientAddress').textContent = '';
                    document.getElementById('clientPhone').textContent = '';
                    document.getElementById('clientEmail').textContent = '';
                    document.getElementById('client_id').value = '';
                }
            }

            if (e.target.id === "discount-type" || e.target.id === "tax") {
                calculateSubtotal();
            }
        });
        document.addEventListener("input", function(e) {
            if (e.target.classList.contains('quantity') || e.target.classList.contains('selling-unit-price')) {
                let wrapper = e.target.closest('.repeater-wrapper');
                calculateItemTotal(wrapper);
                calculateSubtotal();
            }
            if (e.target.id === "discount") {
                calculateSubtotal();
            }
        });

        function calculateSubtotal() {
            let rows = document.querySelectorAll('.repeater-wrapper');
            let subtotal = 0;
            let itemLevelDiscountTotal = 0;
            let itemLevelTaxTotal = 0;

            rows.forEach(row => {
                let basePrice = (parseFloat(row.querySelector('.selling-unit-price')?.value) || 0) * (parseFloat(row
                    .querySelector('.quantity')?.value) || 0);
                subtotal += basePrice;

                let discountAmountText = row.querySelector('.item-discount-amount')?.textContent || '$0.00';
                let taxAmountText = row.querySelector('.item-tax-amount')?.textContent || '$0.00';

                itemLevelDiscountTotal += parseFloat(discountAmountText.replace('$', ''));
                itemLevelTaxTotal += parseFloat(taxAmountText.replace('$', ''));
            });

            document.getElementById("subtotal-display").textContent = '$' + subtotal.toFixed(2);
            document.getElementById("item-level-discount-total-display").textContent = '-$' + itemLevelDiscountTotal
                .toFixed(2);
            document.getElementById("item-level-tax-total-display").textContent = '+$' + itemLevelTaxTotal.toFixed(2);

            let subtotalAfterItemAdjustments = subtotal - itemLevelDiscountTotal + itemLevelTaxTotal;
            document.getElementById("subtotal-after-item-adjustments-display").textContent = '$' +
                subtotalAfterItemAdjustments.toFixed(2);

            let discountValue = parseFloat(document.getElementById("discount")?.value || 0);
            let discountType = document.getElementById("discount-type")?.value || "%";
            let discountAmount = 0;
            if (discountType === "%") {
                discountAmount = (subtotalAfterItemAdjustments * discountValue) / 100;
            } else {
                discountAmount = discountValue;
            }
            document.getElementById("discount-display").textContent = '-$' + discountAmount.toFixed(2);

            let taxableAmount = subtotalAfterItemAdjustments - discountAmount;
            if (taxableAmount < 0) taxableAmount = 0;
            document.getElementById("taxable-amount-display").textContent = '$' + taxableAmount.toFixed(2);

            let taxSelect = document.getElementById("tax");
            let taxId = taxSelect.value;
            let taxRate = 0;
            if (taxSelect.selectedIndex >= 0) {
                let selectedOption = taxSelect.options[taxSelect.selectedIndex];
                taxRate = parseFloat(selectedOption.getAttribute('data-rate') || 0);
            }

            let taxAmount = (taxableAmount * taxRate) / 100;
            document.getElementById("tax-amount-display").textContent = '+$' + taxAmount.toFixed(2);

            let grandTotal = taxableAmount + taxAmount;
            document.getElementById("grand-total").textContent = '$' + grandTotal.toFixed(2);

            // Update hidden fields for form submission
            document.getElementById("sub_total").value = subtotal.toFixed(2);
            document.getElementById("document_discount_amount").value = discountAmount.toFixed(2);
            document.getElementById("tax_amount").value = taxAmount.toFixed(2);
            document.getElementById("total_amount").value = grandTotal.toFixed(2);
            document.getElementById("tax_id").value = taxId;
            let discountTypeInt = discountType === "%" ? 1 : 2;
            document.getElementById("document_discount_type").value = discountTypeInt;
            document.getElementById("document_discount_rate").value = discountValue.toFixed(2);
        }

        function saveInvoice() {
            const form = document.getElementById('invoiceForm');
            const clientSelect = document.getElementById('clientSelect');
            const saveButton = document.getElementById('saveInvoiceBtn');

            if (!clientSelect.value) {
                Swal.fire({ icon: 'warning', title: 'Validation Error', text: 'Please select a client', confirmButtonColor: '#3085d6', });
                clientSelect.focus();
                return;
            }
            const items = document.querySelectorAll('.item-details');
            let hasValidItems = Array.from(items).some(item => item.value);
            if (!hasValidItems) {
                Swal.fire({ icon: 'warning', title: 'Validation Error', text: 'Please add at least one item', confirmButtonColor: '#3085d6', });
                return;
            }

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            let formData = new FormData(form);
            let issueDate = document.querySelector('.invoice-date').value;
            let dueDate = document.querySelector('.due-date').value;
            if (issueDate) { let parts = issueDate.split('/'); issueDate = parts[2] + '-' + parts[0] + '-' + parts[1]; }
            if (dueDate) { let parts = dueDate.split('/'); dueDate = parts[2] + '-' + parts[0] + '-' + parts[1]; }
            formData.set('issue_date', issueDate);
            formData.set('due_date', dueDate);
            formData.set('invoice_number', document.getElementById('invoiceId').value);
            
            document.querySelectorAll('.repeater-wrapper').forEach((row, index) => {
                const itemId = row.querySelector('.item-details')?.value;
                if (itemId) {
                    formData.append(`items[${index}][item_id]`, itemId);
                    formData.append(`items[${index}][quantity]`, parseFloat(row.querySelector('.quantity')?.value || 0));
                    formData.append(`items[${index}][unit_price]`, parseFloat(row.querySelector('.selling-unit-price')?.value || 0));
                    formData.append(`items[${index}][total_price]`, parseFloat(row.querySelector('.total-price')?.value || 0));
                    formData.append(`items[${index}][discount_percent]`, parseFloat(row.querySelector('.discount').textContent) || 0);
                    formData.append(`items[${index}][tax_id]`, row.querySelector('.item-tax-id').value);
                }
            });

            fetch("{{ route('billing.invoices.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async response => {
                    if (!response.ok) {
                        let errorData;
                        try { errorData = await response.json(); } catch (e) { throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`); }
                        throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save';
                    const type = data.type || (data.success ? "success" : "error");
                    const message = data.message || "Operation completed.";

                    if (data.success) {
                        Swal.fire({ icon: type, title: 'Success!', text: message, confirmButtonColor: '#3085d6', })
                        .then(() => { window.location.href = "{{ route('accounting.billings.index') }}"; });
                    } else {
                        Swal.fire({ icon: type, title: 'Error!', text: message, confirmButtonColor: '#3085d6', });
                    }
                })
                .catch(error => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save';
                    console.error('AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: `<div>An error occurred while saving the invoice.</div><div class="text-muted small mt-2">${error.message}</div>`,
                        confirmButtonColor: '#3085d6',
                    });
                });
        }

        function previewInvoice() {
            Swal.fire({ icon: 'info', title: 'Preview', text: 'Preview functionality will be implemented soon.', confirmButtonColor: '#3085d6', });
        }
    </script>
@endsection

@section('content')
    @php
        $branchLogo = null;
        if (isset($branch) && !empty($branch)) {
            $extensions = ['png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'];
            foreach ($extensions as $ext) {
                $path = public_path('storage/branch_logos/' . $branch->id . '.' . $ext);
                if (file_exists($path)) {
                    $branchLogo = asset('storage/branch_logos/' . $branch->id . '.' . $ext);
                    break;
                }
            }
        }
        $company = $company ?? null;
        $branch = $branch ?? null;
        $clients = $clients ?? [];
        $items = $items ?? [];
        $taxes = $taxes ?? [];
        $nextInvoiceNumber = $nextInvoiceNumber ?? '#INV-001';
        $document_prefix = $document_prefix ?? 'INV-';
    @endphp
    <div class="row invoice-add">
        <!-- Invoice Add-->
        <div class="col-lg-9 col-12 mb-lg-0 mb-6">
            <div class="card invoice-preview-card p-sm-12 p-6">
                <div class="card-body invoice-preview-header rounded">
                    <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
                        <div class="mb-md-0 mb-6">
                            <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                                <span class="app-brand-logo demo">
                                    @if ($branchLogo)
                                        <img src="{{ $branchLogo }}" alt="Branch Logo" height="40">
                                    @else
                                        @include('_partials.macros')
                                        <span class="app-brand-text demo fw-bold ms-50">{{ config('variables.templateName') }}</span>
                                    @endif
                                </span>
                            </div>
                            @if ($company && $branch)
                                <p class="mb-2">{{ $company->name ?? 'Company Name' }}</p>
                                <p class="mb-2">{{ $company->city ?? '' }}@if ($company->city && ($company->state || $company->zip)), @endif{{ $company->state ?? '' }} {{ $company->zip ?? '' }}</p>
                                <p class="mb-2">{{ $branch->name ?? 'Branch Name' }}</p>
                                <p class="mb-3">{{ $branch->address ?? 'Branch Address' }}</p>
                            @else
                                <p class="text-muted">No company details available</p>
                            @endif
                        </div>
                        <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
                            <dl class="row mb-0 gx-4">
                                <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                                    <span class="h5 text-capitalize mb-0 text-nowrap">Invoice</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control" disabled
                                        placeholder="{{ $nextInvoiceNumber ?? '#3905' }}"
                                        value="{{ $nextInvoiceNumber ?? '#3905' }}" id="invoiceId" name="invoice_number" />
                                </dd>
                                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                                    <span class="fw-normal">Date Issued:</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control invoice-date" placeholder="MM/DD/YYYY" />
                                </dd>
                                <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                                    <span class="fw-normal">Due Date:</span>
                                </dt>
                                <dd class="col-sm-7 mb-0">
                                    <input type="text" class="form-control due-date" placeholder="MM/DD/YYYY" />
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0">
                    <div class="row">
                        <div class="col-md-6 col-sm-5 col-12 mb-sm-0 mb-6">
                            <h6>Invoice To:</h6>
                            <select class="form-select mb-4 w-75" id="clientSelect" name="client_id_select">
                                <option value="">-- Select Customer --</option>
                                @if (count($clients) > 0)
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            data-company="{{ $client->company_name ?? '' }}"
                                            data-address="{{ $client->address ?? '' }}"
                                            data-city="{{ $client->city ?? '' }}" data-state="{{ $client->state ?? '' }}"
                                            data-zip="{{ $client->zip ?? '' }}" data-phone="{{ $client->phone ?? '' }}"
                                            data-email="{{ $client->email ?? '' }}">
                                            {{ $client->name ?? 'Unknown Client' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No clients available</option>
                                @endif
                            </select>
                            <div id="clientDetails">
                                <p class="mb-1" id="clientCompany">Select a customer to view details</p>
                                <p class="mb-1" id="clientAddress"></p>
                                <p class="mb-1" id="clientPhone"></p>
                                <p class="mb-0" id="clientEmail"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-6" />
                <div class="card-body pt-0 px-0">
                    <form class="source-item" id="invoiceForm" method="POST"
                        action="{{ route('billing.invoices.store') }}">
                        @csrf
                        <!-- Hidden fields -->
                        <input type="hidden" name="client_id" id="client_id" value="">
                        <input type="hidden" name="sub_total" id="sub_total" value="0">
                        <input type="hidden" name="document_discount_type" id="document_discount_type" value="1">
                        <input type="hidden" name="document_discount_rate" id="document_discount_rate" value="0">
                        <input type="hidden" name="document_discount_amount" id="document_discount_amount" value="0">
                        <input type="hidden" name="tax_id" id="tax_id" value="0">
                        <input type="hidden" name="tax_amount" id="tax_amount" value="0">
                        <input type="hidden" name="total_amount" id="total_amount" value="0">
                        <input type="hidden" name="payment_status" value="unpaid">

                        <div class="invoice-form-container">
                            <div class="mb-4" data-repeater-list="items">
                                <div class="repeater-wrapper pt-0 pt-md-9" data-repeater-item>
                                    <div class="d-flex border rounded position-relative pe-0">
                                        <div class="row w-100 p-6 g-6">
                                            <div class="col-md-4 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Item</p>
                                                <select class="form-select item-details" name="item_id">
                                                    <option value="">-- Select Item --</option>
                                                    @if (count($items) > 0)
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}" data-price="{{ number_format($item->selling_unit_price ?? 0, 2, '.', '') }}">
                                                                {{ $item->name ?? 'Unknown Item' }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option value="" disabled>No items available</option>
                                                    @endif
                                                </select>
                                            </div>

                                            <div class="col-md-3 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Selling Unit Price</p>
                                                <input type="number" class="form-control selling-unit-price"
                                                    name="unit_price" placeholder="999999999.00" step="0.01"
                                                    min="0">
                                                <div class="text-heading mt-2">
                                                    <div class="mb-1">
                                                        <small class="text-muted">Discount:</small>
                                                        <span class="discount me-2">0%</span>
                                                        <small class="text-muted">Amt:</small>
                                                        <span class="item-discount-amount text-success fw-medium">$0.00</span>
                                                    </div>
                                                    <div class="mb-1">
                                                        <small class="text-muted">Tax:</small>
                                                        <span class="tax-1 me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Item Tax Rate">0%</span>
                                                        <input type="hidden" class="item-tax-id" value="0">
                                                        <small class="text-muted">Amt:</small>
                                                        <span class="item-tax-amount text-primary fw-medium">$0.00</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Qty</p>
                                                <input type="number" class="form-control quantity" name="quantity"
                                                    placeholder="999" min="1" value="1" step="1">
                                            </div>

                                            <div class="col-md-3 col-12 pe-0">
                                                <p class="h6 repeater-title">Price</p>
                                                <input type="number" class="form-control total-price" name="total_price"
                                                    placeholder="99999.00" readonly>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                            <i class="icon-base bx bx-x icon-lg cursor-pointer" data-repeater-delete></i>
                                            <div class="dropdown">
                                                <i class="icon-base bx bx-cog icon-lg cursor-pointer more-options-dropdown"
                                                    role="button" id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                    data-bs-auto-close="false" aria-expanded="false"></i>

                                                <div class="dropdown-menu dropdown-menu-end w-px-300 p-4"
                                                    aria-labelledby="dropdownMenuButton">
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label class="form-label">Item Level Discount (%)</label>
                                                            <input type="number" class="form-control item-discount-input"
                                                                min="0" max="100" step="0.01" placeholder="Enter discount %" />
                                                            <small class="text-muted">Valid range: 0-100%</small>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Item Level Tax</label>
                                                            <select name="tax-1-input" class="form-select item-tax-select">
                                                                <option value="0" data-percentage="0" selected>0%</option>
                                                                @if (count($taxes) > 0)
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}" data-percentage="{{ $tax->percentage }}">
                                                                            {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                        </option>
                                                                    @endforeach
                                                                @else
                                                                    <option value="0" data-percentage="0">No tax (0%)</option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown-divider my-4"></div>
                                                    <button type="button" class="btn btn-label-primary btn-apply-changes">Apply Changes</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-primary" data-repeater-create><i class="icon-base bx bx-plus icon-xs me-1_5"></i>Add Item</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <hr class="my-0" />
                <div class="card-body px-0">
                    <div class="row row-gap-4">
                        <div class="col-md-6 mb-md-0 mb-4">
                        </div>
                        <div class="col-md-6 d-flex justify-content-end">
                            <div class="invoice-calculations">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Subtotal:</span>
                                    <span class="fw-medium text-heading" id="subtotal-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Item Level Discount:</span>
                                    <span class="fw-medium text-heading" id="item-level-discount-total-display">-$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Item Level Tax:</span>
                                    <span class="fw-medium text-heading" id="item-level-tax-total-display">+$0.00</span>
                                </div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100 fw-medium">Net Amount:</span>
                                    <span class="fw-medium text-heading" id="subtotal-after-item-adjustments-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex align-items-center w-px-100"><span>Discount:</span></div>
                                    <div class="d-flex align-items-center">
                                        <input type="number" id="discount" class="form-control form-control-sm me-2" value="0" style="width: 80px;" min="0">
                                        <select class="form-select form-select-sm me-2" style="width: 70px;" id="discount-type">
                                            <option value="%" selected>%</option>
                                            <option value="Amount">$</option>
                                        </select>
                                        <span class="fw-medium text-heading" id="discount-display" style="width: 60px;">-$0.00</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Taxable Amount:</span>
                                    <span class="fw-medium text-heading" id="taxable-amount-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex align-items-center w-px-100"><span>Tax:</span></div>
                                    <div class="d-flex align-items-center">
                                        <select id="tax" class="form-select form-control-sm me-2" style="width: 120px;">
                                            <option value="0" data-rate="0" selected>No tax (0%)</option>
                                            @foreach ($taxes as $tax)
                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}">
                                                    {{ $tax->name }} ({{ $tax->percentage }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="fw-medium text-heading" id="tax-amount-display" style="width: 60px;">+$0.00</span>
                                    </div>
                                </div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between">
                                    <span class="w-px-100 fw-bold">Total:</span>
                                    <span class="fw-bold text-heading" id="grand-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body px-0 pb-0">
                    <div class="row"><div class="col-12"></div></div>
                </div>
            </div>
        </div>
        <!-- /Invoice Add-->
        <!-- Invoice Actions -->
        <div class="col-lg-3 col-12 invoice-actions">
            <div class="card mb-6">
                <div class="card-body">
                    <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#sendInvoiceOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="icon-base bx bx-paper-plane icon-xs me-2"></i>Send Invoice</span>
                    </button>
                    <button type="button" class="btn btn-label-secondary d-grid w-100 mb-4" onclick="previewInvoice()">Preview</button>
                    <button type="button" id="saveInvoiceBtn" class="btn btn-label-secondary d-grid w-100" onclick="saveInvoice()">Save</button>
                </div>
            </div>
            <div>
                <label for="acceptPaymentsVia" class="form-label">Accept payments via</label>
                <select class="form-select mb-6" id="acceptPaymentsVia">
                    <option value="Bank Account">Bank Account</option>
                    <option value="Paypal">Paypal</option>
                    <option value="Card">Credit/Debit Card</option>
                    <option value="UPI Transfer">UPI Transfer</option>
                </select>
                <div class="d-flex justify-content-between mb-2">
                    <label for="payment-terms">Payment Terms</label>
                    <div class="form-check form-switch me-n2"><input type="checkbox" class="form-check-input" id="payment-terms" checked /></div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <label for="client-notes">Client Notes</label>
                    <div class="form-check form-switch me-n2"><input type="checkbox" class="form-check-input" id="client-notes" checked /></div>
                </div>
                <div class="d-flex justify-content-between">
                    <label for="payment-stub">Payment Stub</label>
                    <div class="form-check form-switch me-n2"><input type="checkbox" class="form-check-input" id="payment-stub" checked /></div>
                </div>
            </div>
        </div>
        <!-- /Invoice Actions -->
    </div>
    <!-- Offcanvas -->
    @include('_partials/_offcanvas/offcanvas-send-invoice')
    <!-- /Offcanvas -->
@endsection
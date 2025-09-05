@extends('layouts/layoutMaster')
@section('title', 'Add - Debit Note')
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
            const issueDatePicker = flatpickr(".debit-note-date", {
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
            // =========================================================================================
            // MODIFIED AND IMPROVED EVENT LISTENER FOR 'APPLY CHANGES'
            // =========================================================================================
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-apply-changes')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropdown = e.target.closest('.dropdown-menu');
                    const repeaterWrapper = dropdown.closest('.repeater-wrapper');
                    // 1. Get the new discount and tax values from the dropdown inputs
                    const discountValue = parseFloat(dropdown.querySelector('#discountInput').value) || 0;
                    const taxValue = dropdown.querySelector('#taxInput1').value || '0%'; // e.g., "5%"
                    // 2. Update the percentage text displays in the main item row
                    const discountDisplay = repeaterWrapper.querySelector('.discount');
                    discountDisplay.textContent = discountValue + '%';
                    const taxDisplay = repeaterWrapper.querySelector('.tax-1');
                    taxDisplay.textContent = taxValue;
                    // 3. Call the existing calculation function. It will now use the new percentages
                    // to calculate and display the discount amount, tax amount, and total price.
                    calculateItemTotal(repeaterWrapper);
                    // 4. Recalculate the grand totals for the entire debit note
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
            // END OF MODIFIED SECTION
            // =========================================================================================
        });
        // Calculate item total with discount and tax
        function calculateItemTotal(row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.selling-unit-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').textContent) || 0;
            const taxPercent = parseFloat(row.querySelector('.tax-1').textContent) || 0;
            // Calculate base amount
            const baseAmount = qty * unitPrice;
            // Calculate discount amount
            const discountAmount = baseAmount * (discountPercent / 100);
            // Calculate taxable amount after discount
            const taxableAmount = baseAmount - discountAmount;
            // Calculate tax amount
            const taxAmount = taxableAmount * (taxPercent / 100);
            // Calculate final total
            const totalAmount = taxableAmount + taxAmount;
            // Update display elements for this item row
            row.querySelector('.item-discount-amount').textContent = '$' + discountAmount.toFixed(2);
            row.querySelector('.item-tax-amount').textContent = '$' + taxAmount.toFixed(2);
            row.querySelector('.total-price').value = totalAmount.toFixed(2);
        }
        document.addEventListener('change', function(e) {
            // =======================================================================
            // FINAL FIX: No longer references item-description in any way
            // =======================================================================
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

            // Handle invoice selection (replacing client selection)
            if (e.target.id === 'invoiceSelect') {
                let selected = e.target.options[e.target.selectedIndex];
                if (selected.value && selected.value !== '') {
                    let customer = selected.getAttribute('data-customer') || '';
                    let company = selected.getAttribute('data-company') || '';
                    let address = selected.getAttribute('data-address') || '';
                    let city = selected.getAttribute('data-city') || '';
                    let state = selected.getAttribute('data-state') || '';
                    let zip = selected.getAttribute('data-zip') || '';
                    let phone = selected.getAttribute('data-phone') || '';
                    let email = selected.getAttribute('data-email') || '';
                    // Update customer details display
                    document.getElementById('customerCompany').textContent = company || customer;
                    let fullAddress = '';
                    if (address) fullAddress += address;
                    if (city || state || zip) {
                        if (address) fullAddress += ', ';
                        fullAddress += [city, state, zip].filter(Boolean).join(', ');
                    }
                    document.getElementById('customerAddress').textContent = fullAddress;
                    document.getElementById('customerPhone').textContent = phone;
                    document.getElementById('customerEmail').textContent = email;
                    // Update hidden invoice_id field
                    document.getElementById('invoice_id').value = selected.value;
                } else {
                    // Clear customer details
                    document.getElementById('customerCompany').textContent = 'Select an invoice to view details';
                    document.getElementById('customerAddress').textContent = '';
                    document.getElementById('customerPhone').textContent = '';
                    document.getElementById('customerEmail').textContent = '';
                    // Reset hidden invoice_id field
                    document.getElementById('invoice_id').value = '';
                }
            }
            // Update when discount type changes
            if (e.target.id === "discount-type") {
                calculateSubtotal();
            }
            // Update when tax selection changes
            if (e.target.id === "tax") {
                calculateSubtotal();
            }
        });
        document.addEventListener("input", function(e) {
            if (e.target.classList.contains('quantity') || e.target.classList.contains('selling-unit-price')) {
                let wrapper = e.target.closest('.repeater-wrapper');
                calculateItemTotal(wrapper);
                // Update overall calculations
                calculateSubtotal();
            }
            // Update when discount changes
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
                // Calculate item-level discount and tax totals
                let discountAmountText = row.querySelector('.item-discount-amount')?.textContent || '$0.00';
                let taxAmountText = row.querySelector('.item-tax-amount')?.textContent || '$0.00';
                itemLevelDiscountTotal += parseFloat(discountAmountText.replace('$', ''));
                itemLevelTaxTotal += parseFloat(taxAmountText.replace('$', ''));
            });

            // update subtotal display
            document.getElementById("subtotal-display").textContent = '$' + subtotal.toFixed(2);
            // Update item-level discount and tax totals
            document.getElementById("item-level-discount-total-display").textContent = '-$' + itemLevelDiscountTotal
                .toFixed(2);
            document.getElementById("item-level-tax-total-display").textContent = '+$' + itemLevelTaxTotal.toFixed(2);

            // Calculate subtotal after item-level adjustments
            let subtotalAfterItemAdjustments = subtotal - itemLevelDiscountTotal + itemLevelTaxTotal;
            document.getElementById("subtotal-after-item-adjustments-display").textContent = '$' +
                subtotalAfterItemAdjustments.toFixed(2);

            // Discount logic (document-level)
            let discountValue = parseFloat(document.getElementById("discount")?.value || 0);
            let discountType = document.getElementById("discount-type")?.value || "%";
            let discountAmount = 0;
            if (discountType === "%") {
                discountAmount = (subtotalAfterItemAdjustments * discountValue) / 100;
            } else {
                discountAmount = discountValue;
            }
            // Update discount display
            document.getElementById("discount-display").textContent = '-$' + discountAmount.toFixed(2);
            // taxable amount = subtotal after item adjustments - document discount
            let taxableAmount = subtotalAfterItemAdjustments - discountAmount;
            if (taxableAmount < 0) taxableAmount = 0;
            // update taxable amount display
            document.getElementById("taxable-amount-display").textContent = '$' + taxableAmount.toFixed(2);
            // Tax calculation (document-level)
            let taxSelect = document.getElementById("tax");
            let taxId = taxSelect.value;
            let taxRate = 0;
            // Get the tax rate from the selected option's data attribute
            if (taxSelect.selectedIndex >= 0) {
                let selectedOption = taxSelect.options[taxSelect.selectedIndex];
                taxRate = parseFloat(selectedOption.getAttribute('data-rate') || 0);
            }
            let taxAmount = (taxableAmount * taxRate) / 100;
            document.getElementById("tax-amount-display").textContent = '+$' + taxAmount.toFixed(2);
            // grand total
            let grandTotal = taxableAmount + taxAmount;
            document.getElementById("grand-total").textContent = '$' + grandTotal.toFixed(2);
            // Update hidden fields for form submission
            document.getElementById("sub_total").value = subtotal.toFixed(2);
            document.getElementById("document_discount_amount").value = discountAmount.toFixed(2);
            document.getElementById("tax_amount").value = taxAmount.toFixed(2);
            document.getElementById("total_amount").value = grandTotal.toFixed(2);
            document.getElementById("tax_id").value = taxId;
            // Update discount type and rate (convert to integer values for database)
            // Assuming 1 = percentage, 2 = fixed amount
            let discountTypeInt = discountType === "%" ? 1 : 2;
            document.getElementById("document_discount_type").value = discountTypeInt;
            document.getElementById("document_discount_rate").value = discountValue.toFixed(2);
        }

        function saveDebitNote() {
            const form = document.getElementById('debitNoteForm');
            const invoiceSelect = document.getElementById('invoiceSelect');
            const saveButton = document.getElementById('saveDebitNoteBtn');

            // Validate invoice selection
            if (!invoiceSelect.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select an invoice',
                    confirmButtonColor: '#3085d6',
                });
                invoiceSelect.focus();
                return;
            }

            // Validate at least one item is selected
            const items = document.querySelectorAll('.item-details');
            let hasValidItems = Array.from(items).some(item => item.value);
            if (!hasValidItems) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please add at least one item',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

            // Show loading state
            saveButton.disabled = true;
            saveButton.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            // Prepare form data
            let formData = new FormData(form);

            // Format dates to Y-m-d format for database
            let issueDate = document.querySelector('.debit-note-date').value;
            let dueDate = document.querySelector('.due-date').value;

            // Convert from m/d/Y to Y-m-d
            if (issueDate) {
                let parts = issueDate.split('/');
                issueDate = parts[2] + '-' + parts[0] + '-' + parts[1];
            }
            if (dueDate) {
                let parts = dueDate.split('/');
                dueDate = parts[2] + '-' + parts[0] + '-' + parts[1];
            }

            // Add issue date and due date
            formData.set('issue_date', issueDate);
            formData.set('due_date', dueDate);

            // Get debit note number from the disabled input
            let debitNoteNumber = document.getElementById('debitNoteId').value;
            formData.set('debit_note_number', debitNoteNumber);

            // Get document discount type and value from hidden fields which are correctly set by calculateSubtotal()
            formData.set('document_discount_type', document.getElementById("document_discount_type").value);
            formData.set('document_discount_rate', document.getElementById("document_discount_rate").value);

            // We use document_discount_amount which is also set by calculateSubtotal()
            formData.set('document_discount_amount', document.getElementById("document_discount_amount").value);

            // FIXED: Properly handle item data submission
            const repeaterWrappers = document.querySelectorAll('.repeater-wrapper');
            repeaterWrappers.forEach((row, index) => {
                const itemId = row.querySelector('.item-details')?.value || '';
                const qty = row.querySelector('.quantity')?.value || '0';
                const unitPrice = row.querySelector('.selling-unit-price')?.value || '0.00';
                const totalPrice = row.querySelector('.total-price')?.value || '0.00';

                const discountElement = row.querySelector('.discount');
                const itemDiscountPercent = discountElement ? parseFloat(discountElement.textContent) || 0 : 0;

                const taxElement = row.querySelector('.tax-1');
                const itemTaxPercent = taxElement ? parseFloat(taxElement.textContent) || 0 : 0;

                // Always append all items, even if empty
                formData.append(`items[${index}][item_id]`, itemId);
                formData.append(`items[${index}][quantity]`, qty);
                formData.append(`items[${index}][unit_price]`, unitPrice);
                formData.append(`items[${index}][total_price]`, totalPrice);
                formData.append(`items[${index}][discount_percent]`, itemDiscountPercent);
                formData.append(`items[${index}][tax_id]`, itemTaxPercent);
            });

            // Log for debugging
            console.log('Form data being sent:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // AJAX request
            fetch("{{ route('billing.debit-notes.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async response => {
                    // Check if the response is ok (status in the range 200-299)
                    if (!response.ok) {
                        // Try to get the error message from the response
                        let errorData;
                        try {
                            errorData = await response.json();
                        } catch (e) {
                            // If we can't parse JSON, use the status text
                            throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                        }
                        throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Reset button state
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save';

                    const type = data.type || (data.success ? "success" : "error");
                    const message = data.message || "Operation completed.";

                    if (data.success) {
                        Swal.fire({
                            icon: type,
                            title: 'Success!',
                            text: message,
                            confirmButtonColor: '#3085d6',
                        }).then((result) => {
                            window.location.href = "{{ route('billing.debit-notes.index') }}";
                        });
                    } else {
                        Swal.fire({
                            icon: type,
                            title: 'Error!',
                            text: message,
                            confirmButtonColor: '#3085d6',
                        });
                    }
                })
                .catch(error => {
                    // Reset button state
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save';

                    console.error('AJAX error:', error);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: `<div>An error occurred while saving the debit note.</div>
                  <div class="text-muted small mt-2">${error.message}</div>`,
                        confirmButtonColor: '#3085d6',
                    });
                });
        }

        function previewDebitNote() {
            Swal.fire({
                icon: 'info',
                title: 'Preview',
                text: 'Preview functionality will be implemented soon.',
                confirmButtonColor: '#3085d6',
            });
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
        $invoices = $invoices ?? []; // Changed from clients to invoices
        $items = $items ?? [];
        $taxes = $taxes ?? [];
        $nextDebitNoteNumber = $nextDebitNoteNumber ?? '#DBN-001';
        $document_prefix = $document_prefix ?? 'DBN-';
    @endphp
    <div class="row invoice-add">
        <!-- Debit Note Add-->
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
                                        <span
                                            class="app-brand-text demo fw-bold ms-50">{{ config('variables.templateName') }}</span>
                                    @endif
                                </span>
                            </div>
                            @if ($company && $branch)
                                <p class="mb-2">{{ $company->name ?? 'Company Name' }}</p>
                                <p class="mb-2">{{ $company->city ?? '' }}@if ($company->city && ($company->state || $company->zip))
                                        ,
                                    @endif{{ $company->state ?? '' }} {{ $company->zip ?? '' }}</p>
                                <p class="mb-2">{{ $branch->name ?? 'Branch Name' }}</p>
                                <p class="mb-3">{{ $branch->address ?? 'Branch Address' }}</p>
                            @else
                                <p class="text-muted">No company details available</p>
                            @endif
                        </div>
                        <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
                            <dl class="row mb-0 gx-4">
                                <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                                    <span class="h5 text-capitalize mb-0 text-nowrap">Debit Note</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control" disabled
                                        placeholder="{{ $nextDebitNoteNumber ?? '#3905' }}"
                                        value="{{ $nextDebitNoteNumber ?? '#3905' }}" id="debitNoteId"
                                        name="debit_note_number" />
                                </dd>
                                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                                    <span class="fw-normal">Date Issued:</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control debit-note-date" placeholder="MM/DD/YYYY" />
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
                            <h6>Invoice:</h6>
                            <select class="form-select mb-4 w-75" id="invoiceSelect" name="invoice_id_select">
                                <option value="">-- Select Invoice --</option>
                                @if (count($invoices) > 0)
                                    @foreach ($invoices as $invoice)
                                        <option value="{{ $invoice->id }}"
                                            data-customer="{{ $invoice->customer->name ?? '' }}"
                                            data-company="{{ $invoice->customer->company_name ?? '' }}"
                                            data-address="{{ $invoice->customer->address ?? '' }}"
                                            data-city="{{ $invoice->customer->city ?? '' }}"
                                            data-state="{{ $invoice->customer->state ?? '' }}"
                                            data-zip="{{ $invoice->customer->zip ?? '' }}"
                                            data-phone="{{ $invoice->customer->phone ?? '' }}"
                                            data-email="{{ $invoice->customer->email ?? '' }}">
                                            {{ $invoice->invoice_number }} -
                                            {{ $invoice->customer->name ?? 'Unknown Customer' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No invoices available</option>
                                @endif
                            </select>
                            <div id="customerDetails">
                                <p class="mb-1" id="customerCompany">Select an invoice to view details</p>
                                <p class="mb-1" id="customerAddress"></p>
                                <p class="mb-1" id="customerPhone"></p>
                                <p class="mb-0" id="customerEmail"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-6" />
                <div class="card-body pt-0 px-0">
                    <form class="source-item" id="debitNoteForm" method="POST"
                        action="{{ route('billing.debit-notes.store') }}">
                        @csrf
                        <!-- Hidden fields -->
                        <input type="hidden" name="invoice_id" id="invoice_id" value="">
                        <input type="hidden" name="sub_total" id="sub_total" value="0">
                        <input type="hidden" name="document_discount_type" id="document_discount_type" value="1">
                        <input type="hidden" name="document_discount_rate" id="document_discount_rate" value="0">
                        <input type="hidden" name="document_discount_amount" id="document_discount_amount" value="0">
                        <input type="hidden" name="tax_id" id="tax_id" value="0">
                        <input type="hidden" name="tax_amount" id="tax_amount" value="0">
                        <input type="hidden" name="total_amount" id="total_amount" value="0">
                        <input type="hidden" name="payment_status" value="unpaid">
                        <div class="debit-note-form-container">
                            <div class="mb-4" data-repeater-list="items">
                                <!-- ======================================================================= -->
                                <!-- FINAL MODIFIED ITEM ROW (DESCRIPTION TEXTAREA REMOVED) -->
                                <!-- ======================================================================= -->
                                <div class="repeater-wrapper pt-0 pt-md-9" data-repeater-item>
                                    <div class="d-flex border rounded position-relative pe-0">
                                        <div class="row w-100 p-6 g-6">
                                            <!-- Item Select -->
                                            <div class="col-md-4 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Item</p>
                                                <select class="form-select item-details" name="item_id">
                                                    <option value="">-- Select Item --</option>
                                                    @if (count($items) > 0)
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-price="{{ number_format($item->selling_unit_price ?? 0, 2, '.', '') }}">
                                                                {{ $item->name ?? 'Unknown Item' }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option value="" disabled>No items available</option>
                                                    @endif
                                                </select>
                                                <!-- The item-description textarea is now completely removed from here -->
                                            </div>
                                            <!-- Selling Unit Price -->
                                            <div class="col-md-3 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Selling Unit Price</p>
                                                <input type="number" class="form-control selling-unit-price"
                                                    name="unit_price" placeholder="999999999.00" step="0.01"
                                                    min="0">
                                                <!-- Item Level Discount and Tax Display -->
                                                <div class="text-heading mt-2">
                                                    <div class="mb-1">
                                                        <small class="text-muted">Discount:</small>
                                                        <span class="discount me-2">0%</span>
                                                        <small class="text-muted">Amt:</small>
                                                        <span
                                                            class="item-discount-amount text-success fw-medium">$0.00</span>
                                                    </div>
                                                    <div class="mb-1">
                                                        <small class="text-muted">Tax:</small>
                                                        <span class="tax-1 me-2" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Item Tax Rate">0%</span>
                                                        <small class="text-muted">Amt:</small>
                                                        <span class="item-tax-amount text-primary fw-medium">$0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Quantity -->
                                            <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                <p class="h6 repeater-title">Qty</p>
                                                <input type="number" class="form-control quantity" name="quantity"
                                                    placeholder="999" min="1" value="1" step="1">
                                            </div>
                                            <!-- Total Price -->
                                            <div class="col-md-3 col-12 pe-0">
                                                <p class="h6 repeater-title">Price</p>
                                                <input type="number" class="form-control total-price" name="total_price"
                                                    placeholder="99999.00" readonly>
                                            </div>
                                        </div>
                                        <div
                                            class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                            <i class="icon-base bx bx-x icon-lg cursor-pointer" data-repeater-delete></i>
                                            <div class="dropdown">
                                                <i class="icon-base bx bx-cog icon-lg cursor-pointer more-options-dropdown"
                                                    role="button" id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                    data-bs-auto-close="false" aria-expanded="false"></i>
                                                <div class="dropdown-menu dropdown-menu-end w-px-300 p-4"
                                                    aria-labelledby="dropdownMenuButton">
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label for="discountInput" class="form-label">Item Level
                                                                Discount (%)</label>
                                                            <input type="number" class="form-control" id="discountInput"
                                                                min="0" max="100" step="0.01"
                                                                placeholder="Enter discount %" />
                                                            <small class="text-muted">Valid range: 0-100%</small>
                                                        </div>
                                                        <div class="col-12">
                                                            <label for="taxInput1" class="form-label">Item Level
                                                                Tax</label>
                                                            <select name="tax-1-input" id="taxInput1"
                                                                class="form-select tax-select">
                                                                <option value="0%" selected>0%</option>
                                                                @if (count($taxes) > 0)
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}%">
                                                                            {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                        </option>
                                                                    @endforeach
                                                                @else
                                                                    <option value="0%">No tax (0%)</option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown-divider my-4"></div>
                                                    <button type="button"
                                                        class="btn btn-label-primary btn-apply-changes">Apply
                                                        Changes</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-primary" data-repeater-create><i
                                            class="icon-base bx bx-plus icon-xs me-1_5"></i>Add Item</button>
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
                            <div class="debit-note-calculations">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Subtotal:</span>
                                    <span class="fw-medium text-heading" id="subtotal-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Item Level Discount:</span>
                                    <span class="fw-medium text-heading"
                                        id="item-level-discount-total-display">-$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Item Level Tax:</span>
                                    <span class="fw-medium text-heading" id="item-level-tax-total-display">+$0.00</span>
                                </div>
                                <hr class="my-2" />
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100 fw-medium">Net Amount:</span>
                                    <span class="fw-medium text-heading"
                                        id="subtotal-after-item-adjustments-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex align-items-center w-px-100">
                                        <span>Discount:</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <input type="number" id="discount" class="form-control form-control-sm me-2"
                                            value="0" style="width: 80px;" min="0">
                                        <select class="form-select form-select-sm me-2" style="width: 70px;"
                                            id="discount-type">
                                            <option value="%" selected>%</option>
                                            <option value="Amount">$</option>
                                        </select>
                                        <span class="fw-medium text-heading" id="discount-display"
                                            style="width: 60px;">-$0.00</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">Taxable Amount:</span>
                                    <span class="fw-medium text-heading" id="taxable-amount-display">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex align-items-center w-px-100">
                                        <span>Tax:</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <select id="tax" class="form-select form-control-sm me-2"
                                            style="width: 120px;">
                                            <option value="0" data-rate="0">No Tax (0%)</option>
                                            @if (count($taxes) > 0)
                                                @foreach ($taxes as $tax)
                                                    <option value="{{ $tax->id }}"
                                                        data-rate="{{ $tax->percentage }}">
                                                        {{ $tax->name }} ({{ $tax->percentage }}%)</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="fw-medium text-heading" id="tax-amount-display"
                                            style="width: 60px;">+$0.00</span>
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
                    <div class="row">
                        <div class="col-12">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Debit Note Add-->
        <!-- Debit Note Actions -->
        <div class="col-lg-3 col-12 debit-note-actions">
            <div class="card mb-6">
                <div class="card-body">
                    <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas"
                        data-bs-target="#sendInvoiceOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                                class="icon-base bx bx-paper-plane icon-xs me-2"></i>Send Debit Note</span>
                    </button>
                    <button type="button" class="btn btn-label-secondary d-grid w-100 mb-4"
                        onclick="previewDebitNote()">Preview</button>
                    <button type="button" id="saveDebitNoteBtn" class="btn btn-label-secondary d-grid w-100"
                        onclick="saveDebitNote()">Save</button>
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
                    <div class="form-check form-switch me-n2">
                        <input type="checkbox" class="form-check-input" id="payment-terms" checked />
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <label for="client-notes">Customer Notes</label>
                    <div class="form-check form-switch me-n2">
                        <input type="checkbox" class="form-check-input" id="client-notes" checked />
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <label for="payment-stub">Payment Stub</label>
                    <div class="form-check form-switch me-n2">
                        <input type="checkbox" class="form-check-input" id="payment-stub" checked />
                    </div>
                </div>
            </div>
        </div>
        <!-- /Debit Note Actions -->
    </div>
    <!-- Offcanvas -->
    @include('_partials/_offcanvas/offcanvas-send-invoice')
    <!-- /Offcanvas -->
@endsection

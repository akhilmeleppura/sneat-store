@extends('layouts/layoutMaster')
@section('title', 'Edit - Credit Note')
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
            // =======================================================================
            // INITIALIZE DATA AND CALCULATIONS FOR EDIT PAGE
            // =======================================================================
            const issueDatePicker = flatpickr(".credit-note-date", {
                dateFormat: "m/d/Y",
                defaultDate: "{{ $creditNote->issue_date ? $creditNote->issue_date->format('m/d/Y') : '' }}",
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        let dueDate = new Date(selectedDates[0]);
                        dueDate.setDate(dueDate.getDate() + 7);
                        dueDatePicker.setDate(dueDate, true);
                    }
                }
            });
            const dueDatePicker = flatpickr(".due-date", {
                dateFormat: "m/d/Y",
                defaultDate: "{{ $creditNote->due_date ? $creditNote->due_date->format('m/d/Y') : '' }}",
            });
            
            // Initialize select fields and calculations after a delay to ensure everything is loaded.
            setTimeout(() => {
                // Set invoice selection and trigger change event to show details
                if (document.getElementById('invoiceSelect')) {
                    const invoiceSelect = document.getElementById('invoiceSelect');
                    invoiceSelect.value = "{{ $creditNote->invoice_id }}";
                    // Manually trigger the change event to populate invoice details
                    const event = new Event('change', { bubbles: true });
                    invoiceSelect.dispatchEvent(event);
                }
                
                // Set document-level tax
                if (document.getElementById('tax')) {
                    document.getElementById('tax').value = "{{ $creditNote->document_tax_id ?? 0 }}";
                }
                
                // Set document-level discount
                document.getElementById('discount-type').value = "{{ $creditNote->document_discount_type == 1 ? '%' : 'Amount' }}";
                document.getElementById('discount').value = "{{ $creditNote->document_discount_rate > 0 ? $creditNote->document_discount_rate : $creditNote->document_discount_amount }}";
                
                // Initialize each existing item's discount and tax values
                document.querySelectorAll('.repeater-wrapper').forEach(wrapper => {
                    // Get the discount and tax values from the display spans
                    const discountPercent = parseFloat(wrapper.querySelector('.discount').textContent) || 0;
                    
                    // Set the discount input value in the dropdown
                    const discountInput = wrapper.querySelector('.discountInput');
                    if (discountInput) {
                        discountInput.value = discountPercent;
                    }
                    
                    // Set the tax select value in the dropdown
                    const taxSelect = wrapper.querySelector('.taxInput1');
                    if (taxSelect) {
                        // Get the tax ID from the hidden field
                        const taxIdField = wrapper.querySelector('input[name="tax_id"]');
                        if (taxIdField) {
                            taxSelect.value = taxIdField.value;
                        }
                    }
                    
                    // Recalculate the item total to ensure all values are properly displayed
                    calculateItemTotal(wrapper);
                });
                
                calculateSubtotal();
                
                // Setup repeater to handle new items with default values
                setupRepeaterDefaults();
            }, 200);
            
            // =========================================================================================
            // EVENT LISTENER FOR 'APPLY CHANGES' (ITEM-LEVEL DISCOUNT/TAX)
            // =========================================================================================
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-apply-changes')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropdown = e.target.closest('.dropdown-menu');
                    const repeaterWrapper = dropdown.closest('.repeater-wrapper');
                    const discountValue = parseFloat(dropdown.querySelector('.discountInput').value) || 0;
                    const taxSelect = dropdown.querySelector('.taxInput1');
                    const taxId = taxSelect.value;
                    const taxText = taxSelect.options[taxSelect.selectedIndex].text;
                    
                    // Update the display spans
                    repeaterWrapper.querySelector('.discount').textContent = discountValue + '%';
                    repeaterWrapper.querySelector('.tax-1').textContent = taxText;
                    
                    // Update the hidden tax ID field
                    const taxIdField = repeaterWrapper.querySelector('input[name="tax_id"]');
                    if (taxIdField) {
                        taxIdField.value = taxId;
                    }
                    
                    calculateItemTotal(repeaterWrapper);
                    calculateSubtotal();
                    const dropdownToggle = repeaterWrapper.querySelector('[data-bs-toggle="dropdown"]');
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) bsDropdown.hide();
                }
            });
            
            // Function to set up default values for new items added via repeater
            function setupRepeaterDefaults() {
                // Listen for new items being added
                const repeaterList = document.querySelector('[data-repeater-list="items"]');
                if (repeaterList) {
                    // Create a MutationObserver to detect when new items are added
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                                for (let i = 0; i < mutation.addedNodes.length; i++) {
                                    const node = mutation.addedNodes[i];
                                    // Check if the added node is a repeater wrapper
                                    if (node.classList && node.classList.contains('repeater-wrapper')) {
                                        initializeNewItem(node);
                                    }
                                    // Check if the added node contains a repeater wrapper
                                    else if (node.querySelector) {
                                        const wrapper = node.querySelector('.repeater-wrapper');
                                        if (wrapper) {
                                            initializeNewItem(wrapper);
                                        }
                                    }
                                }
                            }
                        });
                    });
                    
                    // Start observing the repeater list for child list changes
                    observer.observe(repeaterList, { childList: true });
                }
            }
            
            // Function to initialize a new item with default values
            function initializeNewItem(wrapper) {
                // Set default discount to 0%
                const discountDisplay = wrapper.querySelector('.discount');
                if (discountDisplay) {
                    discountDisplay.textContent = '0%';
                }
                
                // Set default tax to 0%
                const taxDisplay = wrapper.querySelector('.tax-1');
                if (taxDisplay) {
                    taxDisplay.textContent = '0%';
                }
                
                // Set discount input to 0
                const discountInput = wrapper.querySelector('.discountInput');
                if (discountInput) {
                    discountInput.value = 0;
                }
                
                // Set tax select to 0%
                const taxSelect = wrapper.querySelector('.taxInput1');
                if (taxSelect) {
                    taxSelect.value = '0';
                }
                
                // Add hidden tax_id field if it doesn't exist
                if (!wrapper.querySelector('input[name="tax_id"]')) {
                    const hiddenTaxId = document.createElement('input');
                    hiddenTaxId.type = 'hidden';
                    hiddenTaxId.name = 'tax_id';
                    hiddenTaxId.value = '0';
                    wrapper.querySelector('.row').appendChild(hiddenTaxId);
                }
                
                // Calculate the item total
                calculateItemTotal(wrapper);
            }
        });
        
        // Calculate item total including item-level discount and tax
        function calculateItemTotal(row) {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.selling-unit-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').textContent) || 0;
            
            // Get tax percentage from the selected option in the tax dropdown
            const taxSelect = row.querySelector('.taxInput1');
            let taxPercent = 0;
            if (taxSelect && taxSelect.value && taxSelect.value !== '0') {
                const selectedOption = taxSelect.options[taxSelect.selectedIndex];
                // Extract percentage from the text (e.g., "VAT (10%)" -> 10)
                const match = selectedOption.text.match(/(\d+(?:\.\d+)?)%/);
                if (match) {
                    taxPercent = parseFloat(match[1]);
                }
            }
            
            const baseAmount = qty * unitPrice;
            const discountAmount = baseAmount * (discountPercent / 100);
            const taxableAmount = baseAmount - discountAmount;
            const taxAmount = taxableAmount * (taxPercent / 100);
            const totalAmount = taxableAmount + taxAmount;
            
            row.querySelector('.item-discount-amount').textContent = '$' + discountAmount.toFixed(2);
            row.querySelector('.item-tax-amount').textContent = '$' + taxAmount.toFixed(2);
            row.querySelector('.total-price').value = totalAmount.toFixed(2);
        }
        
        document.addEventListener('change', function(e) {
            // Handle item selection change
            if (e.target.classList.contains('item-details')) {
                const selected = e.target.options[e.target.selectedIndex];
                const wrapper = e.target.closest('.repeater-wrapper');
                if (selected.value) {
                    wrapper.querySelector('.selling-unit-price').value = (parseFloat(selected.getAttribute('data-price')) || 0).toFixed(2);
                } else {
                    wrapper.querySelector('.selling-unit-price').value = '0.00';
                }
                calculateItemTotal(wrapper);
                calculateSubtotal();
            }
            
            // Handle invoice selection change
            if (e.target.id === 'invoiceSelect') {
                const selected = e.target.options[e.target.selectedIndex];
                const invoice_id = document.getElementById('invoice_id');
                
                if (selected.value) {
                    // Update hidden invoice_id field
                    invoice_id.value = selected.value;
                    
                    // Get customer details from data attributes
                    const customer = selected.getAttribute('data-customer') || selected.textContent;
                    const company = selected.getAttribute('data-company') || '';
                    const address = selected.getAttribute('data-address') || '';
                    const city = selected.getAttribute('data-city') || '';
                    const state = selected.getAttribute('data-state') || '';
                    const zip = selected.getAttribute('data-zip') || '';
                    const phone = selected.getAttribute('data-phone') || '';
                    const email = selected.getAttribute('data-email') || '';
                    
                    // Update customer details display
                    document.getElementById('customerCompany').textContent = company || customer;
                    
                    // Format full address
                    let fullAddress = '';
                    if (address) fullAddress += address;
                    if (city || state || zip) {
                        if (address) fullAddress += ', ';
                        fullAddress += [city, state, zip].filter(Boolean).join(', ');
                    }
                    document.getElementById('customerAddress').textContent = fullAddress;
                    document.getElementById('customerPhone').textContent = phone;
                    document.getElementById('customerEmail').textContent = email;
                } else {
                    // Clear customer details
                    invoice_id.value = '';
                    document.getElementById('customerCompany').textContent = 'Select an invoice to view details';
                    document.getElementById('customerAddress').textContent = '';
                    document.getElementById('customerPhone').textContent = '';
                    document.getElementById('customerEmail').textContent = '';
                }
            }
            
            // Recalculate if document-level discount or tax changes
            if (['discount-type', 'tax'].includes(e.target.id)) {
                calculateSubtotal();
            }
        });
        
        document.addEventListener("input", function(e) {
            // Recalculate if quantity, price, or document discount input changes
            if (e.target.classList.contains('quantity') || e.target.classList.contains('selling-unit-price')) {
                calculateItemTotal(e.target.closest('.repeater-wrapper'));
                calculateSubtotal();
            }
            if (e.target.id === "discount") {
                calculateSubtotal();
            }
        });
        
        // Calculate grand totals for the entire credit note
        function calculateSubtotal() {
            let subtotal = 0, itemLevelDiscountTotal = 0, itemLevelTaxTotal = 0;
            document.querySelectorAll('.repeater-wrapper').forEach(row => {
                subtotal += (parseFloat(row.querySelector('.selling-unit-price')?.value) || 0) * (parseFloat(row.querySelector('.quantity')?.value) || 0);
                itemLevelDiscountTotal += parseFloat(row.querySelector('.item-discount-amount')?.textContent.replace('$', '') || 0);
                itemLevelTaxTotal += parseFloat(row.querySelector('.item-tax-amount')?.textContent.replace('$', '') || 0);
            });
            
            document.getElementById("subtotal-display").textContent = '$' + subtotal.toFixed(2);
            document.getElementById("item-level-discount-total-display").textContent = '-$' + itemLevelDiscountTotal.toFixed(2);
            document.getElementById("item-level-tax-total-display").textContent = '+$' + itemLevelTaxTotal.toFixed(2);
            
            const subtotalAfterItemAdjustments = subtotal - itemLevelDiscountTotal + itemLevelTaxTotal;
            document.getElementById("subtotal-after-item-adjustments-display").textContent = '$' + subtotalAfterItemAdjustments.toFixed(2);
            
            const discountValue = parseFloat(document.getElementById("discount")?.value || 0);
            const discountType = document.getElementById("discount-type")?.value || "%";
            const discountAmount = (discountType === "%") ? (subtotalAfterItemAdjustments * discountValue) / 100 : discountValue;
            document.getElementById("discount-display").textContent = '-$' + discountAmount.toFixed(2);
            const taxableAmount = Math.max(0, subtotalAfterItemAdjustments - discountAmount);
            document.getElementById("taxable-amount-display").textContent = '$' + taxableAmount.toFixed(2);
            const taxSelect = document.getElementById("tax");
            const taxId = taxSelect.value;
            const taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex]?.getAttribute('data-rate') || 0);
            const taxAmount = taxableAmount * (taxRate / 100);
            document.getElementById("tax-amount-display").textContent = '+$' + taxAmount.toFixed(2);
            
            const grandTotal = taxableAmount + taxAmount;
            document.getElementById("grand-total").textContent = '$' + grandTotal.toFixed(2);
            
            // Update hidden fields
            document.getElementById("sub_total").value = subtotal.toFixed(2);
            document.getElementById("document_discount_amount").value = discountAmount.toFixed(2);
            document.getElementById("tax_amount").value = taxAmount.toFixed(2);
            document.getElementById("total_amount").value = grandTotal.toFixed(2);
            document.getElementById("tax_id").value = taxId;
            document.getElementById("document_discount_type").value = discountType === "%" ? 1 : 2;
            document.getElementById("document_discount_rate").value = discountValue;
        }
        
        // Function to handle form submission for updating the credit note
        function updateCreditNote() {
            if (!document.getElementById('invoiceSelect').value) {
                return Swal.fire({ 
                    icon: 'warning', 
                    title: 'Validation Error', 
                    text: 'Please select an invoice.',
                    confirmButtonColor: '#3085d6',
                });
            }
            
            if (!Array.from(document.querySelectorAll('.item-details')).some(item => item.value)) {
                 return Swal.fire({ 
                    icon: 'warning', 
                    title: 'Validation Error', 
                    text: 'Please add at least one item.',
                    confirmButtonColor: '#3085d6',
                });
            }
            
            const saveButton = document.getElementById('updateCreditNoteBtn');
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
            
            let formData = new FormData(document.getElementById('creditNoteForm'));
            formData.append('_method', 'PUT'); // Specify PUT method for update
            
            let issueDate = document.querySelector('.credit-note-date').value;
            if(issueDate) formData.set('issue_date', issueDate.split('/').reverse().join('-'));
            let dueDate = document.querySelector('.due-date').value;
            if(dueDate) formData.set('due_date', dueDate.split('/').reverse().join('-'));
            
            formData.set('credit_note_number', document.getElementById('creditNoteId').value);
            
            // Clear previous items to avoid confusion on the backend
            formData.forEach((value, key) => { 
                if (key.startsWith('items[')) formData.delete(key); 
                if (key.startsWith('existing_items[')) formData.delete(key);
            });
            
            // Collect all items (both existing and new) in a unified format
            document.querySelectorAll('.repeater-wrapper').forEach((row, index) => {
                const itemId = row.querySelector('.item-details')?.value;
                if (itemId) {
                    const itemDbId = row.querySelector('input[name="id"]')?.value; // For existing items
                    if (itemDbId) {
                        formData.append(`existing_items[${index}][id]`, itemDbId);
                        formData.append(`existing_items[${index}][item_id]`, itemId);
                        formData.append(`existing_items[${index}][quantity]`, row.querySelector('.quantity')?.value || 0);
                        formData.append(`existing_items[${index}][unit_price]`, row.querySelector('.selling-unit-price')?.value || 0);
                        formData.append(`existing_items[${index}][total_price]`, row.querySelector('.total-price')?.value || 0);
                        formData.append(`existing_items[${index}][discount_percent]`, parseFloat(row.querySelector('.discount').textContent) || 0);
                        
                        // Get tax ID and percentage
                        const taxSelect = row.querySelector('.taxInput1');
                        const taxId = taxSelect.value;
                        formData.append(`existing_items[${index}][tax_id]`, taxId);
                    } else {
                        // New item
                        formData.append(`items[${index}][item_id]`, itemId);
                        formData.append(`items[${index}][quantity]`, row.querySelector('.quantity')?.value || 0);
                        formData.append(`items[${index}][unit_price]`, row.querySelector('.selling-unit-price')?.value || 0);
                        formData.append(`items[${index}][total_price]`, row.querySelector('.total-price')?.value || 0);
                        formData.append(`items[${index}][discount_percent]`, parseFloat(row.querySelector('.discount').textContent) || 0);
                        
                        // Get tax ID and percentage
                        const taxSelect = row.querySelector('.taxInput1');
                        const taxId = taxSelect.value;
                        formData.append(`items[${index}][tax_id]`, taxId);
                    }
                }
            });
            
            fetch("{{ route('billing.credit-notes.update', $creditNote->id) }}", {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'Accept': 'application/json' 
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Server error');
                    });
                }
                return response.json();
            })
            .then(data => {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Success!', 
                    text: data.message || "Credit note updated successfully.",
                    confirmButtonColor: '#3085d6',
                })
                .then(() => {
                    window.location.href = "{{ route('billing.credit-notes.index') }}";
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error!', 
                    text: error.message || 'An error occurred while updating the credit note.',
                    confirmButtonColor: '#3085d6',
                });
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = 'Update';
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
        <!-- Credit Note Edit-->
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
                                    <span class="h5 text-capitalize mb-0 text-nowrap">Credit Note</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control" disabled value="{{ $creditNoteNumber }}"
                                        id="creditNoteId" />
                                </dd>
                                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                                    <span class="fw-normal">Date Issued:</span>
                                </dt>
                                <dd class="col-sm-7">
                                    <input type="text" class="form-control credit-note-date" placeholder="MM/DD/YYYY" />
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
                            <h6>Associated Invoice:</h6>
                            <select class="form-select mb-4 w-75" id="invoiceSelect">
                                <option value="">-- Select Invoice --</option>
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
                                        {{ $invoice->document_prefix }}-{{ $invoice->document_number }} - {{ $invoice->customer->name ?? 'Unknown Customer' }}
                                    </option>
                                @endforeach
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
                    <form class="source-item" id="creditNoteForm">
                        @csrf
                        <input type="hidden" name="invoice_id" id="invoice_id">
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
                                                <input type="hidden" name="id" value="{{ $creditNoteItem->id }}" />
                                                <!-- Hidden field for tax_id -->
                                                <input type="hidden" name="tax_id" value="{{ $creditNoteItem->tax_id ?? 0 }}" />
                                                <div class="col-md-5 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Item</p>
                                                    <select class="form-select item-details" name="item_id">
                                                        <option value="">-- Select --</option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-price="{{ number_format($item->selling_unit_price ?? 0, 2, '.', '') }}"
                                                                {{ $creditNoteItem->item_id == $item->id ? 'selected' : '' }}>
                                                                {{ $item->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Selling Unit Price</p>
                                                    <input type="number"
                                                        class="form-control selling-unit-price" name="unit_price"
                                                        placeholder="999" value="{{ number_format($creditNoteItem->selling_unit_price, 2, '.', '') }}" step="0.01"
                                                        min="0">
                                                    <div class="text-heading mt-2">
                                                        <div class="mb-1"><small class="text-muted">Discount:</small>
                                                            <span class="discount me-2">{{ $creditNoteItem->discount_rate ?? 0 }}%</span>
                                                            <small class="text-muted">Amt:</small> <span
                                                                class="item-discount-amount text-success fw-medium">$0.00</span>
                                                        </div>
                                                        <div class="mb-1"><small class="text-muted">Tax:</small>
                                                            <span class="tax-1 me-2">
                                                                @if($creditNoteItem->tax_id)
                                                                    {{ optional($creditNoteItem->tax)->name }} ({{ optional($creditNoteItem->tax)->percentage }}%)
                                                                @else
                                                                    0%
                                                                @endif
                                                            </span>
                                                            <small class="text-muted">Amt:</small> <span
                                                                class="item-tax-amount text-primary fw-medium">$0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-12 mb-md-0 mb-4">
                                                    <p class="h6 repeater-title">Qty</p>
                                                    <input type="number" class="form-control quantity" name="quantity"
                                                        placeholder="1" value="{{ $creditNoteItem->quantity }}"
                                                        min="1">
                                                </div>
                                                <div class="col-md-3 col-12 pe-0">
                                                    <p class="h6 repeater-title">Price</p>
                                                    <input type="number" class="form-control total-price"
                                                        name="total_price" placeholder="0.00" readonly>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                                <i class="icon-base bx bx-x icon-lg cursor-pointer" data-repeater-delete></i>
                                                <div class="dropdown">
                                                    <i class="icon-base bx bx-cog icon-lg cursor-pointer" role="button" data-bs-toggle="dropdown" data-bs-auto-close="false"></i>
                                                    <div class="dropdown-menu dropdown-menu-end w-px-300 p-4">
                                                        <div class="row g-3">
                                                            <div class="col-12"><label class="form-label">Discount
                                                                    (%)</label><input type="number"
                                                                    class="form-control discountInput"
                                                                    value="{{ $creditNoteItem->discount_rate ?? 0 }}"
                                                                    min="0" max="100" /></div>
                                                            <div class="col-12"><label
                                                                    class="form-label">Tax</label><select
                                                                    class="form-select taxInput1">
                                                                    <option value="0" {{ is_null($creditNoteItem->tax_id) || $creditNoteItem->tax_id == 0 ? 'selected' : '' }}>No Tax (0%)</option>
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}" {{ $creditNoteItem->tax_id == $tax->id ? 'selected' : '' }}>
                                                                            {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                        </option>
                                                                    @endforeach
                                                                </select></div>
                                                        </div>
                                                        <div class="dropdown-divider my-4"></div><button type="button"
                                                            class="btn btn-label-primary btn-apply-changes">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <!-- This empty block will be used by repeater JS to add new items -->
                                @endforelse
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
                        <div class="col-md-6 mb-md-0 mb-4"></div>
                        <div class="col-md-6 d-flex justify-content-end">
                            <div class="invoice-calculations">
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
                                            <option value="0" data-rate="0">No Tax (0%)</option>
                                            @foreach ($taxes as $tax)
                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}"
                                                    {{ $creditNote->document_tax_id == $tax->id ? 'selected' : '' }}>
                                                    {{ $tax->name }} ({{ $tax->percentage }}%)</option>
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
            </div>
        </div>
        <!-- /Credit Note Edit-->
        <!-- Credit Note Actions -->
        <div class="col-lg-3 col-12 invoice-actions">
            <div class="card mb-6">
                <div class="card-body">
                    <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#sendInvoiceOffcanvas">
                        <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="icon-base bx bx-paper-plane icon-xs me-2"></i>Send Credit Note</span>
                    </button>
                    <button type="button" class="btn btn-label-secondary d-grid w-100 mb-4" onclick="previewCreditNote()">Preview</button>
                    <button type="button" id="updateCreditNoteBtn" class="btn btn-label-secondary d-grid w-100" onclick="updateCreditNote()">Update</button>
                </div>
            </div>
        </div>
    </div>
    @include('_partials/_offcanvas/offcanvas-send-invoice')
@endsection
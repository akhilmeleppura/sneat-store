@extends('layouts/layoutMaster')

@section('title', 'Journal Preview')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@push('styles')
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .printable-section, .printable-section * {
        visibility: visible;
    }
    .printable-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="row invoice-preview">
    <!-- Journal Details -->
    <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6 printable-section">
        <div class="card invoice-preview-card p-sm-12 p-6">
            <!-- Header -->
            <div class="card-body invoice-preview-header rounded">
                <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
                    <div class="mb-xl-0 mb-6 text-heading">
                        <h5 class="mb-2">Demo Company امتحان</h5>
                        <p class="mb-1">company@email.com</p>
                        <p class="mb-0">+91 1234567891</p>
                    </div>
                    <div>
                        <h5 class="mb-6">Journal #{{ $journal->journal_number }}</h5>
                        <div class="mb-1 text-heading">
                            <span>Recorded On:</span>
                            <span class="fw-medium">{{ $journal->created_at->format('d-m-Y') }}</span>
                        </div>
                        <div class="text-heading">
                            <span>Transaction Date:</span>
                            <span class="fw-medium">{{ \Carbon\Carbon::parse($journal->transaction_date)->format('d-m-Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal Entries Table -->
            <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                <table class="table m-0">
                    <thead class="table-light">
                        <tr>
                            <th>Debit Account</th>
                            <th>Credit Account</th>
                            <th class="text-end">Debit Amount</th>
                            <th class="text-end">Credit Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($journal->entries as $entry)
                            <tr>
                                <td>{{ $entry->debit_account_name }}</td>
                                <td>{{ $entry->credit_account_name }}</td>
                                <td class="text-end">{{ $entry->debit_amount > 0 ? number_format($entry->debit_amount, 2) : '' }}</td>
                                <td class="text-end">{{ $entry->credit_amount > 0 ? number_format($entry->credit_amount, 2) : '' }}</td>
                                <td>{{ $entry->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Note -->
            <div class="mt-4">
                <span class="fw-medium text-heading">Note:</span>
                <span>Thank you for reviewing this journal entry. Please verify the details before proceeding.</span>
            </div>
        </div>
    </div>

    <!-- Journal Actions Sidebar -->
    <div class="col-xl-3 col-md-4 col-12 invoice-actions">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('accounting.journal.edit', $journal->id) }}" class="btn btn-primary d-grid w-100 mb-4">
                    Edit Journal
                </a>
                <button class="btn btn-label-secondary d-grid w-100 mb-4">
                    Download
                </button>
                <button class="btn btn-label-secondary d-grid w-100 mb-4" onclick="window.print();">
                    Print
                </button>
                <button class="btn btn-danger d-grid w-100">
                    Delete Journal
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

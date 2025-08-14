@extends('accounting::components.layouts.master')

@section('title', 'Trial Balance Report')

@section('page-style')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Modern Styling */
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f0f2f5;
        color: #333;
    }

    .app-container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        padding: 25px 30px;
    }

    /* Filter Bar - New Style */
    .filter-container {
        background: white;
        border-radius: 8px;
        padding: 10px 20px;
        margin-bottom: 20px;
        border: 1px solid #eee;
        display: flex;
        align-items: center;
    }

    .filter-container .input-group {
        display: flex;
        align-items: center;
        flex-grow: 1;
    }

    .filter-container .input-group-text {
        background: transparent;
        border: none;
        padding: 0;
        font-weight: 500;
        color: #555;
        margin-right: 8px;
    }

    .filter-container .form-control {
        border: 1px solid #ddd;
        padding: 8px 12px;
        border-radius: 4px;
        flex-grow: 1;
    }

    .filter-container .btn {
        padding: 8px 15px;
        border-radius: 4px;
        margin-left: 10px;
    }

    .filter-container .btn-success {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }

    .filter-container .btn-outline-secondary {
        border: 1px solid #ddd;
        color: #555;
    }

    /* Title Section */
    .title-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
    }

    .title-section h4 {
        font-size: 26px;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        margin: 0;
    }

    .export-btn {
        background: #007bff;
        color: white;
        padding: 10px 18px;
        border-radius: 5px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    /* Info Bar */
    .info-bar {
        background: #f7f9fc;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 25px;
        display: flex;
        flex-wrap: wrap;
    }

    .info-item {
        flex: 1;
        min-width: 200px;
    }

    .info-item strong {
        color: #6c757d;
        font-weight: normal;
        font-size: 12px;
        display: block;
    }

    /* Table Styling */
    .data-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .table-responsive {
        border-radius: 8px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table thead {
        background: #21355c;
        color: white;
    }

    .table thead th {
        padding: 14px 15px;
        font-weight: 500;
        text-align: left;
        color: white !important;
    }

    .table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .table tbody tr:nth-child(even) {
        background: #fbfdff;
    }

    .account-link {
        color: #002F6C;
        text-decoration: underline;
    }

    .numeric-cell {
        text-align: right;
    }

    .table tfoot {
        background: #21355c;
        color: white;
        font-weight: bold;
    }

    .table tfoot td {
        padding: 14px 15px;
        color: white !important;
    }

    /* PDF Specific Styles */
    @media print {
        body {
            background: white;
            color: black;
        }
        .table thead th,
        .table tfoot td {
            color: white !important;
            background-color: #21355c !important;
        }
        .account-link {
            color: #002F6C !important;
        }
    }
</style>
@endsection

@section('content')
<div class="app-container">
    <!-- Filter Section -->
    <form method="GET" action="{{ route('accounting.trial-balance.index') }}">
        <div class="filter-container">
            <div class="input-group">
                <span class="input-group-text">Duration</span>
                <input type="text" name="date_range" class="form-control flatpickr-range" 
                       value="{{ request('date_range') }}" placeholder="Start Date to End Date">
                <button type="submit" class="btn btn-success">OK</button>
                <a href="{{ route('accounting.trial-balance.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
            <button type="submit" formaction="{{ route('accounting.trial-balance.export-pdf') }}" 
                    class="export-btn">
                Export as PDF
            </button>
        </div>
    </form>

    <!-- Title Section -->
    <div class="title-section">
        <h4>TRIAL BALANCE</h4>
    </div>

    <!-- Info Bar -->
    <div class="info-bar">
        <div class="info-item">
            <strong>Data Period:</strong>
            <span>{{ request('date_range') ?? 'All Time' }}</span>
        </div>
        <div class="info-item">
            <strong>Generated By:</strong>
            <span>Admin</span>
        </div>
        <div class="info-item">
            <strong>Generated On:</strong>
            <span>{{ now()->format('D d, Y') }}</span>
        </div>
    </div>

    <!-- Data Table -->
    <div class="data-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th rowspan="2" style="color: white !important;">Account</th>
                        <th colspan="2" style="color: white !important;">Opening</th>
                        <th colspan="2" style="color: white !important;">Transaction</th>
                        <th colspan="2" style="color: white !important;">Closing</th>
                    </tr>
                    <tr>
                        <th style="color: white !important;">Debit (SAR)</th>
                        <th style="color: white !important;">Credit (SAR)</th>
                        <th style="color: white !important;">Debit (SAR)</th>
                        <th style="color: white !important;">Credit (SAR)</th>
                        <th style="color: white !important;">Debit (SAR)</th>
                        <th style="color: white !important;">Credit (SAR)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalOpeningDebit = $totalOpeningCredit = 0;
                        $totalTransactionDebit = $totalTransactionCredit = 0;
                        $totalClosingDebit = $totalClosingCredit = 0;

                        $filteredData = $trialBalanceData->filter(function($row){
                            return $row['transaction_debit'] > 0 || $row['transaction_credit'] > 0;
                        });
                    @endphp

                    @if($filteredData->count() > 0)
                        @foreach($filteredData as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.ledger.view', ['id' => $row['account_id']]) }}{{ request('date_range') ? '?date_range=' . urlencode(request('date_range')) : '' }}" 
                                       target="_blank" class="account-link">
                                        {{ $row['account_name'] }}
                                    </a>
                                </td>
                                <td class="numeric-cell">{{ number_format($row['opening_debit'], 2) }}</td>
                                <td class="numeric-cell">{{ number_format($row['opening_credit'], 2) }}</td>
                                <td class="numeric-cell">{{ number_format($row['transaction_debit'], 2) }}</td>
                                <td class="numeric-cell">{{ number_format($row['transaction_credit'], 2) }}</td>
                                <td class="numeric-cell">{{ number_format($row['closing_debit'], 2) }}</td>
                                <td class="numeric-cell">{{ number_format($row['closing_credit'], 2) }}</td>
                            </tr>
                            @php
                                $totalOpeningDebit += $row['opening_debit'];
                                $totalOpeningCredit += $row['opening_credit'];
                                $totalTransactionDebit += $row['transaction_debit'];
                                $totalTransactionCredit += $row['transaction_credit'];
                                $totalClosingDebit += $row['closing_debit'];
                                $totalClosingCredit += $row['closing_credit'];
                            @endphp
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center">No data available for the selected date range.</td>
                        </tr>
                    @endif
                </tbody>
                @if($filteredData->count() > 0)
                <tfoot>
                    <tr>
                        <td style="color: white !important;">Total</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalOpeningDebit, 2) }}</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalOpeningCredit, 2) }}</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalTransactionDebit, 2) }}</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalTransactionCredit, 2) }}</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalClosingDebit, 2) }}</td>
                        <td class="numeric-cell" style="color: white !important;">{{ number_format($totalClosingCredit, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    flatpickr(".flatpickr-range", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: {
            rangeSeparator: " to "
        }
    });
    
    // Ensure PDF export maintains white text in headers/footers
    document.querySelector('button[formaction*="export-pdf"]').addEventListener('click', function() {
        // Add any necessary PDF export preparation here
    });
});
</script>
@endsection
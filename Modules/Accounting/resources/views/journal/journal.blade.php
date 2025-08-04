@extends('layouts.layoutMaster')
@section('title', 'Journal Entries')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/animate-css/animate.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <table class="datatables-users table" id="journalEntriesTable">
            <thead>
                <tr></tr>  <!-- Headers Rendered Dynamically -->
            </thead>
        </table>
    </div>
</div>
@endsection

@section('page-script')
@vite(['resources/js/HS/data-table.js'])
<script>
    window.dataTableConfig = {
        ajaxUrl: "{{ route('accounting.journal.entriesList') }}", // Correct route name
        actionsRoutePrefix: '/accounting/journal',
        addButton: {
            label: 'Add Journal',
            url: "{{ route('accounting.journal.create') }}"
        },
        permissions: {
            canView: @json(auth()->user()->is_supreme_admin == 1 || auth()->user()->can('accounting.journal.view')),
            canEdit: @json(auth()->user()->is_supreme_admin == 1 || auth()->user()->can('accounting.journal.edit')),
            canDelete: @json(auth()->user()->is_supreme_admin == 1 || auth()->user()->can('accounting.journal.delete')),
            canAdd: @json(auth()->user()->is_supreme_admin == 1 || auth()->user()->can('accounting.journal.create'))
        },
        columns: {
            id: { type: 'text' },
            created_at: { type: 'text' },
            transaction_date: { type: 'text' },
            journal_number: { type: 'text' },
            created_by: { type: 'text' },
            entries_count: { type: 'text' },
            summary: { type: 'text' }
        }
    };
</script>
@endsection

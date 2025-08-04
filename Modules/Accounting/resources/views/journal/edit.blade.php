@extends('layouts.layoutMaster')
@section('title', 'Edit Journal Entry')

@section('content')
<div class="col-12">
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <h5>Edit Journal Entry</h5>
      <div>
        <strong>Total Debit:</strong>
        <input type="text" id="total_debit" class="form-control d-inline w-auto" value="0.00" readonly disabled>
        <strong>Total Credit:</strong>
        <input type="text" id="total_credit" class="form-control d-inline w-auto" value="0.00" readonly disabled>
      </div>
    </div>

    <div class="card-body">
      <form action="{{ route('accounting.journal.update', $journal->id) }}" method="POST" id="journal-form">
        @csrf @method('PUT')

        <div class="mb-3 w-25">
          <label>Transaction Date</label>
          <input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ $journal->transaction_date }}" required>
        </div>

        <!-- Subheadings Row -->
        <div class="row fw-bold text-center mb-2">
          <div class="col-md-3">Account Name</div>
          <div class="col-md-2">Debit</div>
          <div class="col-md-2">Credit</div>
          <div class="col-md-3">Description</div>
          <div class="col-md-1">Action</div>
        </div>

        <div id="journal-entries">
          @foreach($journal->entries as $index => $entry)
            <div class="row journal-entry align-items-center mb-2">
              <div class="col-md-3">
                <select name="entries[{{ $index }}][ledger_account_id]" class="form-control form-control-sm" required>
                  <option value="">Select Account</option>
                  @foreach($chartOfAccounts as $account)
<option value="{{ $account->id }}" {{ (string)$account->id === (string)old('entries.'.$index.'.ledger_account_id', $entry->chart_of_account_id) ? 'selected' : '' }}>
    {{ $account->account_name }} [{{ $account->account_type }}]
</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <input type="number" name="entries[{{ $index }}][debit_amount]" class="form-control form-control-sm debit-amount" value="{{ $entry->debit_amount }}" placeholder="0.00">
              </div>
              <div class="col-md-2">
                <input type="number" name="entries[{{ $index }}][credit_amount]" class="form-control form-control-sm credit-amount" value="{{ $entry->credit_amount }}" placeholder="0.00">
              </div>
              <div class="col-md-3">
                <input type="text" name="entries[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $entry->description }}" placeholder="Description">
              </div>
              <div class="col-md-1 text-center">
                @if($index > 0)
                  <button type="button" class="btn btn-sm btn-danger remove-entry">X</button>
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <button type="button" id="add-entry" class="btn btn-success btn-sm">Add More</button>

        <hr>

        <!-- Summary Row -->
        <div class="row align-items-center">
          <div class="col-md-6">
            <label for="summary">Summary</label>
            <input type="text" name="summary" id="summary" class="form-control" value="{{ $journal->summary ?? '' }}" placeholder="General remarks...">
          </div>
          <div class="col-md-3">
            <label for="total">Total Count</label>
            <input type="text" id="total" class="form-control" value="0.00" readonly disabled>
          </div>
        </div>

        <div class="mt-3">
          <button type="submit" class="btn btn-primary">Update</button>
          <a href="{{ route('accounting.journal.index') }}" class="btn btn-secondary">Back</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Entry Template -->
<div id="entry-template" class="d-none">
  <div class="row journal-entry align-items-center mb-2">
    <div class="col-md-3">
      <select name="__name__" class="form-control form-control-sm" required>
        <option value="">Select Account</option>
        @foreach($chartOfAccounts as $account)
          <option value="{{ $account->id }}">{{ $account->account_name }} [{{ $account->account_type }}]</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" name="__debit_name__" class="form-control form-control-sm debit-amount" placeholder="0.00">
    </div>
    <div class="col-md-2">
      <input type="number" name="__credit_name__" class="form-control form-control-sm credit-amount" placeholder="0.00">
    </div>
    <div class="col-md-3">
      <input type="text" name="__desc_name__" class="form-control form-control-sm" placeholder="Description">
    </div>
    <div class="col-md-1 text-center">
      <button type="button" class="btn btn-sm btn-danger remove-entry">X</button>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<!-- Include SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function calculateTotals() {
        let debitTotal = 0;
        let creditTotal = 0;

        document.querySelectorAll('.debit-amount').forEach(input => {
            debitTotal += parseFloat(input.value) || 0;
        });
        document.querySelectorAll('.credit-amount').forEach(input => {
            creditTotal += parseFloat(input.value) || 0;
        });

        document.getElementById('total_debit').value = debitTotal.toFixed(2);
        document.getElementById('total_credit').value = creditTotal.toFixed(2);
        document.getElementById('total').value = (debitTotal + creditTotal).toFixed(2);
    }

    function initializeDisableFields() {
        document.querySelectorAll('.journal-entry').forEach(row => {
            const debitInput = row.querySelector('.debit-amount');
            const creditInput = row.querySelector('.credit-amount');

            if (parseFloat(debitInput.value) > 0) {
                creditInput.disabled = true;
            } else if (parseFloat(creditInput.value) > 0) {
                debitInput.disabled = true;
            }
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('debit-amount') || e.target.classList.contains('credit-amount')) {
            const row = e.target.closest('.journal-entry');
            const debitInput = row.querySelector('.debit-amount');
            const creditInput = row.querySelector('.credit-amount');

            if (e.target.classList.contains('debit-amount')) {
                creditInput.disabled = parseFloat(debitInput.value) > 0;
                if (!creditInput.disabled) creditInput.value = '';
            } else {
                debitInput.disabled = parseFloat(creditInput.value) > 0;
                if (!debitInput.disabled) debitInput.value = '';
            }

            calculateTotals();
        }
    });

    document.getElementById('add-entry').addEventListener('click', function () {
        const index = document.querySelectorAll('.journal-entry').length;
        const template = document.getElementById('entry-template').innerHTML
            .replace(/__name__/g, `entries[${index}][ledger_account_id]`)
            .replace(/__debit_name__/g, `entries[${index}][debit_amount]`)
            .replace(/__credit_name__/g, `entries[${index}][credit_amount]`)
            .replace(/__desc_name__/g, `entries[${index}][description]`);

        document.getElementById('journal-entries').insertAdjacentHTML('beforeend', template);
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-entry')) {
            const entries = document.querySelectorAll('.journal-entry');
            if (entries.length > 1) {
                e.target.closest('.journal-entry').remove();
                calculateTotals();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cannot Delete!',
                    text: 'At least one entry must be present.',
                    confirmButtonText: 'Okay'
                });
            }
        }
    });

    

    initializeDisableFields();
    calculateTotals();
});
</script>
@endsection

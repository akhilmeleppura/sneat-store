@extends('layouts.layoutMaster')
@section('title', isset($journalEntry) ? 'Edit Journal Entry' : 'Create Journal Entry')

@section('content')
<div class="col-12">
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <h5>{{ isset($journalEntry) ? 'Edit' : 'Create' }} Journal Entry</h5>
      <div>
        <strong>Total Debit:</strong>
        <input type="text" id="total_debit" class="form-control d-inline w-auto" value="0.00" readonly disabled>
        <strong>Total Credit:</strong>
        <input type="text" id="total_credit" class="form-control d-inline w-auto" value="0.00" readonly disabled>
      </div>
    </div>

    <div class="card-body">
      <form action="{{ isset($journalEntry) ? route('accounting.journal.update', $journalEntry->id) : route('accounting.journal.store') }}" method="POST" id="journal-form">
        @csrf
        @if(isset($journalEntry))
          @method('PUT')
        @endif

        <div class="mb-3 w-25">
          <label>Transaction Date</label>
          <input type="date" name="transaction_date" class="form-control form-control-sm" required
            value="{{ old('transaction_date', $today ?? ($journalEntry->transaction_date ?? now()->format('Y-m-d'))) }}">
        </div>

        <!-- Subheadings -->
        <div class="row fw-bold text-center mb-2">
          <div class="col-md-3">Account Name</div>
          <div class="col-md-2">Debit</div>
          <div class="col-md-2">Credit</div>
          <div class="col-md-3">Description</div>
        </div>

        <div id="journal-entries">
          @php
            $entries = old('entries', $journalEntry->entries ?? [['ledger_account_id' => '', 'debit_amount' => '', 'credit_amount' => '', 'description' => '']]);
          @endphp

          @foreach($entries as $index => $entry)
          <div class="row journal-entry align-items-center mb-2">
            <div class="col-md-3">
              <select name="entries[{{ $index }}][ledger_account_id]" class="form-control form-control-sm" required>
                <option value="">Select Account</option>
                @foreach ($chartOfAccounts as $account)
                  <option value="{{ $account->id }}"
                    @if((string) old("entries.$index.ledger_account_id", $entry['ledger_account_id'] ?? $entry->chart_of_account_id ?? '') === (string) $account->id)
                      selected
                    @endif>
                    {{ $account->account_name }} [{{ $account->account_type }}]
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <input type="number" step="0.01" name="entries[{{ $index }}][debit_amount]" class="form-control form-control-sm debit-amount" placeholder="0.00" value="{{ old("entries.$index.debit_amount", $entry['debit_amount'] ?? $entry->debit_amount ?? '') }}">
            </div>
            <div class="col-md-2">
              <input type="number" step="0.01" name="entries[{{ $index }}][credit_amount]" class="form-control form-control-sm credit-amount" placeholder="0.00" value="{{ old("entries.$index.credit_amount", $entry['credit_amount'] ?? $entry->credit_amount ?? '') }}">
            </div>
            <div class="col-md-3">
              <input type="text" name="entries[{{ $index }}][description]" class="form-control form-control-sm" placeholder="Description" value="{{ old("entries.$index.description", $entry['description'] ?? $entry->description ?? '') }}">
            </div>
            <div class="col-md-1 text-center">
              @if($index > 0)
              <button type="button" class="btn btn-sm btn-danger remove-entry">X</button>
              @endif
            </div>
          </div>
          @endforeach
        </div>

        <button type="button" id="add-entry" class="btn btn-success btn-sm mt-2">Add More</button>

        <hr>

        <!-- Summary -->
        <div class="row align-items-center mt-3">
          <div class="col-md-6">
            <label for="summary">Summary</label>
            <input type="text" name="summary" id="summary" class="form-control" placeholder="General remarks..." value="{{ old('summary', $journalEntry->summary ?? '') }}">
          </div>
          <div class="col-md-3">
            <label for="total-count">Total Count</label>
            <input type="text" id="total-count" class="form-control" value="{{ count($entries) }}" readonly disabled>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">{{ isset($journalEntry) ? 'Update' : 'Save' }}</button>
          <a href="{{ route('accounting.journal.index') }}" class="btn btn-secondary">Back</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Hidden Template -->
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
      <input type="number" step="0.01" name="__debit_name__" class="form-control form-control-sm debit-amount" placeholder="0.00">
    </div>
    <div class="col-md-2">
      <input type="number" step="0.01" name="__credit_name__" class="form-control form-control-sm credit-amount" placeholder="0.00">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  function calculateTotals() {
    let debitTotal = 0, creditTotal = 0;
    document.querySelectorAll('.debit-amount').forEach(input => {
      debitTotal += parseFloat(input.value) || 0;
    });
    document.querySelectorAll('.credit-amount').forEach(input => {
      creditTotal += parseFloat(input.value) || 0;
    });

    document.getElementById('total_debit').value = debitTotal.toFixed(2);
    document.getElementById('total_credit').value = creditTotal.toFixed(2);
    document.getElementById('total-count').value = document.querySelectorAll('.journal-entry').length;
  }

  // 🔐 Lock opposite input on initial load (for Edit)
  document.querySelectorAll('.journal-entry').forEach(row => {
    const debitInput = row.querySelector('.debit-amount');
    const creditInput = row.querySelector('.credit-amount');

    if (parseFloat(debitInput.value) > 0) {
      creditInput.disabled = true;
    } else if (parseFloat(creditInput.value) > 0) {
      debitInput.disabled = true;
    }
  });

  document.addEventListener('input', function (e) {
    if (e.target.classList.contains('debit-amount') || e.target.classList.contains('credit-amount')) {
      const row = e.target.closest('.journal-entry');
      const debitInput = row.querySelector('.debit-amount');
      const creditInput = row.querySelector('.credit-amount');

      if (e.target.classList.contains('debit-amount')) {
        if (parseFloat(debitInput.value) > 0) {
          creditInput.disabled = true;
          creditInput.value = '';
        } else {
          creditInput.disabled = false;
        }
      }

      if (e.target.classList.contains('credit-amount')) {
        if (parseFloat(creditInput.value) > 0) {
          debitInput.disabled = true;
          debitInput.value = '';
        } else {
          debitInput.disabled = false;
        }
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
    calculateTotals();
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

  document.getElementById('journal-form').addEventListener('submit', function (e) {
    const debit = parseFloat(document.getElementById('total_debit').value) || 0;
    const credit = parseFloat(document.getElementById('total_credit').value) || 0;

    if (debit !== credit) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Mismatch Detected!',
        text: 'Total Debit and Credit amounts must be equal to proceed.',
        confirmButtonText: 'Okay'
      });
    }
  });

  calculateTotals();
});
</script>
@endsection


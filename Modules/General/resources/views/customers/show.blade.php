@extends('layouts/layoutMaster')

@section('title', 'Customer Details - Pages')

@section('content')
  <div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
      <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Customers /</span> Customer Details</h4>
      
      <div class="row">
        <div class="col-md-4">
          <div class="card mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-xl">
                  <span class="avatar-initial rounded-circle bg-label-primary">
                    {{ substr($customer->name, 0, 1) }}
                  </span>
                </div>
                <div class="ms-3">
                  <h5 class="mb-0">{{ $customer->name }}</h5>
                  <div class="mt-1">{!! $customer->status_badge !!}</div>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                  <p class="mb-0">Customer Type</p>
                  <h6 class="mb-0">{{ $customer->customerType->name ?? 'N/A' }}</h6>
                </div>
                <div class="text-end">
                  <p class="mb-0">Member Since</p>
                  <h6 class="mb-0">{{ $customer->created_at->format('M d, Y') }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-8">
          <div class="card mb-4">
            <h5 class="card-header">Customer Information</h5>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-4">
                  <p class="mb-0">Full Name</p>
                  <h6 class="mb-0">{{ $customer->name }}</h6>
                </div>
                <div class="col-md-4">
                  <p class="mb-0">Email</p>
                  <h6 class="mb-0">{{ $customer->email }}</h6>
                </div>
                <div class="col-md-4">
                  <p class="mb-0">Phone</p>
                  <h6 class="mb-0">{{ $customer->phone ?? 'N/A' }}</h6>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-0">Address</p>
                  <h6 class="mb-0">{{ $customer->address ?? 'N/A' }}</h6>
                </div>
                <div class="col-md-6">
                  <p class="mb-0">Status</p>
                  <h6 class="mb-0">{!! $customer->status_badge !!}</h6>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card mb-4">
            <h5 class="card-header">Recent Invoices</h5>
            <div class="card-body">
              @if($customer->invoices && $customer->invoices->count() > 0)
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($customer->invoices->take(5) as $invoice)
                        <tr>
                          <td>{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</td>
                          <td>{{ $invoice->date ?? $invoice->created_at->format('M d, Y') }}</td>
                          <td>${{ number_format($invoice->amount ?? 0, 2) }}</td>
                          <td>
                            <span class="badge bg-label-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
                              {{ ucfirst($invoice->status ?? 'pending') }}
                            </span>
                          </td>
                          <td>
                            <a href="#" class="btn btn-sm btn-primary">View</a>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <p>No invoices found for this customer.</p>
              @endif
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-3">
        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary me-2">Edit Customer</a>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to List</a>
      </div>
    </div>
  </div>
@endsection
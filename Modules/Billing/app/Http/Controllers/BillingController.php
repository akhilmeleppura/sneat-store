<?php

namespace Modules\Billing\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Billing\App\Models\BillingInvoice;
use Modules\Billing\App\Models\BillingInvoiceItem;
use App\Models\Customers\Customer;
use Modules\General\App\Models\Company;
use Modules\General\App\Models\Branch;
use Modules\Billing\App\Models\BillingItem;
use App\Models\Taxes\Tax;


class BillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('billing::billings.billings');
    }

    /**
     * Show the form for creating a new resource.
     */
   

    public function create()
    {
        $invoice = new BillingInvoice();
        $clients = Customer::all();
        $user = auth()->user();
        $company = Company::find($user->company_id);
        $branch = Branch::find($user->branch_id);
        $items = BillingItem::all();
        $taxes = Tax::all();

        $lastInvoice = BillingInvoice::latest('id')->first();
        if ($lastInvoice) {
            $prefix = $lastInvoice->document_prefix;
            $lastNumber = (int) filter_var($lastInvoice->document_number, FILTER_SANITIZE_NUMBER_INT);
            $nextInvoiceNumber = $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $prefix = 'INV-';
            $nextInvoiceNumber = $prefix . '0001';
        }

        return view('billing::billings.form', compact(
            'invoice',
            'clients',
            'company',
            'branch',
            'items',
            'taxes',
            'nextInvoiceNumber'
        ));
    }

    public function edit($id)
    {
        $invoice = BillingInvoice::findOrFail($id);
        $clients = Customer::all();
        $user = auth()->user();
        $company = Company::find($user->company_id);
        $branch = Branch::find($user->branch_id);
        $items = BillingItem::all();
        $taxes = Tax::all();

        $invoiceNumber = $invoice->document_prefix . '-' . $invoice->document_number;

        return view('billing::billings.edit', compact(
            'invoice',
            'clients',
            'company',
            'branch',
            'items',
            'taxes',
            'invoiceNumber'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($invoiceId)
    {
        $invoice = BillingInvoice::with([
            'customer',
            'items.billingItem',
            'items.item',
            'items.company',  // Load company from items
            'items.branch',   // Load branch from items
            'salesperson',
            'createdBy'
        ])->findOrFail($invoiceId);

        return view('billing::billings.show', compact('invoice'));
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}

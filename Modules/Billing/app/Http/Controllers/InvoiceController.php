<?php

namespace Modules\Billing\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Billing\App\Models\BillingInvoice;
use Modules\Billing\App\Models\BillingInvoiceItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\HS\Reply; // make sure you import this

class InvoiceController extends Controller
{

    public function getInvoices(Request $request)
    {
        Log::info('getInvoices called', ['request' => $request->all()]);

        $query = BillingInvoice::with(['customer' => function ($query) {
            $query->select('id', 'name', 'email');
        }])
            ->select([
                'billing_invoices.id',
                'billing_invoices.customer_id',
                'billing_invoices.document_number',
                'billing_invoices.issue_date',
                'billing_invoices.sub_total',
                'billing_invoices.payment_status',
                'billing_invoices.document_discount_amount',
                DB::raw('(billing_invoices.sub_total - COALESCE(billing_invoices.document_discount_amount, 0)) as balance')
            ])
            ->latest();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%$search%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        $start  = $request->input('start', 0);
        $length = $request->input('length', 1000);

        $recordsTotal = BillingInvoice::count();
        $recordsFiltered = $query->count();

        $invoices = $query->skip($start)->take($length)->get();

        $data = $invoices->map(function ($invoice) {
            return [
                'invoice_id'      => $invoice->id,
                'invoice_status'  => $invoice->payment_status,
                // Format date to YYYY-MM-DD or any format you want
                'issued_date'     => $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : '',
                'client_name'     => $invoice->customer->name ?? 'Unknown',
                'total'           => $invoice->sub_total,
                'balance'         => $invoice->balance,
                'action'          => '',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }



    public function show($invoiceId)
    {
        $invoice = BillingInvoice::with('customer')->findOrFail($invoiceId);

        return view('billing::billings.show', compact('invoice'));
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('billing::index');
    }


    // public function store(Request $request)
    // {
    //     // Validate the request
    //     $request->validate([
    //         'invoice_number' => 'required|string',
    //         'client_id' => 'required|exists:customers,id',
    //         'issue_date' => 'required|date',
    //         'due_date' => 'required|date|after_or_equal:issue_date',
    //         'items' => 'required|array',
    //         'items.*.item_id' => 'required|exists:billing_items,id',
    //         'items.*.quantity' => 'required|numeric|min:0.01',
    //         'items.*.unit_price' => 'required|numeric|min:0',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $user = auth()->user();
    //         list($prefix, $number) = explode('-', $request->invoice_number);

    //         // Create the main invoice
    //         $invoice = BillingInvoice::create([
    //             'document_prefix'          => $prefix,
    //             'document_number'          => $number,
    //             'customer_id'              => $request->client_id,
    //             'issue_date'               => $request->issue_date,
    //             'due_date'                 => $request->due_date,
    //             'sub_total'                => $request->sub_total,
    //             'document_discount_type'   => $request->document_discount_type,
    //             'document_discount_rate'   => $request->document_discount_rate,
    //             'document_discount_amount' => $request->document_discount_amount,
    //         ]);

    //         // Save invoice items
    //         foreach ($request->items as $item) {
    //             BillingInvoiceItem::create([
    //                 'document_id'        => $invoice->id,
    //                 'item_id'            => $item['item_id'],
    //                 'quantity'           => $item['quantity'],
    //                 'selling_unit_price' => $item['unit_price'],
    //                 'taxes'              => json_encode($item['taxes'] ?? []),
    //                 'company_id'         => $user->company_id,
    //                 'branch_id'          => $user->branch_id,
    //             ]);
    //         }

    //         DB::commit();

    //         // Return success response without data
    //         return Reply::success("Invoice {$invoice->document_prefix}-{$invoice->document_number} created successfully!");

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         // Return error response with proper status code
    //         return Reply::error('Error creating invoice: ' . $e->getMessage(), 500);
    //     }
    // }


    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'invoice_number' => 'required|string',
            'client_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:billing_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',

        ]);

        DB::beginTransaction();
        try {
            $user = auth()->user();
            list($prefix, $number) = explode('-', $request->invoice_number);

            // Create the main invoice
            $invoice = BillingInvoice::create([
                'document_prefix'          => $prefix,
                'document_number'          => $number,
                'customer_id'              => $request->client_id,
                'issue_date'               => $request->issue_date,
                'due_date'                 => $request->due_date,
                'sub_total'                => $request->sub_total,
                'document_discount_type'   => $request->document_discount_type,
                'document_discount_rate'   => $request->document_discount_rate,
                'document_discount_amount' => $request->document_discount_amount,
                'document_tax_id'      => $request->tax_id,

            ]);

            // Save invoice items
            foreach ($request->items as $item) {
                BillingInvoiceItem::create([
                    'document_id'        => $invoice->id,
                    'item_id'            => $item['item_id'],
                    'quantity'           => $item['quantity'],
                    'selling_unit_price' => $item['unit_price'],
                    'tax_id'              => $item['tax_id'],
                    'discount_rate' => $item['discount_percent'],
                    'subtotal' => $item['total_price'],
                    'company_id'         => $user->company_id,
                    'branch_id'          => $user->branch_id,
                ]);
            }

            DB::commit();

            // Return success response without data
            return Reply::success("Invoice {$invoice->document_prefix}-{$invoice->document_number} created successfully!");
        } catch (\Exception $e) {
            DB::rollBack();

            // Return error response with proper status code
            return Reply::error('Error creating invoice: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Update the specified invoice in storage.
     */
   public function update(Request $request, $id)
{
    // Validate the request
    $request->validate([
        'client_id' => 'required|exists:customers,id',
        'issue_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:issue_date',
        'existing_items' => 'sometimes|array',
        'existing_items.*.id' => 'required|exists:billing_invoice_items,id',
        'existing_items.*.item_id' => 'required|exists:billing_items,id',
        'existing_items.*.quantity' => 'required|numeric|min:0.01',
        'existing_items.*.unit_price' => 'required|numeric|min:0',
        'items' => 'sometimes|array',
        'items.*.item_id' => 'required|exists:billing_items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
        'tax_id' => 'nullable|exists:taxes,id',
    ]);

    DB::beginTransaction();
    try {
        $invoice = BillingInvoice::findOrFail($id);

        // Update invoice details
        $invoice->customer_id              = $request->client_id;
        $invoice->issue_date               = $request->issue_date;
        $invoice->due_date                 = $request->due_date;
        $invoice->sub_total                = $request->sub_total;
        $invoice->document_discount_type   = $request->document_discount_type;
        $invoice->document_discount_rate   = $request->document_discount_rate;
        $invoice->document_discount_amount = $request->document_discount_amount;
        $invoice->document_tax_id          = $request->tax_id;
        $invoice->save();

        $existingItemIds = [];

        // Update existing items
        if ($request->has('existing_items')) {
            foreach ($request->existing_items as $itemData) {
                $invoiceItem = BillingInvoiceItem::find($itemData['id']);
                if ($invoiceItem) {
                    $invoiceItem->item_id            = $itemData['item_id'];
                    $invoiceItem->quantity           = $itemData['quantity'];
                    $invoiceItem->selling_unit_price = $itemData['unit_price'];
                    $invoiceItem->tax_id             = $itemData['tax_id'] ?? null;
                    $invoiceItem->discount_rate      = $itemData['discount_percent'] ?? 0;
                    $invoiceItem->subtotal           = $itemData['total_price'];
                    $invoiceItem->save();
                    $existingItemIds[] = $invoiceItem->id;
                }
            }
        }

        // Delete removed items
        $invoice->items()->whereNotIn('id', $existingItemIds)->delete();

        // Add new items
        if ($request->has('items')) {
            foreach ($request->items as $itemData) {
                if (!empty($itemData['item_id'])) {
                    BillingInvoiceItem::create([
                        'document_id'        => $invoice->id,
                        'item_id'            => $itemData['item_id'],
                        'description'        => $itemData['description'] ?? '',
                        'quantity'           => $itemData['quantity'],
                        'selling_unit_price' => $itemData['unit_price'],
                        'tax_id'             => $itemData['tax_id'] ?? null,
                        'discount_rate'      => $itemData['discount_percent'] ?? 0,
                        'subtotal'           => $itemData['total_price'],
                        'company_id'         => auth()->user()->company_id,
                        'branch_id'          => auth()->user()->branch_id,
                    ]);
                }
            }
        }

        DB::commit();

        return Reply::success("Invoice {$invoice->document_prefix}-{$invoice->document_number} updated successfully!");
    } catch (\Exception $e) {
        DB::rollBack();
        return Reply::error('Error updating invoice: ' . $e->getMessage(), 500);
    }
}

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy($id)
    {
        try {
            $invoice = BillingInvoice::findOrFail($id);
            $invoiceNumber = $invoice->document_prefix . '-' . $invoice->document_number;

            // Delete invoice items first
            $invoice->items()->delete();

            // Delete the invoice
            $invoice->delete();

            // Return success response
            return Reply::success("Invoice {$invoiceNumber} deleted successfully!");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return not found response
            return Reply::notFound('The requested invoice was not found.');
        } catch (\Exception $e) {
            // Return error response
            return Reply::error('Error deleting invoice: ' . $e->getMessage(), 500);
        }
    }
}

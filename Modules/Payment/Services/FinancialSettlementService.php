<?php

namespace Modules\Payment\Services;

use App\Models\Customers\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\App\Models\ChartOfAccount;
use Modules\Accounting\App\Models\JournalEntries;
use Modules\Accounting\App\Models\JournalIndex;
use Modules\Accounting\App\Models\MainCategory;
use Modules\Accounting\App\Models\SubCategory;
use Modules\Billing\App\Models\BillingInvoice;
use Modules\Billing\App\Models\BillingInvoiceItem;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Payment\Models\PaymentTransaction;

class FinancialSettlementService
{
    /**
     * Settle an order upon successful payment.
     * Executes atomic inventory commit, billing invoice creation,
     * and general ledger double-entry bookkeeping.
     */
    public function settleOrder(Order $order, ?string $transactionReference = null): array
    {
        // 1. Idempotency check: Don't settle if invoice already linked
        if ($order->payment_status === 'paid' && !empty($order->metadata['invoice_id'] ?? null)) {
            Log::info("Order #{$order->order_number} already settled. Skipping duplicate settlement.");
            return [
                'status'         => 'already_settled',
                'invoice_id'     => $order->metadata['invoice_id'],
                'journal_id'     => $order->metadata['journal_index_id'] ?? null,
            ];
        }

        return DB::transaction(function () use ($order, $transactionReference) {
            // 2. Commit reserved inventory
            try {
                $inventoryService = app(InventoryService::class);
                $branchId = $order->branch_id ?? $order->tenant_branch_id ?? 1;
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        $inventoryService->commitReservedStock(
                            $item->product_variant_id,
                            $branchId,
                            $item->quantity,
                            $order->order_number
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Inventory commit notice for Order #{$order->order_number}: " . $e->getMessage());
            }

            // 3. Billing Module: Create or find Customer
            $customerTypeId = DB::table('customer_types')->value('id');
            if (!$customerTypeId) {
                $customerTypeId = DB::table('customer_types')->insertGetId([
                    'name'       => 'Retail E-Commerce',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $customer = Customer::firstOrCreate(
                ['email' => $order->customer_email],
                [
                    'name'             => $order->customer_name,
                    'phone'            => $order->customer_phone,
                    'address'          => is_array($order->shipping_address) ? json_encode($order->shipping_address) : $order->shipping_address,
                    'status'           => 'active',
                    'customer_type_id' => $customerTypeId,
                ]
            );

            // 4. Billing Module: Create BillingInvoice
            $invoiceNumber = 'INV-' . str_replace('ORD-', '', $order->order_number);
            $invoice = BillingInvoice::firstOrCreate(
                ['document_number' => $invoiceNumber],
                [
                    'document_prefix'          => 'INV-',
                    'customer_id'              => $customer->id,
                    'issue_date'               => now()->toDateString(),
                    'due_date'                 => now()->toDateString(),
                    'sub_total'                => $order->subtotal,
                    'document_discount_type'   => 0,
                    'document_discount_rate'   => 0,
                    'document_discount_amount' => $order->discount_amount,
                    'payment_status'           => 1,
                ]
            );

            // Create BillingInvoiceItems
            foreach ($order->items as $orderItem) {
                $billingItem = \Modules\Billing\App\Models\BillingItem::firstOrCreate(
                    ['name' => $orderItem->product_name],
                    [
                        'type'               => '1',
                        'selling_unit_price' => $orderItem->unit_price,
                    ]
                );

                BillingInvoiceItem::firstOrCreate(
                    [
                        'document_id' => $invoice->id,
                        'item_id'     => $billingItem->id,
                    ],
                    [
                        'quantity'           => $orderItem->quantity,
                        'selling_unit_price' => $orderItem->unit_price,
                        'discount_amount'    => $orderItem->discount_amount ?? 0,
                        'discount_type'      => 'fixed',
                        'subtotal'           => $orderItem->line_total,
                        'company_id'         => null,
                        'branch_id'          => null,
                    ]
                );
            }

            // 5. Accounting Module: Ensure Accounts & Categories Exist
            $mainCategoryAsset = MainCategory::firstOrCreate(
                ['name' => 'Assets'],
                ['type' => 'Asset']
            );
            $mainCategoryLiability = MainCategory::firstOrCreate(
                ['name' => 'Liabilities'],
                ['type' => 'Liability']
            );
            $mainCategoryRevenue = MainCategory::firstOrCreate(
                ['name' => 'Revenue'],
                ['type' => 'Income']
            );

            $subCategoryCurrentAssets = SubCategory::firstOrCreate(
                ['name' => 'Current Assets'],
                [
                    'main_category_id' => $mainCategoryAsset->id,
                    'description'      => 'Operating Cash, Bank Accounts, and Payment Gateway Clearing',
                ]
            );
            $subCategoryLiabilities = SubCategory::firstOrCreate(
                ['name' => 'Current Liabilities'],
                [
                    'main_category_id' => $mainCategoryLiability->id,
                    'description'      => 'Sales Tax & VAT Payable to Authorities',
                ]
            );
            $subCategoryOperatingRevenue = SubCategory::firstOrCreate(
                ['name' => 'Operating Revenue'],
                [
                    'main_category_id' => $mainCategoryRevenue->id,
                    'description'      => 'E-Commerce Product Sales & Shipping Charges',
                ]
            );

            // Chart of Accounts
            $cashAccount = ChartOfAccount::firstOrCreate(
                ['identifier' => config('payment.settlement.clearing_account_identifier', '1010')],
                [
                    'account_name'      => config('payment.settlement.clearing_account_name', 'Operating Cash & Gateway Clearing'),
                    'main_category_id'  => $mainCategoryAsset->id,
                    'subcategory_id'    => $subCategoryCurrentAssets->id,
                    'cumulative_debit'  => 0,
                    'cumulative_credit' => 0,
                ]
            );

            $salesAccount = ChartOfAccount::firstOrCreate(
                ['identifier' => config('payment.settlement.sales_revenue_identifier', '4000')],
                [
                    'account_name'      => config('payment.settlement.sales_revenue_name', 'E-Commerce Sales Revenue'),
                    'main_category_id'  => $mainCategoryRevenue->id,
                    'subcategory_id'    => $subCategoryOperatingRevenue->id,
                    'cumulative_debit'  => 0,
                    'cumulative_credit' => 0,
                ]
            );

            $taxAccount = ChartOfAccount::firstOrCreate(
                ['identifier' => config('payment.settlement.tax_payable_identifier', '2020')],
                [
                    'account_name'      => config('payment.settlement.tax_payable_name', 'Sales Tax & VAT Payable'),
                    'main_category_id'  => $mainCategoryLiability->id,
                    'subcategory_id'    => $subCategoryLiabilities->id,
                    'cumulative_debit'  => 0,
                    'cumulative_credit' => 0,
                ]
            );

            $shippingAccount = ChartOfAccount::firstOrCreate(
                ['identifier' => config('payment.settlement.shipping_revenue_identifier', '4010')],
                [
                    'account_name'      => config('payment.settlement.shipping_revenue_name', 'Shipping & Handling Revenue'),
                    'main_category_id'  => $mainCategoryRevenue->id,
                    'subcategory_id'    => $subCategoryOperatingRevenue->id,
                    'cumulative_debit'  => 0,
                    'cumulative_credit' => 0,
                ]
            );

            // Create Journal Index
            $journalNumber = 'JRN-' . str_replace('ORD-', '', $order->order_number);
            $journal = JournalIndex::firstOrCreate(
                ['journal_number' => $journalNumber],
                [
                    'transaction_date'   => now()->toDateString(),
                    'created_by'         => $order->user_id ?? 1,
                    'number_of_entries'  => 2 + ($order->tax_amount > 0 ? 1 : 0) + ($order->shipping_amount > 0 ? 1 : 0),
                    'transaction_amount' => $order->grand_total,
                    'summary'            => "Settlement for Order #{$order->order_number} ({$order->customer_name})",
                ]
            );

            // Double Entry: DEBIT Cash/Clearing Account
            JournalEntries::firstOrCreate(
                [
                    'journal_id'          => $journal->id,
                    'chart_of_account_id' => $cashAccount->id,
                ],
                [
                    'debit_amount'  => $order->grand_total,
                    'credit_amount' => 0,
                    'description'   => "Payment received for Order #{$order->order_number}",
                ]
            );
            $cashAccount->increment('cumulative_debit', $order->grand_total);

            // Double Entry: CREDIT Sales Revenue (Net product amount: subtotal - discount)
            $netSales = max(0, $order->subtotal - $order->discount_amount);
            JournalEntries::firstOrCreate(
                [
                    'journal_id'          => $journal->id,
                    'chart_of_account_id' => $salesAccount->id,
                ],
                [
                    'debit_amount'  => 0,
                    'credit_amount' => $netSales,
                    'description'   => "Product sales revenue from Order #{$order->order_number}",
                ]
            );
            $salesAccount->increment('cumulative_credit', $netSales);

            // Double Entry: CREDIT Sales Tax Payable (if applicable)
            if ($order->tax_amount > 0) {
                JournalEntries::firstOrCreate(
                    [
                        'journal_id'          => $journal->id,
                        'chart_of_account_id' => $taxAccount->id,
                    ],
                    [
                        'debit_amount'  => 0,
                        'credit_amount' => $order->tax_amount,
                        'description'   => "Sales tax collected for Order #{$order->order_number}",
                    ]
                );
                $taxAccount->increment('cumulative_credit', $order->tax_amount);
            }

            // Double Entry: CREDIT Shipping Revenue (if applicable)
            if ($order->shipping_amount > 0) {
                JournalEntries::firstOrCreate(
                    [
                        'journal_id'          => $journal->id,
                        'chart_of_account_id' => $shippingAccount->id,
                    ],
                    [
                        'debit_amount'  => 0,
                        'credit_amount' => $order->shipping_amount,
                        'description'   => "Shipping fee revenue from Order #{$order->order_number}",
                    ]
                );
                $shippingAccount->increment('cumulative_credit', $order->shipping_amount);
            }

            // 6. Update PaymentTransaction record
            if ($transactionReference) {
                PaymentTransaction::where('transaction_reference', $transactionReference)
                    ->update(['status' => 'successful']);
            } else {
                PaymentTransaction::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'successful']);
            }

            // 7. Update Order
            $orderMetadata = $order->metadata ?? [];
            $orderMetadata['invoice_id']         = $invoice->id;
            $orderMetadata['invoice_number']     = $invoiceNumber;
            $orderMetadata['journal_index_id']   = $journal->id;
            $orderMetadata['journal_number']     = $journalNumber;
            $orderMetadata['settled_at']         = now()->toISOString();

            $order->update([
                'payment_status'     => 'paid',
                'fulfillment_status' => 'fulfilled',
                'status'             => $order->status === 'pending' ? 'processing' : $order->status,
                'metadata'           => $orderMetadata,
            ]);

            // 8. Multi-Vendor Commission Attribution
            try {
                if (class_exists(\Modules\Marketplace\Services\CommissionService::class)) {
                    app(\Modules\Marketplace\Services\CommissionService::class)->recordOrderEarnings($order);
                }
            } catch (\Throwable $e) {
                Log::warning("Marketplace commission calculation notice for Order #{$order->order_number}: " . $e->getMessage());
            }

            // 9. Dispatch Payment Settled Event
            try {
                event(new \Modules\Payment\Events\OrderPaymentSettledEvent($order, null));
            } catch (\Throwable $e) {
                Log::warning("OrderPaymentSettledEvent notice for Order #{$order->order_number}: " . $e->getMessage());
            }

            Log::info("Financial settlement complete for Order #{$order->order_number}: Invoice {$invoiceNumber}, Journal {$journalNumber}");

            return [
                'status'         => 'settled',
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'journal_id'     => $journal->id,
                'journal_number' => $journalNumber,
            ];
        });
    }
}

<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Inventory\Services\InventoryReportService;
use Modules\Marketplace\Services\VendorAnalyticsService;
use Modules\Order\Models\Order;
use Modules\Order\Services\AnalyticsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsReportController extends Controller
{
    /**
     * E-Commerce sales analytics dashboard.
     */
    public function sales(Request $request, AnalyticsService $analyticsService)
    {
        $period = $request->get('period', '30_days');
        $storeId = $request->filled('store_id') ? (int) $request->store_id : null;

        $overview = $analyticsService->getSalesOverview($period, $storeId);
        $chartData = $analyticsService->getSalesChartData($period, $storeId);
        $topProducts = $analyticsService->getTopSellingProducts(5, $period, $storeId);

        $recentOrders = Order::with('items')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->latest()
            ->take(10)
            ->get();

        $stores = Store::all();

        return view('order::admin.reports.sales', compact(
            'overview',
            'chartData',
            'topProducts',
            'recentOrders',
            'period',
            'storeId',
            'stores'
        ));
    }

    /**
     * Marketplace vendor commission and performance ledger.
     */
    public function vendors(Request $request, VendorAnalyticsService $vendorAnalyticsService)
    {
        $period = $request->get('period', '30_days');

        $overview = $vendorAnalyticsService->getMarketplaceOverview($period);
        $leaderboard = $vendorAnalyticsService->getVendorLeaderboard(15, $period);

        return view('order::admin.reports.vendors', compact(
            'overview',
            'leaderboard',
            'period'
        ));
    }

    /**
     * Branch inventory health and stock valuation report.
     */
    public function inventory(Request $request, InventoryReportService $inventoryReportService)
    {
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        $overview = $inventoryReportService->getInventoryOverview($branchId);
        $lowStockItems = $inventoryReportService->getLowStockItems(25, $branchId);
        $branches = Branch::all();

        return view('order::admin.reports.inventory', compact(
            'overview',
            'lowStockItems',
            'branchId',
            'branches'
        ));
    }

    /**
     * Export sales records as a streamed CSV file.
     */
    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $period = $request->get('period', '30_days');
        $end = Carbon::now()->endOfDay();
        $start = match ($period) {
            'today'   => Carbon::today()->startOfDay(),
            '7_days'  => Carbon::now()->subDays(6)->startOfDay(),
            'year'    => Carbon::now()->subYear()->startOfDay(),
            default   => Carbon::now()->subDays(29)->startOfDay(),
        };

        $fileName = 'sales_report_' . Carbon::now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($start, $end) {
            $handle = fopen('php://output', 'w');

            // Header Row
            fputcsv($handle, [
                'Order Number',
                'Date',
                'Customer Name',
                'Customer Email',
                'Status',
                'Payment Status',
                'Payment Method',
                'Subtotal (USD)',
                'Discount (USD)',
                'Tax (USD)',
                'Shipping (USD)',
                'Grand Total (USD)',
            ]);

            Order::whereBetween('created_at', [$start, $end])
                ->orderBy('created_at', 'desc')
                ->chunk(100, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->order_number,
                            $order->created_at->format('Y-m-d H:i:s'),
                            $order->customer_name,
                            $order->customer_email,
                            $order->status,
                            $order->payment_status,
                            $order->payment_method,
                            number_format($order->subtotal, 2, '.', ''),
                            number_format($order->discount_amount, 2, '.', ''),
                            number_format($order->tax_amount, 2, '.', ''),
                            number_format($order->shipping_amount, 2, '.', ''),
                            number_format($order->grand_total, 2, '.', ''),
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

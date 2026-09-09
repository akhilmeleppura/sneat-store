<?php

namespace Modules\Order\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Context\Facades\Context;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class AnalyticsService
{
    /**
     * Resolve date range from period string.
     */
    protected function resolvePeriod(string $period): array
    {
        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'today'   => Carbon::today()->startOfDay(),
            '7_days'  => Carbon::now()->subDays(6)->startOfDay(),
            'year'    => Carbon::now()->subYear()->startOfDay(),
            default   => Carbon::now()->subDays(29)->startOfDay(), // 30_days
        };

        return [$start, $end];
    }

    /**
     * Get aggregate sales KPI metrics.
     */
    public function getSalesOverview(string $period = '30_days', ?int $storeId = null): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $query = Order::whereBetween('created_at', [$start, $end]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $totalOrders = (clone $query)->count();
        $completedOrders = (clone $query)->where('status', 'completed')->count();
        $processingOrders = (clone $query)->where('status', 'processing')->count();
        $cancelledOrders = (clone $query)->where('status', 'cancelled')->count();

        $grossSales = (float) (clone $query)->whereNotIn('status', ['cancelled'])->sum('grand_total');
        $subtotalSales = (float) (clone $query)->whereNotIn('status', ['cancelled'])->sum('subtotal');
        $taxTotal = (float) (clone $query)->whereNotIn('status', ['cancelled'])->sum('tax_amount');
        $shippingTotal = (float) (clone $query)->whereNotIn('status', ['cancelled'])->sum('shipping_amount');
        $discountTotal = (float) (clone $query)->whereNotIn('status', ['cancelled'])->sum('discount_amount');

        $netSales = max(0, $subtotalSales - $discountTotal);
        $averageOrderValue = $totalOrders > 0 ? round($grossSales / $totalOrders, 2) : 0.00;

        return [
            'period'              => $period,
            'start_date'          => $start->toDateString(),
            'end_date'            => $end->toDateString(),
            'total_orders'        => $totalOrders,
            'completed_orders'    => $completedOrders,
            'processing_orders'   => $processingOrders,
            'cancelled_orders'    => $cancelledOrders,
            'gross_sales'         => round($grossSales, 2),
            'net_sales'           => round($netSales, 2),
            'subtotal'            => round($subtotalSales, 2),
            'tax_total'           => round($taxTotal, 2),
            'shipping_total'      => round($shippingTotal, 2),
            'discount_total'      => round($discountTotal, 2),
            'average_order_value' => $averageOrderValue,
            'completion_rate'     => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0.0,
        ];
    }

    /**
     * Get time-series daily/monthly sales data for ApexCharts.
     */
    public function getSalesChartData(string $period = '30_days', ?int $storeId = null): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $query = Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Aggregate daily
        $rawRecords = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(grand_total) as revenue')
        )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill all dates in the range
        $labels = [];
        $revenueSeries = [];
        $ordersSeries = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $labels[] = $current->format('M d');

            if ($rawRecords->has($dateStr)) {
                $revenueSeries[] = round((float) $rawRecords[$dateStr]->revenue, 2);
                $ordersSeries[] = (int) $rawRecords[$dateStr]->order_count;
            } else {
                $revenueSeries[] = 0.0;
                $ordersSeries[] = 0;
            }

            $current->addDay();
        }

        return [
            'labels'   => $labels,
            'revenue'  => $revenueSeries,
            'orders'   => $ordersSeries,
            'currency' => 'USD',
        ];
    }

    /**
     * Get top selling products by quantity and revenue.
     */
    public function getTopSellingProducts(int $limit = 5, string $period = '30_days', ?int $storeId = null): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereNotIn('orders.status', ['cancelled']);

        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        return $query->select(
            'order_items.product_id',
            'order_items.product_name',
            'order_items.variant_sku',
            DB::raw('SUM(order_items.quantity) as total_qty'),
            DB::raw('SUM(order_items.line_total) as total_revenue')
        )
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.variant_sku')
            ->orderByDesc('total_qty')
            ->take($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'product_id'    => $row->product_id,
                    'product_name'  => $row->product_name,
                    'sku'           => $row->variant_sku,
                    'total_qty'     => (int) $row->total_qty,
                    'total_revenue' => round((float) $row->total_revenue, 2),
                ];
            })
            ->toArray();
    }
}

<?php

namespace Modules\Marketplace\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorEarning;
use Modules\Marketplace\Models\VendorPayout;
use Modules\Order\Models\OrderItem;

class VendorAnalyticsService
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
            default   => Carbon::now()->subDays(29)->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * Get platform-wide marketplace KPI metrics.
     */
    public function getMarketplaceOverview(string $period = '30_days'): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $totalVendors = Vendor::count();
        $activeVendors = Vendor::where('status', 'active')->count();

        $earningsQuery = VendorEarning::whereBetween('created_at', [$start, $end]);

        $marketplaceGMV = (float) (clone $earningsQuery)->sum('gross_amount');
        $platformCommissions = (float) (clone $earningsQuery)->sum('commission_amount');
        $vendorNetEarnings = (float) (clone $earningsQuery)->sum('net_amount');

        $payoutsQuery = VendorPayout::whereBetween('created_at', [$start, $end]);
        $totalPayoutsCompleted = (float) (clone $payoutsQuery)->where('status', 'completed')->sum('amount');
        $totalPayoutsPending = (float) (clone $payoutsQuery)->where('status', 'requested')->sum('amount');

        $totalVendorBalances = (float) Vendor::sum('balance');

        return [
            'period'                  => $period,
            'total_vendors'           => $totalVendors,
            'active_vendors'          => $activeVendors,
            'marketplace_gmv'         => round($marketplaceGMV, 2),
            'platform_commissions'    => round($platformCommissions, 2),
            'vendor_net_earnings'     => round($vendorNetEarnings, 2),
            'payouts_completed'       => round($totalPayoutsCompleted, 2),
            'payouts_pending'         => round($totalPayoutsPending, 2),
            'total_vendor_balances'   => round($totalVendorBalances, 2),
        ];
    }

    /**
     * Get vendor sales leaderboard.
     */
    public function getVendorLeaderboard(int $limit = 10, string $period = '30_days'): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        return VendorEarning::join('marketplace_vendors', 'marketplace_vendor_earnings.vendor_id', '=', 'marketplace_vendors.id')
            ->whereBetween('marketplace_vendor_earnings.created_at', [$start, $end])
            ->select(
                'marketplace_vendors.id as vendor_id',
                'marketplace_vendors.name as vendor_name',
                'marketplace_vendors.email as vendor_email',
                'marketplace_vendors.commission_rate',
                'marketplace_vendors.balance',
                DB::raw('COUNT(marketplace_vendor_earnings.id) as sales_count'),
                DB::raw('SUM(marketplace_vendor_earnings.gross_amount) as total_gross'),
                DB::raw('SUM(marketplace_vendor_earnings.commission_amount) as total_commission'),
                DB::raw('SUM(marketplace_vendor_earnings.net_amount) as total_net')
            )
            ->groupBy(
                'marketplace_vendors.id',
                'marketplace_vendors.name',
                'marketplace_vendors.email',
                'marketplace_vendors.commission_rate',
                'marketplace_vendors.balance'
            )
            ->orderByDesc('total_gross')
            ->take($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'vendor_id'        => $row->vendor_id,
                    'vendor_name'      => $row->vendor_name,
                    'vendor_email'     => $row->vendor_email,
                    'commission_rate'  => (float) $row->commission_rate,
                    'balance'          => round((float) $row->balance, 2),
                    'sales_count'      => (int) $row->sales_count,
                    'total_gross'      => round((float) $row->total_gross, 2),
                    'total_commission' => round((float) $row->total_commission, 2),
                    'total_net'        => round((float) $row->total_net, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get performance metrics for a specific vendor.
     */
    public function getVendorPerformance(int $vendorId, string $period = '30_days'): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $vendor = Vendor::findOrFail($vendorId);

        $earningsQuery = VendorEarning::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$start, $end]);

        $gross = (float) (clone $earningsQuery)->sum('gross_amount');
        $commission = (float) (clone $earningsQuery)->sum('commission_amount');
        $net = (float) (clone $earningsQuery)->sum('net_amount');
        $itemsSold = (int) (clone $earningsQuery)->count();

        return [
            'vendor_id'       => $vendor->id,
            'vendor_name'     => $vendor->name,
            'balance'         => round((float) $vendor->balance, 2),
            'commission_rate' => (float) $vendor->commission_rate,
            'gross_sales'     => round($gross, 2),
            'commissions_paid'=> round($commission, 2),
            'net_earnings'    => round($net, 2),
            'items_sold'      => $itemsSold,
        ];
    }

    /**
     * Get time-series earnings and sales count for a specific vendor.
     */
    public function getVendorChartData(int $vendorId, string $period = '30_days'): array
    {
        [$start, $end] = $this->resolvePeriod($period);

        $rawRecords = VendorEarning::where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(net_amount) as net_earnings'),
                DB::raw('SUM(gross_amount) as gross_sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $netSeries = [];
        $salesCountSeries = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $labels[] = $current->format('M d');

            if ($rawRecords->has($dateStr)) {
                $netSeries[] = round((float) $rawRecords[$dateStr]->net_earnings, 2);
                $salesCountSeries[] = (int) $rawRecords[$dateStr]->sales_count;
            } else {
                $netSeries[] = 0.0;
                $salesCountSeries[] = 0;
            }

            $current->addDay();
        }

        return [
            'labels'       => $labels,
            'net_earnings' => $netSeries,
            'sales_count'  => $salesCountSeries,
        ];
    }
}

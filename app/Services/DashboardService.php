<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private BranchContextService $branchContext;

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getDashboardStats(User $user, ?int $branchId = null, string $range = 'today'): array
    {
        $branch = $this->resolveBranch($user, $branchId);
        $resolvedBranchId = (int) $branch->id;
        $selectedRange = $this->normalizeRange($range);
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $tomorrow = $today->copy()->addDay();
        $yesterday = $today->copy()->subDay();
        $lastSevenDaysStart = $today->copy()->subDays(6);

        $todaySales = $this->sumSalesBetween($resolvedBranchId, $today, $tomorrow);
        $yesterdaySales = $this->sumSalesBetween($resolvedBranchId, $yesterday, $today);
        $lastSevenDaysSales = $this->sumSalesBetween($resolvedBranchId, $lastSevenDaysStart, $tomorrow);
        $monthlySales = (float) $this->baseOrderQuery($resolvedBranchId)
            ->where('o.created_at', '>=', $monthStart)
            ->sum('o.grand_total');

        $todayClients = $this->countDistinctClientsBetween($resolvedBranchId, $today, $tomorrow);
        $yesterdayClients = $this->countDistinctClientsBetween($resolvedBranchId, $yesterday, $today);
        $todayOrders = $this->countOrdersBetween($resolvedBranchId, $today, $tomorrow);
        $clientTrend = $this->buildClientTrend($todayClients, $yesterdayClients);
        $rangeSales = $this->resolveRangeSales($selectedRange, $todaySales, $yesterdaySales, $lastSevenDaysSales);
        $rangeLabel = $this->resolveRangeLabel($selectedRange);

        $topServices = $this->buildTopServices($resolvedBranchId, $monthStart);
        $topMasseuses = $this->buildTopMasseuses($resolvedBranchId, $today, $tomorrow);
        $salesChart = $this->buildSalesChart($resolvedBranchId);

        $todayServiceSales = (int) round($this->sumSalesByItemType($resolvedBranchId, $today, $tomorrow, 'service'));
        $todayPackageSales = (int) round($this->sumSalesByItemType($resolvedBranchId, $today, $tomorrow, 'package'));
        $todayTotalCombinedSales = $todayServiceSales + $todayPackageSales;
        
        $dailyMasseuseFee = (int) round($this->sumCommissions($resolvedBranchId, $today, $tomorrow));
        $monthlyMasseuseFee = (int) round($this->sumCommissions($resolvedBranchId, $monthStart));
        $netProfit = (int) round($monthlySales) - $monthlyMasseuseFee;

        return [
            'branch_id' => $resolvedBranchId,
            'branch_name' => (string) $branch->name,
            'today_sales' => (int) round($todaySales),
            'yesterday_sales' => (int) round($yesterdaySales),
            'last_7_days_sales' => (int) round($lastSevenDaysSales),
            'today_clients' => $todayClients,
            'yesterday_clients' => $yesterdayClients,
            'today_orders' => $todayOrders,
            'client_trend' => $clientTrend,
            'selected_range' => $selectedRange,
            'selected_range_label' => $rangeLabel,
            'selected_range_sales' => (int) round($rangeSales),
            'monthly_sales' => (int) round($monthlySales),
            'daily_masseuse_fee' => $dailyMasseuseFee,
            'monthly_masseuse_fee' => $monthlyMasseuseFee,
            'today_service_sales' => $todayServiceSales,
            'today_package_sales' => $todayPackageSales,
            'today_total_combined_sales' => $todayTotalCombinedSales,
            'net_profit' => $netProfit,
            'last_sync' => Carbon::now()->format('H:i'),
            'top_services' => $topServices,
            'top_masseuses' => $topMasseuses,
            'sales_chart' => $salesChart,
        ];
    }

    public function getCashierDashboardData(User $user, ?int $branchId = null): array
    {
        $branch = $this->resolveBranch($user, $branchId);
        $resolvedBranchId = (int) $branch->id;
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $yesterday = $today->copy()->subDay();

        $todaySales = $this->sumSalesBetween($resolvedBranchId, $today, $tomorrow);
        $yesterdaySales = $this->sumSalesBetween($resolvedBranchId, $yesterday, $today);
        $todayOrders = $this->countOrdersBetween($resolvedBranchId, $today, $tomorrow);
        $yesterdayOrders = $this->countOrdersBetween($resolvedBranchId, $yesterday, $today);

        $todayMasseuses = $this->buildMasseuseSummaryRows($resolvedBranchId, $today, $tomorrow);
        $yesterdayMasseuses = $this->buildMasseuseSummaryRows($resolvedBranchId, $yesterday, $today);
        $masseuseComparisons = $this->mergeMasseuseComparisons($todayMasseuses, $yesterdayMasseuses);

        return [
            'branch_id' => $resolvedBranchId,
            'branch_name' => (string) $branch->name,
            'today_sales' => (int) round($todaySales),
            'yesterday_sales' => (int) round($yesterdaySales),
            'today_orders' => $todayOrders,
            'yesterday_orders' => $yesterdayOrders,
            'masseuses' => $masseuseComparisons,
            'last_sync' => Carbon::now()->format('H:i'),
        ];
    }

    private function normalizeRange(string $range): string
    {
        if (in_array($range, ['today', 'yesterday', '7d'], true)) {
            return $range;
        }

        return 'today';
    }

    private function resolveRangeLabel(string $range): string
    {
        if ($range === 'yesterday') {
            return 'เมื่อวาน';
        }

        if ($range === '7d') {
            return '7 วันย้อนหลัง';
        }

        return 'วันนี้';
    }

    private function resolveRangeSales(string $range, float $todaySales, float $yesterdaySales, float $lastSevenDaysSales): float
    {
        if ($range === 'yesterday') {
            return $yesterdaySales;
        }

        if ($range === '7d') {
            return $lastSevenDaysSales;
        }

        return $todaySales;
    }

    private function buildClientTrend(int $todayClients, int $yesterdayClients): array
    {
        if ($todayClients === $yesterdayClients) {
            return [
                'icon' => 'bi-arrow-right',
                'class' => 'text-secondary',
                'text' => '0% จากเมื่อวาน',
            ];
        }

        if ($yesterdayClients <= 0) {
            if ($todayClients <= 0) {
                return [
                    'icon' => 'bi-arrow-right',
                    'class' => 'text-secondary',
                    'text' => '0% จากเมื่อวาน',
                ];
            }

            return [
                'icon' => 'bi-arrow-up',
                'class' => 'text-success',
                'text' => '+100% จากเมื่อวาน',
            ];
        }

        $deltaPercent = (int) round((($todayClients - $yesterdayClients) / $yesterdayClients) * 100);
        $isUp = $deltaPercent > 0;

        return [
            'icon' => $isUp ? 'bi-arrow-up' : 'bi-arrow-down',
            'class' => $isUp ? 'text-success' : 'text-danger',
            'text' => ($isUp ? '+' : '') . $deltaPercent . '% จากเมื่อวาน',
        ];
    }

    private function resolveBranch(User $user, ?int $branchId): object
    {
        $authorizedBranchId = $this->branchContext->resolveAuthorizedBranchId($user, $branchId);
        $branchQuery = DB::table('branches')
            ->select('id', 'name')
            ->where('is_active', 1);

        if ($authorizedBranchId > 0) {
            $branch = (clone $branchQuery)
                ->where('id', $authorizedBranchId)
                ->first();

            if ($branch !== null) {
                return $branch;
            }
        }

        if (!$this->branchContext->canManageAllBranches($user)) {
            return (object) [
                'id' => $authorizedBranchId,
                'name' => 'Assigned Branch',
            ];
        }

        $branch = $branchQuery
            ->orderBy('id')
            ->first();

        if ($branch !== null) {
            return $branch;
        }

        return (object) [
            'id' => 1,
            'name' => 'Default Branch',
        ];
    }

    private function baseOrderQuery(int $branchId): Builder
    {
        $query = DB::table('orders as o')
            ->where('o.branch_id', $branchId);

        return $this->applyPaidScope($query, 'o');
    }

    private function sumSalesBetween(int $branchId, Carbon $from, Carbon $to): float
    {
        return (float) $this->baseOrderQuery($branchId)
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->sum('o.grand_total');
    }

    private function countDistinctClientsBetween(int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) $this->baseOrderQuery($branchId)
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->distinct('o.customer_id')
            ->count('o.customer_id');
    }

    private function countOrdersBetween(int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) $this->baseOrderQuery($branchId)
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->count('o.id');
    }

    private function applyPaidScope(Builder $query, string $alias): Builder
    {
        return $query->where(function (Builder $statusQuery) use ($alias): void {
            $statusQuery
                ->where($alias . '.status', 'paid')
                ->orWhereNull($alias . '.status');
        });
    }

    private function buildTopServices(int $branchId, Carbon $from): array
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('services as s', 's.id', '=', 'oi.item_id')
            ->where('o.branch_id', $branchId)
            ->where('oi.item_type', 'service')
            ->where('o.created_at', '>=', $from)
            ->selectRaw('s.name as name, AVG(oi.unit_price) as avg_price, SUM(oi.qty) as qty')
            ->groupBy('s.id', 's.name')
            ->orderByDesc('qty')
            ->limit(4);

        $rows = $this->applyPaidScope($query, 'o')->get();

        $totalQty = (int) $rows->sum(static function ($row): int {
            return (int) $row->qty;
        });

        return $rows->map(function ($row) use ($totalQty): array {
            $count = (int) $row->qty;
            $percent = $totalQty > 0 ? (int) round(($count / $totalQty) * 100) : 0;

            return [
                'name' => (string) $row->name,
                'price' => (int) round((float) $row->avg_price),
                'count' => $count,
                'icon' => '',
                'percent' => $percent,
            ];
        })->all();
    }

    private function buildTopMasseuses(int $branchId, Carbon $from, Carbon $to): array
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('masseuses as m', 'm.id', '=', 'oi.masseuse_id')
            ->where('o.branch_id', $branchId)
            ->where('oi.item_type', 'service')
            ->whereNotNull('oi.masseuse_id')
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->selectRaw(
                "oi.masseuse_id as id, " .
                "COALESCE(NULLIF(m.nickname, ''), m.full_name, CONCAT('Masseuse #', oi.masseuse_id)) as name, " .
                "SUM(oi.qty * oi.unit_price) as amount, " .
                "COUNT(DISTINCT o.id) as queue_count"
            )
            ->groupBy('oi.masseuse_id', 'm.nickname', 'm.full_name')
            ->orderByDesc('amount')
            ->limit(3);

        $rows = $this->applyPaidScope($query, 'o')->get();

        return $rows->map(static function ($row): array {
            $id = (int) $row->id;

            return [
                'id' => $id,
                'name' => (string) $row->name,
                'amount' => (int) round((float) $row->amount),
                'queue_count' => (int) $row->queue_count,
                'avatar' => 'https://i.pravatar.cc/150?u=ms' . $id,
            ];
        })->all();
    }

    private function buildMasseuseSummaryRows(int $branchId, Carbon $from, Carbon $to): array
    {
        $revenueQuery = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('masseuses as m', 'm.id', '=', 'oi.masseuse_id')
            ->where('o.branch_id', $branchId)
            ->where('oi.item_type', 'service')
            ->whereNotNull('oi.masseuse_id')
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->selectRaw(
                "oi.masseuse_id as id, " .
                "COALESCE(NULLIF(m.nickname, ''), m.full_name, CONCAT('Masseuse #', oi.masseuse_id)) as name, " .
                "SUM(oi.qty * oi.unit_price) as income, " .
                "COUNT(DISTINCT o.id) as queue_count"
            )
            ->groupBy('oi.masseuse_id', 'm.nickname', 'm.full_name');

        $revenues = $this->applyPaidScope($revenueQuery, 'o')
            ->get()
            ->keyBy('id');

        $commissionQuery = DB::table('commissions as c')
            ->join('order_items as oi', 'oi.id', '=', 'c.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branchId)
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to)
            ->selectRaw('c.masseuse_id as id, SUM(c.amount) as commission')
            ->groupBy('c.masseuse_id');

        $commissions = $this->applyPaidScope($commissionQuery, 'o')
            ->get()
            ->keyBy('id');

        $allIds = array_values(array_unique(array_merge(
            array_map('intval', array_keys($revenues->all())),
            array_map('intval', array_keys($commissions->all()))
        )));

        $rows = [];
        foreach ($allIds as $id) {
            $revenue = $revenues->get($id);
            $commission = $commissions->get($id);

            $rows[$id] = [
                'id' => (int) $id,
                'name' => (string) ($revenue->name ?? ('Masseuse #' . $id)),
                'income' => (float) ($revenue->income ?? 0),
                'commission' => (float) ($commission->commission ?? 0),
                'queue_count' => (int) ($revenue->queue_count ?? 0),
            ];
        }

        return $rows;
    }

    private function mergeMasseuseComparisons(array $todayRows, array $yesterdayRows): array
    {
        $allIds = array_values(array_unique(array_merge(array_keys($todayRows), array_keys($yesterdayRows))));
        $merged = [];

        foreach ($allIds as $id) {
            $today = $todayRows[$id] ?? null;
            $yesterday = $yesterdayRows[$id] ?? null;

            $merged[] = [
                'id' => (int) $id,
                'name' => (string) ($today['name'] ?? $yesterday['name'] ?? ('Masseuse #' . $id)),
                'today_income' => (float) ($today['income'] ?? 0),
                'today_commission' => (float) ($today['commission'] ?? 0),
                'today_queue_count' => (int) ($today['queue_count'] ?? 0),
                'yesterday_income' => (float) ($yesterday['income'] ?? 0),
                'yesterday_commission' => (float) ($yesterday['commission'] ?? 0),
                'yesterday_queue_count' => (int) ($yesterday['queue_count'] ?? 0),
            ];
        }

        usort($merged, static function (array $left, array $right): int {
            $leftScore = ((float) $left['today_income']) + ((float) $left['yesterday_income']);
            $rightScore = ((float) $right['today_income']) + ((float) $right['yesterday_income']);

            return $rightScore <=> $leftScore;
        });

        return $merged;
    }

    private function sumCommissions(int $branchId, Carbon $from, ?Carbon $to = null): float
    {
        $query = DB::table('commissions as c')
            ->join('order_items as oi', 'oi.id', '=', 'c.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branchId)
            ->where('o.created_at', '>=', $from);

        if ($to !== null) {
            $query->where('o.created_at', '<', $to);
        }

        $query = $this->applyPaidScope($query, 'o');

        return (float) $query->sum('c.amount');
    }

    private function sumSalesByItemType(int $branchId, Carbon $from, Carbon $to, string $itemType): float
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branchId)
            ->where('oi.item_type', $itemType)
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<', $to);

        if ($itemType === 'service') {
            $query->leftJoin('services as s', 's.id', '=', 'oi.item_id');
            $query = $this->applyPaidScope($query, 'o');
            return (float) $query->sum(DB::raw('oi.qty * CASE WHEN oi.unit_price > 0 THEN oi.unit_price ELSE COALESCE(s.price, 0) END'));
        }

        $query = $this->applyPaidScope($query, 'o');

        return (float) $query->sum(DB::raw('oi.qty * oi.unit_price'));
    }

    private function buildSalesChart(int $branchId): array
    {
        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $rows = $this->baseOrderQuery($branchId)
            ->where('o.created_at', '>=', $startDate)
            ->selectRaw('DATE(o.created_at) as order_date, SUM(o.grand_total) as total')
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $totalByDate = $rows->mapWithKeys(static function ($row): array {
            return [(string) $row->order_date => (float) $row->total];
        });

        $labels = [];
        $data = [];
        for ($days = 6; $days >= 0; $days--) {
            $date = Carbon::today()->subDays($days);
            $dateKey = $date->toDateString();
            $labels[] = $date->format('d/m');
            $data[] = (int) round((float) ($totalByDate[$dateKey] ?? 0));
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}

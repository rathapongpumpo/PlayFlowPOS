<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Services\PointService;

class CrmController extends Controller
{
    private PointService $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $branchId = (int) ($user->branch_id ?? 1);

        // Statistics
        $totalCustomers = DB::table('customers')
            ->where('branch_id', $branchId)
            ->count();

        $activeCustomers = DB::table('customers')
            ->where('branch_id', $branchId)
            ->count();

        $totalPointsIssued = DB::table('point_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'earn')
            ->sum('points');

        $totalPointsRedeemed = DB::table('point_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'redeem')
            ->sum('points');

        // Dynamic Point Settings
        $pointSettings = $this->pointService->getSettings($branchId);

        // Top spenders
        $topSpenders = DB::table('customers as c')
            ->leftJoin('orders as o', 'o.customer_id', '=', 'c.id')
            ->where('c.branch_id', $branchId)
            ->groupBy('c.id', 'c.name', 'c.phone', 'c.total_points')
            ->select([
                'c.id',
                'c.name',
                'c.phone',
                'c.total_points',
                DB::raw('COALESCE(SUM(o.grand_total), 0) as total_spending'),
                DB::raw('COUNT(o.id) as visit_count')
            ])
            ->orderByDesc('total_spending')
            ->limit(10)
            ->get();

        $tierDistribution = collect([]);
        $recentFeedback = [];

        return view('crm.index', compact(
            'totalCustomers',
            'activeCustomers',
            'totalPointsIssued',
            'totalPointsRedeemed',
            'pointSettings',
            'topSpenders',
            'tierDistribution',
            'recentFeedback'
        ));
    }

    public function updatePointSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $branchId = (int) ($user->branch_id ?? 1);

        $validated = $request->validate([
            'earn_rate_thb' => ['required', 'numeric', 'min:1'],
            'redeem_rate_thb' => ['required', 'numeric', 'min:0'],
            'min_spend_thb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->pointService->saveSettings(
            $branchId,
            (float) $validated['earn_rate_thb'],
            (float) $validated['redeem_rate_thb'],
            (float) ($validated['min_spend_thb'] ?? 0)
        );

        return redirect()->route('crm.index')->with('success', 'อัปเดตการตั้งค่ากติกาแต้มสะสมเรียบร้อยแล้ว');
    }
}

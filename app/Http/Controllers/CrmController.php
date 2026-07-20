<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\MembershipTier;

class CrmController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $branchId = $user->branch_id;

        // Statistics
        $totalCustomers = DB::table('customers')
            ->where('branch_id', $branchId)
            ->count();

        $activeCustomers = DB::table('customers')
            ->where('branch_id', $branchId)
            ->count(); // Mock active as total for now if last_visit_at is missing

        $totalPointsIssued = DB::table('point_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'earn')
            ->sum('points'); // using 'points' instead of 'amount'

        $totalPointsRedeemed = DB::table('point_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'redeem')
            ->sum('points');

        // Top spenders
        $topSpenders = collect([]); // Not implemented yet if total_spending column is missing

        // Tier distribution (Not implemented yet)
        $tierDistribution = collect([]);


        // Recent feedback
        $recentFeedback = [];

        return view('crm.index', compact(
            'totalCustomers',
            'activeCustomers',
            'totalPointsIssued',
            'totalPointsRedeemed',
            'topSpenders',
            'tierDistribution',
            'recentFeedback'
        ));
    }
}

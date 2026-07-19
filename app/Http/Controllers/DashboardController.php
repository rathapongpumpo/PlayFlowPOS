<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = request()->user();
        $role = (string) ($user->role ?? '');
        $branchId = request()->has('branch_id') ? (int) request()->query('branch_id') : null;

        if ($role === 'super_admin') {
            return redirect()->route('system.shops.index');
        }



        if ($role === 'masseuse') {
            return redirect()->route('masseuse.self');
        }

        if ($role === 'cashier') {
            return view('dashboard', [
                'stats' => $this->dashboardService->getDashboardStats($user, $branchId, (string) request()->query('range', 'today')),
            ]);
        }

        $range = (string) request()->query('range', 'today');

        return view('dashboard', [
            'stats' => $this->dashboardService->getDashboardStats($user, $branchId, $range),
        ]);
    }
}

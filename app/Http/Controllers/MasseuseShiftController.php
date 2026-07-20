<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BranchContextService;

class MasseuseShiftController extends Controller
{
    private BranchContextService $branchService;

    public function __construct(BranchContextService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index(Request $request)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        
        $shifts = DB::table('staff_shifts')
            ->join('masseuses', 'staff_shifts.masseuse_id', '=', 'masseuses.id')
            ->leftJoin('users', 'masseuses.user_id', '=', 'users.id')
            ->where('staff_shifts.branch_id', $branchId)
            ->whereMonth('staff_shifts.shift_date', $month)
            ->whereYear('staff_shifts.shift_date', $year)
            ->select('staff_shifts.*', 'masseuses.nickname', 'masseuses.full_name', 'users.daily_guarantee')
            ->get();
            
        // คำนวณค่าคอมมิชชั่นรายวัน สำหรับกะงานแต่ละวัน
        foreach ($shifts as $shift) {
            $shift->earned_commission = DB::table('commissions')
                ->where('masseuse_id', $shift->masseuse_id)
                ->where('branch_id', $branchId)
                ->whereDate('calculated_at', $shift->shift_date)
                ->sum('amount');
                
            $guarantee = (float)($shift->daily_guarantee ?? 0);
            $shift->guarantee_topup = max(0, $guarantee - $shift->earned_commission);
        }
            
        $masseuses = DB::table('masseuses')
            ->where('branch_id', $branchId)
            ->get(['id', 'nickname', 'full_name']);
            
        return view('masseuses.shifts', compact('shifts', 'masseuses', 'month', 'year'));
    }

    public function store(Request $request)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        $request->validate([
            'masseuse_id' => 'required|integer',
            'shift_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required'
        ]);

        DB::table('staff_shifts')->insert([
            'branch_id' => $branchId,
            'masseuse_id' => $request->masseuse_id,
            'shift_date' => $request->shift_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return back()->with('success', 'บันทึกตารางงานสำเร็จ');
    }

    public function destroy($shiftId)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        DB::table('staff_shifts')
            ->where('id', $shiftId)
            ->where('branch_id', $branchId)
            ->delete();

        return back()->with('success', 'ลบตารางงานสำเร็จ');
    }
}

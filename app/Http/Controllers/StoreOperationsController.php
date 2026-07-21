<?php

namespace App\Http\Controllers;

use App\Services\BranchContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreOperationsController extends Controller
{
    private BranchContextService $branchService;

    public function __construct(BranchContextService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index(Request $request)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        $today = Carbon::today()->toDateString();
        
        // Find if store is already opened today
        $drawer = DB::table('cash_drawers')
            ->where('branch_id', $branchId)
            ->whereDate('opened_at', $today)
            ->first();

        // Calculate expected amount if it's opened but not closed
        $expectedAmount = 0;
        $salesToday = 0;
        if ($drawer && !$drawer->closed_at) {
            $salesToday = DB::table('orders')
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->where('payment_method', 'cash')
                ->sum('grand_total');
                
            $expectedAmount = $drawer->opening_amount + $salesToday;
        } elseif ($drawer && $drawer->closed_at) {
            $expectedAmount = $drawer->expected_amount;
            $salesToday = $expectedAmount - $drawer->opening_amount;
        }

        return view('operations.index', compact('drawer', 'expectedAmount', 'salesToday', 'today'));
    }

    public function openStore(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        $today = Carbon::today()->toDateString();
        
        $exists = DB::table('cash_drawers')
            ->where('branch_id', $branchId)
            ->whereDate('opened_at', $today)
            ->exists();
            
        if ($exists) {
            return back()->with('error', 'ร้านถูกเปิดไปแล้วสำหรับวันนี้');
        }

        DB::table('cash_drawers')->insert([
            'branch_id' => $branchId,
            'opened_by' => auth()->id(),
            'opening_amount' => $request->opening_amount,
            'note' => $request->note,
            'opened_at' => now()
        ]);

        return back()->with('success', 'เปิดร้านเรียบร้อยแล้ว');
    }

    public function closeStore(Request $request)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'expected_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        $today = Carbon::today()->toDateString();
        
        $drawer = DB::table('cash_drawers')
            ->where('branch_id', $branchId)
            ->whereDate('opened_at', $today)
            ->whereNull('closed_at')
            ->first();
            
        if (!$drawer) {
            return back()->with('error', 'ไม่พบการเปิดร้านสำหรับวันนี้ หรือร้านถูกปิดไปแล้ว');
        }

        $difference = $request->closing_amount - $request->expected_amount;

        DB::table('cash_drawers')
            ->where('id', $drawer->id)
            ->update([
                'closed_by' => auth()->id(),
                'closing_amount' => $request->closing_amount,
                'expected_amount' => $request->expected_amount,
                'difference' => $difference,
                'note' => $request->note ? ($drawer->note ? $drawer->note . "\nปิดลิ้นชัก: " . $request->note : $request->note) : $drawer->note,
                'closed_at' => now()
            ]);

        return back()->with('success', 'ปิดร้านเรียบร้อยแล้ว ยอดต่าง: ' . number_format($difference, 2) . ' บาท');
    }

    public function reopenStore(Request $request)
    {
        $userRole = (string) (auth()->user()->role ?? '');
        if (!in_array($userRole, ['branch_manager', 'shop_owner', 'super_admin'])) {
            return back()->with('error', 'คุณไม่มีสิทธิ์ในการเปิดร้านใหม่อีกครั้ง');
        }

        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        $today = Carbon::today()->toDateString();
        
        $drawer = DB::table('cash_drawers')
            ->where('branch_id', $branchId)
            ->whereDate('opened_at', $today)
            ->whereNotNull('closed_at')
            ->first();
            
        if (!$drawer) {
            return back()->with('error', 'ไม่พบการปิดร้านสำหรับวันนี้');
        }

        $note = $drawer->note ? $drawer->note . "\nเปิดร้านอีกครั้งโดย " . auth()->user()->username . " เมื่อ " . now()->format('Y-m-d H:i:s') : "เปิดร้านอีกครั้งโดย " . auth()->user()->username . " เมื่อ " . now()->format('Y-m-d H:i:s');

        DB::table('cash_drawers')
            ->where('id', $drawer->id)
            ->update([
                'closed_by' => null,
                'closing_amount' => null,
                'expected_amount' => null,
                'difference' => null,
                'note' => $note,
                'closed_at' => null
            ]);

        return back()->with('success', 'เปิดร้านใหม่อีกครั้งเรียบร้อยแล้ว');
    }
}

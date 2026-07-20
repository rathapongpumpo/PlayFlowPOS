<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BranchContextService;

class StoreAssetController extends Controller
{
    private BranchContextService $branchService;

    public function __construct(BranchContextService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index(Request $request)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        
        $assets = DB::table('store_assets')
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();
            
        $transactions = DB::table('store_asset_transactions')
            ->join('store_assets', 'store_asset_transactions.asset_id', '=', 'store_assets.id')
            ->leftJoin('users', 'store_asset_transactions.user_id', '=', 'users.id')
            ->where('store_assets.branch_id', $branchId)
            ->orderBy('store_asset_transactions.created_at', 'desc')
            ->select(
                'store_asset_transactions.*',
                'store_assets.name as asset_name',
                'users.username as user_name'
            )
            ->limit(50)
            ->get();
            
        // Map types for UI
        foreach ($transactions as $tx) {
            if ($tx->type === 'add') {
                $tx->transaction_type = 'in';
            } elseif ($tx->type === 'remove') {
                $tx->transaction_type = 'out';
            } else {
                $tx->transaction_type = 'loss';
            }
            $tx->quantity = $tx->qty;
        }
            
        return view('store_assets.index', compact('assets', 'transactions'));
    }

    public function store(Request $request)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        
        $request->validate([
            'name' => 'required|string|max:100',
            'stock_quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
        ]);
        
        DB::table('store_assets')->insert([
            'branch_id' => $branchId,
            'name' => $request->name,
            'qty' => $request->stock_quantity,
            'unit' => $request->unit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return back()->with('success', 'เพิ่มอุปกรณ์ของใช้สำเร็จ');
    }

    public function adjustStock(Request $request, $id)
    {
        $branchId = $this->branchService->resolveAuthorizedBranchId(auth()->user());
        
        $request->validate([
            'transaction_type' => 'required|in:in,out,loss',
            'quantity' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]);
        
        $asset = DB::table('store_assets')
            ->where('id', $id)
            ->where('branch_id', $branchId)
            ->first();
            
        if (!$asset) {
            return back()->with('error', 'ไม่พบอุปกรณ์ของใช้ในสาขานี้');
        }
        
        $qty = $request->quantity;
        $type = $request->transaction_type;
        
        $newStock = $asset->qty;
        
        $mappedType = 'audit';
        if ($type === 'in') {
            $newStock += $qty;
            $mappedType = 'add';
        } else {
            $newStock -= $qty;
            if ($newStock < 0) $newStock = 0;
            $mappedType = 'remove'; // Or audit for loss, but 'remove' is fine
        }
        
        DB::transaction(function() use ($id, $asset, $mappedType, $qty, $newStock, $request) {
            DB::table('store_assets')->where('id', $id)->update([
                'qty' => $newStock,
                'updated_at' => now()
            ]);
            
            DB::table('store_asset_transactions')->insert([
                'asset_id' => $id,
                'type' => $mappedType,
                'qty' => $qty,
                'balance_after' => $newStock,
                'note' => $request->note,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
        
        return back()->with('success', 'ปรับปรุงสต็อกสำเร็จ จำนวนคงเหลือ: ' . $newStock . ' ' . $asset->unit);
    }
}

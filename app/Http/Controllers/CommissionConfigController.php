<?php

namespace App\Http\Controllers;

use App\Services\BranchContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CommissionConfigController extends Controller
{
    private BranchContextService $branchContext;

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function index(Request $request)
    {
        $branchId = $this->branchContext->resolveAuthorizedBranchId($request->user());

        $configs = DB::table('commission_configs as cc')
            ->select(
                'cc.*',
                DB::raw("CASE
                    WHEN cc.item_type = 'service' THEN (SELECT name FROM services WHERE id = cc.item_id)
                    WHEN cc.item_type = 'product' THEN (SELECT name FROM products WHERE id = cc.item_id)
                    WHEN cc.item_type = 'package' THEN (SELECT name FROM packages WHERE id = cc.item_id)
                END as item_name")
            )
            ->when(Schema::hasColumn('commission_configs', 'branch_id'), function ($query) use ($branchId) {
                $query->where('cc.branch_id', $branchId);
            })
            ->get();

        $availableServices = DB::table('services')
            ->when(Schema::hasColumn('services', 'branch_id'), function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name')
            ->get();

        $availableProducts = DB::table('products')
            ->where('type', 'retail')
            ->when(Schema::hasColumn('products', 'branch_id'), function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name')
            ->get();

        $availablePackages = DB::table('packages')
            ->when(Schema::hasColumn('packages', 'branch_id'), function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name')
            ->get();

        return view('admin.commission.index', compact('configs', 'availableServices', 'availableProducts', 'availablePackages'));
    }

    public function store(Request $request)
    {
        $branchId = $this->branchContext->resolveAuthorizedBranchId($request->user());

        $request->validate([
            'item_type' => 'required|in:service,product,package',
            'item_id' => 'required|integer',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'deduct_cost' => 'nullable|numeric|min:0',
        ]);

        $this->assertItemBelongsToBranch((string) $request->item_type, (int) $request->item_id, $branchId);

        $match = [
            'item_type' => (string) $request->item_type,
            'item_id' => (int) $request->item_id,
        ];

        if (Schema::hasColumn('commission_configs', 'branch_id')) {
            $match['branch_id'] = $branchId;
        }

        DB::table('commission_configs')->updateOrInsert($match, [
            'type' => (string) $request->type,
            'value' => (float) $request->value,
            'deduct_cost' => $request->deduct_cost !== null ? (float) $request->deduct_cost : 0,
        ]);

        return back()->with('success', 'บันทึกการตั้งค่าคอมมิชชันเรียบร้อยแล้ว');
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->branchContext->resolveAuthorizedBranchId($request->user());

        $request->validate([
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'deduct_cost' => 'nullable|numeric|min:0',
        ]);

        $query = DB::table('commission_configs')->where('id', $id);
        
        if (Schema::hasColumn('commission_configs', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $query->update([
            'type' => (string) $request->type,
            'value' => (float) $request->value,
            'deduct_cost' => $request->deduct_cost !== null ? (float) $request->deduct_cost : 0,
        ]);

        return back()->with('success', 'อัปเดตการตั้งค่าคอมมิชชันเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $this->branchContext->resolveAuthorizedBranchId($request->user());

        DB::table('commission_configs')
            ->where('id', $id)
            ->when(Schema::hasColumn('commission_configs', 'branch_id'), function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->delete();

        return back()->with('success', 'ลบรายการค่าคอมมิชชันแล้ว');
    }

    private function assertItemBelongsToBranch(string $itemType, int $itemId, int $branchId): void
    {
        $table = match ($itemType) {
            'service' => 'services',
            'product' => 'products',
            'package' => 'packages',
            default => null,
        };

        if ($table === null) {
            throw ValidationException::withMessages([
                'item_type' => ['ประเภทรายการไม่ถูกต้อง'],
            ]);
        }

        $query = DB::table($table)->where('id', $itemId);
        if (Schema::hasColumn($table, 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'item_id' => ['ไม่พบรายการที่เลือกในสาขานี้'],
        ]);
    }
}

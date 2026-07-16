<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionService
{
    /**
     * คำนวณและบันทึกค่าคอมมิชชันสำหรับ Order ทั้งใบ
     */
    public function processOrderCommissions(int $orderId): void
    {
        // ดึง branch_id ของ Order เพื่อบันทึกลง commissions
        $order = DB::table('orders')->where('id', $orderId)->first(['branch_id']);
        $branchId = $order ? ($order->branch_id ?? null) : null;

        // ดึงรายการใน Order ที่มีการระบุ "หมอนวด" (รองรับทั้ง service, product, package)
        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->whereNotNull('masseuse_id')
            ->get();

        foreach ($items as $item) {
            $this->calculateAndSave($item, $branchId);
        }
    }

    /**
     * คำนวณรายรายการ (Item) และบันทึกลงฐานข้อมูล
     */
    private function calculateAndSave($item, ?int $branchId): void
    {
        // 1. ดึงการตั้งค่าคอมมิชชัน โดยใช้ item_type + item_id
        $configQuery = DB::table('commission_configs')
            ->where('item_type', $item->item_type)
            ->where('item_id', $item->item_id);

        if (Schema::hasColumn('commission_configs', 'branch_id')) {
            if ($branchId !== null) {
                $configQuery->where(function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                })->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId]);
            } else {
                $configQuery->whereNull('branch_id');
            }
        }

        $config = $configQuery->first();

        // ไม่พบ config = รายการนี้ไม่คิดค่าคอม → ข้ามไป
        if (!$config) return;

        $amount = 0;

        // 2. คำนวณตามประเภท (Fixed หรือ Percent)
        if ($config->type === 'fixed') {
            // แบบรายรอบ: (ค่าตอบแทนคงที่) x (จำนวนครั้งที่ขาย)
            $amount = (float) $config->value * (int) $item->qty;
        } 
        else if ($config->type === 'percent') {
            // แบบเปอร์เซ็นต์: หักต้นทุนร้านก่อน (Deduct Cost)
            $unitPrice = (float) $item->unit_price;
            
            // หากราคาเป็น 0 (กรณีมาจากการตัดแพ็กเกจ ซึ่ง unit_price จะโดนปรับเป็น 0 ในหน้า POS)
            // ให้ดึงราคาเต็มจากฐานข้อมูลมาเป็นฐานคำนวณ เพื่อให้พนักงานยังได้ค่ามือ
            if ($unitPrice <= 0 && $item->item_type === 'service') {
                $service = DB::table('services')->where('id', $item->item_id)->first(['price']);
                if ($service) {
                    $unitPrice = (float) $service->price;
                }
            }

            $deductCost = (float) ($config->deduct_cost ?? 0);
            
            // ยอดที่เหลือหลังหักต้นทุน
            $baseForCommission = max(0, $unitPrice - $deductCost);
            
            // คำนวณเปอร์เซ็นต์ และคูณจำนวนรอบ
            $amount = ($baseForCommission * ((float) $config->value / 100)) * (int) $item->qty;
        }

        // 3. บันทึกผลลัพธ์ลงตาราง commissions (ใช้ updateOrInsert เพื่อป้องกันข้อมูลซ้ำ)
        DB::table('commissions')->updateOrInsert(
            ['order_item_id' => $item->id], // เงื่อนไขตรวจสอบ
            [
                'branch_id' => $branchId,
                'masseuse_id' => $item->masseuse_id,
                'amount' => round($amount, 2), // ปัดเศษ 2 ตำแหน่งตามมาตรฐานบัญชี
                'calculated_at' => Carbon::now()
            ]
        );
    }
}

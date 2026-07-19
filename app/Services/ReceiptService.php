<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    private BranchContextService $branchContext;

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getPageData(User $user, ?int $requestedBranchId, ?string $dateFrom, ?string $dateTo): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $branchName = $this->getBranchName($branchId);
        $fromDate = $this->normalizeDate($dateFrom, Carbon::today()->toDateString());
        $toDate = $this->normalizeDate($dateTo, $fromDate);

        if ($fromDate > $toDate) {
            $tmp = $fromDate;
            $fromDate = $toDate;
            $toDate = $tmp;
        }

        $fromDateTime = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay()->toDateTimeString();
        $toDateTime = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay()->toDateTimeString();

        $rows = DB::table('orders as o')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->where('o.branch_id', $branchId)
            ->whereBetween('o.created_at', [$fromDateTime, $toDateTime])
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.created_at',
                'o.payment_method',
                'o.status',
                'o.total_amount',
                'o.discount_amount',
                'o.grand_total',
                'c.name'
            )
            ->orderByDesc('o.created_at')
            ->get([
                'o.id',
                'o.order_no',
                'o.created_at',
                'o.payment_method',
                'o.status',
                'o.total_amount',
                'o.discount_amount',
                'o.grand_total',
                'c.name as customer_name',
                DB::raw('COUNT(oi.id) as item_count'),
            ]);

        $orders = $rows->map(function ($row): array {
            return [
                'id' => (int) $row->id,
                'order_no' => (string) $row->order_no,
                'created_at' => Carbon::parse((string) $row->created_at)->format('Y-m-d H:i'),
                'customer_name' => $row->customer_name !== null ? (string) $row->customer_name : 'Walk-in',
                'payment_method' => (string) $row->payment_method,
                'payment_method_label' => $this->translatePaymentMethod((string) $row->payment_method),
                'status' => (string) $row->status,
                'status_label' => $this->translateOrderStatus((string) $row->status),
                'total_amount' => (float) $row->total_amount,
                'discount_amount' => (float) $row->discount_amount,
                'grand_total' => (float) $row->grand_total,
                'item_count' => (int) $row->item_count,
            ];
        })->all();

        $orderCount = count($orders);
        $salesTotal = array_reduce($orders, static function (float $carry, array $order): float {
            return $carry + (($order['status'] === 'paid') ? (float) $order['grand_total'] : 0.0);
        }, 0.0);
        $avgBill = $orderCount > 0 ? ($salesTotal / $orderCount) : 0.0;

        return [
            'activeBranchId' => $branchId,
            'activeBranchName' => $branchName,
            'dateFrom' => $fromDate,
            'dateTo' => $toDate,
            'orders' => $orders,
            'summary' => [
                'order_count' => $orderCount,
                'sales_total' => $salesTotal,
                'average_bill' => $avgBill,
            ],
        ];
    }

    public function getReceiptDetail(User $user, int $orderId, ?int $requestedBranchId): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);

        $order = DB::table('orders as o')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->where('o.id', $orderId)
            ->where('o.branch_id', $branchId)
            ->first([
                'o.id',
                'o.order_no',
                'o.created_at',
                'o.payment_method',
                'o.status',
                'o.total_amount',
                'o.discount_amount',
                'o.grand_total',
                'o.customer_id',
                'c.name as customer_name',
                'c.phone as customer_phone',
            ]);

        if ($order === null) {
            throw ValidationException::withMessages([
                'receipt' => ['ไม่พบข้อมูลใบเสร็จในสาขานี้'],
            ]);
        }

        $items = DB::table('order_items as oi')
            ->leftJoin('services as s', function ($join): void {
                $join->on('s.id', '=', 'oi.item_id')
                    ->where('oi.item_type', '=', 'service');
            })
            ->leftJoin('products as p', function ($join): void {
                $join->on('p.id', '=', 'oi.item_id')
                    ->where('oi.item_type', '=', 'product');
            })
            ->leftJoin('packages as pkg', function ($join): void {
                $join->on('pkg.id', '=', 'oi.item_id')
                    ->where('oi.item_type', '=', 'package');
            })
            ->leftJoin('masseuses as m', 'm.id', '=', 'oi.masseuse_id')
            ->where('oi.order_id', $orderId)
            ->orderBy('oi.id')
            ->get([
                'oi.id',
                'oi.item_type',
                'oi.item_id',
                'oi.qty',
                'oi.unit_price',
                DB::raw("COALESCE(s.name, p.name, pkg.name, CONCAT('Item #', oi.item_id)) as item_name"),
                DB::raw("COALESCE(NULLIF(m.full_name, ''), NULLIF(m.nickname, ''), '') as staff_name"),
            ])
            ->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'item_type' => (string) $row->item_type,
                    'item_type_label' => $this->translateItemType((string) $row->item_type),
                    'item_id' => (int) $row->item_id,
                    'item_name' => (string) $row->item_name,
                    'qty' => (int) $row->qty,
                    'unit_price' => (float) $row->unit_price,
                    'line_total' => (int) $row->qty * (float) $row->unit_price,
                    'staff_name' => (string) $row->staff_name,
                ];
            })
            ->all();

        return [
            'id' => (int) $order->id,
            'order_no' => (string) $order->order_no,
            'created_at' => Carbon::parse((string) $order->created_at)->format('Y-m-d H:i:s'),
            'customer_name' => $order->customer_name !== null ? (string) $order->customer_name : 'Walk-in',
            'customer_phone' => $order->customer_phone !== null ? (string) $order->customer_phone : '-',
            'payment_method' => (string) $order->payment_method,
            'payment_method_label' => $this->translatePaymentMethod((string) $order->payment_method),
            'status' => (string) $order->status,
            'status_label' => $this->translateOrderStatus((string) $order->status),
            'total_amount' => (float) $order->total_amount,
            'discount_amount' => (float) $order->discount_amount,
            'grand_total' => (float) $order->grand_total,
            'items' => $items,
        ];
    }

    private function resolveAuthorizedBranchId(User $user, ?int $requestedBranchId): int
    {
        return $this->branchContext->resolveAuthorizedBranchId($user, $requestedBranchId);
    }

    private function branchExists(int $branchId): bool
    {
        return DB::table('branches')
            ->where('id', $branchId)
            ->exists();
    }

    private function getDefaultBranchId(): int
    {
        $activeBranch = DB::table('branches')
            ->where('is_active', 1)
            ->orderBy('id')
            ->value('id');

        if ($activeBranch !== null) {
            return (int) $activeBranch;
        }

        $firstBranch = DB::table('branches')
            ->orderBy('id')
            ->value('id');

        if ($firstBranch !== null) {
            return (int) $firstBranch;
        }

        return 1;
    }

    private function getBranchName(int $branchId): string
    {
        $name = DB::table('branches')
            ->where('id', $branchId)
            ->value('name');

        return $name !== null ? (string) $name : 'Unknown Branch';
    }

    private function normalizeDate(?string $date, string $fallback): string
    {
        if ($date === null || trim($date) === '') {
            return $fallback;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function translatePaymentMethod(string $method): string
    {
        if ($method === 'cash') {
            return 'เงินสด';
        }

        if ($method === 'transfer') {
            return 'โอนเงิน';
        }

        if ($method === 'credit_card') {
            return 'บัตรเครดิต';
        }

        if ($method === 'package_redeem') {
            return 'ตัดแพ็กเกจ';
        }

        return $method;
    }

    public function voidOrder(User $user, int $orderId, ?int $requestedBranchId = null): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);

        if (!in_array($user->role, ['shop_owner', 'branch_manager'])) {
            throw ValidationException::withMessages([
                'receipt' => ['ไม่มีสิทธิ์ยกเลิกบิล'],
            ]);
        }

        $order = DB::table('orders')
            ->where('id', $orderId)
            ->where('branch_id', $branchId)
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'receipt' => ['ไม่พบบิลที่ระบุในสาขานี้'],
            ]);
        }

        if ($order->status === 'voided') {
            throw ValidationException::withMessages([
                'receipt' => ['บิลนี้ถูกยกเลิกไปแล้ว'],
            ]);
        }

        DB::transaction(function () use ($orderId, $order): void {
            // คืนโควต้าแพ็กเกจ (กรณีตัดแพ็กเกจ)
            $packageItems = DB::table('order_items')
                ->where('order_id', $orderId)
                ->where('item_type', 'package')
                ->where('unit_price', 0)
                ->get();

            foreach ($packageItems as $item) {
                // คืนโควต้าให้แพ็กเกจล่าสุดของลูกค้านี้
                $latestPackage = DB::table('customer_packages')
                    ->where('customer_id', $order->customer_id)
                    ->where('package_id', $item->item_id)
                    ->orderByDesc('id')
                    ->first();

                if ($latestPackage) {
                    DB::table('customer_packages')
                        ->where('id', $latestPackage->id)
                        ->increment('remaining_qty', $item->qty);
                }
            }

            // ลบการซื้อแพ็กเกจในบิลนี้ (กรณีซื้อแพ็กเกจใหม่)
            $purchasedPackages = DB::table('order_items')
                ->where('order_id', $orderId)
                ->where('item_type', 'package')
                ->where('unit_price', '>', 0)
                ->get();

            foreach ($purchasedPackages as $item) {
                // หาแพ็กเกจที่เพิ่งถูกเพิ่มไปในเวลาที่ใกล้เคียงกัน (ไม่เกิน 5 นาที)
                $recentlyBought = DB::table('customer_packages')
                    ->where('customer_id', $order->customer_id)
                    ->where('package_id', $item->item_id)
                    ->where('bought_at', '>=', \Carbon\Carbon::parse($order->created_at)->subMinutes(5))
                    ->where('bought_at', '<=', \Carbon\Carbon::parse($order->created_at)->addMinutes(5))
                    ->orderByDesc('id')
                    ->limit($item->qty)
                    ->pluck('id');

                if ($recentlyBought->isNotEmpty()) {
                    DB::table('customer_packages')
                        ->whereIn('id', $recentlyBought)
                        ->delete();
                }
            }

            // ลบคอมมิชชัน
            if (\Illuminate\Support\Facades\Schema::hasTable('commissions')) {
                $itemIds = DB::table('order_items')
                    ->where('order_id', $orderId)
                    ->pluck('id');

                if ($itemIds->isNotEmpty()) {
                    DB::table('commissions')
                        ->whereIn('order_item_id', $itemIds->all())
                        ->delete();
                }
            }

            // เปลี่ยนสถานะบิลเป็น voided
            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'status' => 'voided',
                ]);

            // อัปเดตสถานะคิวจองที่เชื่อมโยงกับบิลนี้ให้เป็น cancelled
            if (\Illuminate\Support\Facades\Schema::hasTable('bookings')) {
                DB::table('bookings')
                    ->where('order_id', $orderId)
                    ->update([
                        'status' => 'cancelled',
                        'cancel_reason' => 'ยกเลิกบิล (Void)',
                    ]);
            }
        });

        return ['success' => true];
    }

    private function translateOrderStatus(string $status): string
    {
        if ($status === 'paid') {
            return 'ชำระแล้ว';
        }

        if ($status === 'refunded') {
            return 'คืนเงิน';
        }

        return $status;
    }

    private function translateItemType(string $itemType): string
    {
        if ($itemType === 'service') {
            return 'บริการ';
        }

        if ($itemType === 'product') {
            return 'สินค้า';
        }

        if ($itemType === 'package') {
            return 'แพ็กเกจ';
        }

        return $itemType;
    }
}

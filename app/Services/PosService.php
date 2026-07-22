<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PosService
{
    private BranchContextService $branchContext;
    private BookingService $bookingService;
    private PackageService $packageService;
    private CommissionService $commissionService;
    private WalletService $walletService;
    private PointService $pointService;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct(
        BranchContextService $branchContext,
        BookingService $bookingService,
        PackageService $packageService,
        CommissionService $commissionService,
        WalletService $walletService,
        PointService $pointService
    )
    {
        $this->branchContext = $branchContext;
        $this->bookingService = $bookingService;
        $this->packageService = $packageService;
        $this->commissionService = $commissionService;
        $this->walletService = $walletService;
        $this->pointService = $pointService;
    }

    public function getPageData(User $user, array $query = []): array
    {
        $requestedBranchId = isset($query['branch_id']) ? (int) $query['branch_id'] : null;
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);

        return [
            'activeBranchId' => $branchId,
            'items' => $this->getPosItems($branchId),
            'serviceItems' => $this->getServiceItems($branchId),
            'staff' => $this->getStaff($user, $branchId),
            'sellers' => $this->getSellers($user, $branchId),
            'customers' => $this->getCustomers($branchId),
            'customerPackageBalances' => $this->packageService->getCustomerPackageBalancesMap($branchId),
            'bookingContext' => $this->resolveBookingContext($user, $query, $branchId),
        ];
    }

    public function checkout(User $user, array $payload): array
    {
        $requestedBranchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $customerId = isset($payload['customer_id']) && $payload['customer_id'] !== null ? (int) $payload['customer_id'] : null;
        $staffId = isset($payload['staff_id']) && $payload['staff_id'] !== null ? (int) $payload['staff_id'] : null;
        $sellerId = isset($payload['seller_id']) && $payload['seller_id'] !== null ? (int) $payload['seller_id'] : null;
        $discountAmount = isset($payload['discount_amount']) ? (float) $payload['discount_amount'] : 0.0;
        $tipAmount = isset($payload['tip_amount']) ? (float) $payload['tip_amount'] : 0.0;
        $paymentMethod = $this->normalizePaymentMethod((string) ($payload['payment_method'] ?? 'cash'));
        $usePackage = isset($payload['use_package']) ? (bool) $payload['use_package'] : true; // Default to true for backward compatibility
        $bookingContext = isset($payload['booking_context']) && is_array($payload['booking_context'])
            ? $payload['booking_context']
            : null;

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => ['กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนชำระเงิน'],
            ]);
        }

        $this->assertCustomerInBranch($customerId, $branchId);
        $this->assertStaffAvailableForPos($staffId, $branchId);

        return DB::transaction(function () use (
            $user,
            $branchId,
            $items,
            $staffId,
            $customerId,
            $discountAmount,
            $tipAmount,
            $sellerId,
            $paymentMethod,
            $usePackage,
            $bookingContext
        ): array {
            $normalized = $this->normalizeCartItems($branchId, $items, $staffId, $customerId, $usePackage);
            $normalizedItems = $normalized['items'];
            $discount = max(0.0, $discountAmount);
            $tip = max(0.0, $tipAmount);
            
            $pointsToRedeem = isset($payload['points_redeem']) ? (int)$payload['points_redeem'] : 0;
            $pointsDiscount = $pointsToRedeem > 0 ? $this->pointService->calculateDiscount($pointsToRedeem) : 0.0;
            
            $subtotal = array_reduce($normalizedItems, static function (float $carry, array $item): float {
                return $carry + (float) $item['line_total'];
            }, 0.0);
            
            $totalDiscount = $discount + $pointsDiscount;
            $grandTotal = max(0.0, $subtotal - $totalDiscount) + $tip;

            $finalPaymentMethod = $paymentMethod;
            if ($normalized['has_package_redemption'] && $grandTotal <= 0.0) {
                $finalPaymentMethod = 'package_redeem';
            }

            // Calculate points earned
            $pointsEarned = 0;
            if ($customerId && $grandTotal > 0 && in_array($finalPaymentMethod, ['cash', 'transfer', 'credit_card', 'wallet'])) {
                // Deduct tip from points calculation if needed? Usually tips don't earn points.
                $earnableAmount = max(0.0, $grandTotal - $tip);
                $pointsEarned = $this->pointService->calculatePointsEarned($earnableAmount);
            }

            // Charge wallet if needed
            if ($finalPaymentMethod === 'wallet') {
                if (!$customerId) {
                    throw ValidationException::withMessages(['payment_method' => ['ต้องระบุลูกค้าเมื่อชำระด้วยกระเป๋าเงิน']]);
                }
                $this->walletService->spend($branchId, $customerId, $grandTotal, null, 'ชำระค่าบริการ POS');
            }

            // Redeem points if needed
            if ($customerId && $pointsToRedeem > 0) {
                $this->pointService->redeemPoints($branchId, $customerId, $pointsToRedeem, null, 'ใช้เป็นส่วนลด '.number_format($pointsDiscount, 2).' บาท');
            }

            $orderNo = $this->generateOrderNo($branchId);
            $orderId = (int) DB::table('orders')->insertGetId([
                'branch_id' => $branchId,
                'order_no' => $orderNo,
                'customer_id' => $customerId,
                'seller_id' => $sellerId,
                'total_amount' => $subtotal,
                'discount_amount' => $totalDiscount, // combine discount
                'tip_amount' => $tip,
                'grand_total' => $grandTotal,
                'payment_method' => $finalPaymentMethod,
                'points_earned' => $pointsEarned,
                'points_redeemed' => $pointsToRedeem,
                'status' => 'paid',
                'created_at' => now(),
            ]);

            // Link transactions to order
            if ($finalPaymentMethod === 'wallet') {
                DB::table('wallet_transactions')
                    ->where('customer_id', $customerId)
                    ->whereNull('order_id')
                    ->orderBy('id', 'desc')
                    ->limit(1)
                    ->update(['order_id' => $orderId]);
            }
            if ($pointsToRedeem > 0) {
                DB::table('point_transactions')
                    ->where('customer_id', $customerId)
                    ->whereNull('order_id')
                    ->orderBy('id', 'desc')
                    ->limit(1)
                    ->update(['order_id' => $orderId]);
            }

            // Earn points
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints($branchId, $customerId, $pointsEarned, $orderId, 'ได้รับจากการซื้อบริการ');
            }

            foreach ($normalizedItems as $item) {
                DB::table('order_items')->insert([
                    'branch_id' => $branchId,
                    'order_id' => $orderId,
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'masseuse_id' => $item['masseuse_id'],
                ]);
            }

            // สั่งให้ระบบคำนวณคอมมิชชันทันทีหลังบันทึก Order
            $this->commissionService->processOrderCommissions($orderId);

            $packagePurchaseSummary = $this->grantPurchasedPackages(
                $branchId,
                $customerId,
                $normalized['package_purchase_plan'],
                $orderId
            );
            $packageRedeemSummary = $this->applyPackageRedemptions(
                $branchId,
                $normalized['package_usage_plan'],
                $orderId,
                isset($user->id) ? (int) $user->id : null
            );

            $booking = null;
            if ($bookingContext !== null) {
                if (!empty($bookingContext['is_paid'])) {
                    $isReCheckout = !empty($bookingContext['re_checkout']);
                    $canReCheckout = in_array($user->role ?? '', ['shop_owner', 'branch_manager', 'admin'], true);

                    if (!$isReCheckout || !$canReCheckout) {
                        throw ValidationException::withMessages([
                            'booking_context' => ['คิวนี้ชำระเงินแล้ว ไม่สามารถชำระซ้ำได้'],
                        ]);
                    }

                    // ลบ order เก่าที่ผูกกับ booking นี้ก่อนชำระใหม่
                    if ($this->hasColumn('bookings', 'order_id') && !empty($bookingContext['booking_id'])) {
                        $oldOrderId = DB::table('bookings')
                            ->where('id', (int) $bookingContext['booking_id'])
                            ->value('order_id');
                        if ($oldOrderId) {
                            $this->bookingService->deleteLinkedOrderPublic((int) $oldOrderId);
                        }
                    }
                }

                $requiredBookingFields = ['queue_date', 'start_time', 'end_time'];
                foreach ($requiredBookingFields as $field) {
                    if (!isset($bookingContext[$field]) || $bookingContext[$field] === null || $bookingContext[$field] === '') {
                        throw ValidationException::withMessages([
                            'booking_context' => ['ข้อมูลจองไม่ครบถ้วนสำหรับการชำระเงิน'],
                        ]);
                    }
                }

                $bookingServiceIds = $this->normalizeBookingServiceIds($bookingContext);

                $booking = $this->bookingService->saveBooking(
                    isset($bookingContext['booking_id']) ? (int) $bookingContext['booking_id'] : null,
                    [
                        'branch_id' => $branchId,
                        'queue_date' => (string) $bookingContext['queue_date'],
                        'customer_id' => isset($bookingContext['customer_id']) ? (int) $bookingContext['customer_id'] : $customerId,
                        'service_id' => (int) $bookingServiceIds[0],
                        'service_ids' => $bookingServiceIds,
                        'masseuse_id' => isset($bookingContext['staff_id']) ? (int) $bookingContext['staff_id'] : $this->firstServiceMasseuseId($normalizedItems),
                        'bed_id' => isset($bookingContext['bed_id']) && $bookingContext['bed_id'] !== null ? (int) $bookingContext['bed_id'] : null,
                        'start_time' => (string) $bookingContext['start_time'],
                        'end_time' => (string) $bookingContext['end_time'],
                        'status' => 'completed',
                        'cancel_reason' => null,
                    ],
                    $user
                );

                // เชื่อมโยง booking กับ order เพื่อให้แก้ไข/ลบ booking แล้วยอดใน Dashboard อัปเดตตาม
                if ($booking !== null && isset($booking['id']) && $this->hasColumn('bookings', 'order_id')) {
                    DB::table('bookings')
                        ->where('id', (int) $booking['id'])
                        ->update(['order_id' => $orderId]);
                }
            }

            // --- Stamp Card System: 1 visit = 1 stamp ---
            if ($customerId !== null) {
                DB::table('customers')
                    ->where('id', $customerId)
                    ->increment('total_stamps', 1);

                $currentStamps = DB::table('customers')->where('id', $customerId)->value('total_stamps') ?? 1;

                DB::table('customer_stamps')->insert([
                    'branch_id' => $branchId,
                    'customer_id' => $customerId,
                    'order_id' => $orderId,
                    'type' => 'earn',
                    'stamps' => 1,
                    'balance_after' => $currentStamps,
                    'note' => 'แสตมป์จากการใช้บริการ (บิล: ' . $orderNo . ')',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // --------------------------------------------

            return [
                'message' => 'ชำระเงินสำเร็จ',
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'booking' => $booking,
                'customer_package_balances' => $customerId !== null
                    ? ($this->packageService->getCustomerPackageBalancesMap($branchId)[(string) $customerId] ?? [])
                    : [],
                'packages' => [
                    'purchased' => $packagePurchaseSummary,
                    'redeemed' => $packageRedeemSummary,
                ],
                'membership' => $this->syncCustomerTierAfterPayment($customerId, $branchId),
            ];
        });
    }

    private function getPosItems(int $branchId): array
    {
        return array_merge(
            $this->getServiceItems($branchId),
            $this->getProductItems($branchId),
            $this->packageService->getPackagesForPos($branchId)
        );
    }

    private function getServiceItems(int $branchId): array
    {
        $query = DB::table('services')
            ->where('is_active', 1)
            ->orderBy('id')
            ;

        if ($this->hasColumn('services', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->get(['id', 'name', 'duration_minutes', 'price'])
            ->map(static function ($row): array {
                return [
                    'id' => 'service:' . (string) $row->id,
                    'source_id' => (int) $row->id,
                    'type' => 'service',
                    'name' => (string) $row->name,
                    'price' => (float) $row->price,
                    'duration' => (int) $row->duration_minutes,
                ];
            })
            ->all();
    }

    private function getProductItems(int $branchId): array
    {
        $query = DB::table('products')
            ->where('type', 'retail')
            ->where('is_active', 1)
            ->orderBy('id');

        if ($this->hasColumn('products', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->get(['id', 'name', 'sell_price'])
            ->map(static function ($row): array {
                return [
                    'id' => 'product:' . (string) $row->id,
                    'source_id' => (int) $row->id,
                    'type' => 'product',
                    'name' => (string) $row->name,
                    'price' => (float) ($row->sell_price ?? 0),
                    'duration' => null,
                ];
            })
            ->all();
    }

    private function getStaff(User $user, int $branchId): array
    {
        return $this->bookingService->getStaffRoster(
            $user,
            $branchId,
            Carbon::today()->toDateString(),
            true
        );
    }

    private function getSellers(User $user, int $branchId): array
    {
        $query = DB::table('staff')
            ->where('branch_id', $branchId)
            ->where('is_active', 1)
            ->orderBy('name');

        if ($this->hasColumn('staff', 'position')) {
            $query->where(function ($q) {
                $q->whereNull('position')
                  ->orWhere('position', '!=', 'หมอนวด');
            });
        }

        return $query
            ->get(['id', 'name', 'nickname'])
            ->map(function ($row) {
                $name = trim((string)$row->name);
                if (!empty($row->nickname)) {
                    $name .= ' (' . trim((string)$row->nickname) . ')';
                }
                return [
                    'id' => (string) $row->id,
                    'name' => $name,
                ];
            })
            ->all();
    }

    private function getCustomers(int $branchId): array
    {
        $query = DB::table('customers')
            ->orderBy('name');

        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->get(['id', 'name', 'phone', 'wallet_balance', 'total_points', 'total_stamps'])
            ->map(static function ($row): array {
                return [
                    'id' => (string) $row->id,
                    'name' => (string) $row->name,
                    'phone' => (string) $row->phone,
                    'wallet_balance' => (float) ($row->wallet_balance ?? 0),
                    'total_points' => (int) ($row->total_points ?? 0),
                    'total_stamps' => (int) ($row->total_stamps ?? 0),
                ];
            })
            ->all();
    }

    private function assertCustomerInBranch(?int $customerId, int $branchId): void
    {
        if ($customerId === null || $customerId <= 0) {
            return;
        }

        $query = DB::table('customers')->where('id', $customerId);
        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'customer_id' => ['ไม่พบลูกค้าในสาขานี้'],
        ]);
    }

    private function assertStaffAvailableForPos(?int $staffId, int $branchId): void
    {
        if ($staffId === null || $staffId <= 0) {
            return;
        }

        $masseuse = DB::table('masseuses')
            ->where('id', $staffId)
            ->where('branch_id', $branchId)
            ->first(['id', 'status']);

        if ($masseuse === null) {
            throw ValidationException::withMessages([
                'staff_id' => ['ไม่พบหมอนวดในสาขานี้'],
            ]);
        }

        if ((string) ($masseuse->status ?? '') === 'day_off') {
            throw ValidationException::withMessages([
                'staff_id' => ['หมอนวดคนนี้หยุดงาน ไม่สามารถเลือกได้'],
            ]);
        }

        if ($this->tableExists('staff_attendance')) {
            $attendance = DB::table('staff_attendance')
                ->where('masseuse_id', $staffId)
                ->whereDate('attendance_date', Carbon::today()->toDateString())
                ->value('is_working');

            if ($attendance !== null && !(bool) $attendance) {
                throw ValidationException::withMessages([
                    'staff_id' => ['หมอนวดคนนี้ยังไม่เปิดรับงานวันนี้'],
                ]);
            }
        }
    }

    private function normalizeCartItems(int $branchId, array $items, ?int $staffId, ?int $customerId, bool $usePackage = true): array
    {
        $normalized = [];
        $packageUsagePlan = [];
        $packagePurchasePlan = [];
        
        $availablePackageBalances = [];
        if ($usePackage) {
            $availablePackageBalances = $this->loadCustomerPackageBalancesForRedeem($branchId, $customerId);
        }

        foreach ($items as $index => $item) {
            $type = isset($item['type']) ? (string) $item['type'] : '';
            $itemId = isset($item['source_id']) ? (int) $item['source_id'] : 0;
            $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

            if (!in_array($type, ['service', 'product', 'package'], true)) {
                throw ValidationException::withMessages([
                    'items' => ["ข้อมูลประเภทรายการไม่ถูกต้อง ที่ลำดับ " . ($index + 1)],
                ]);
            }

            if ($itemId <= 0 || $qty <= 0) {
                throw ValidationException::withMessages([
                    'items' => ["ข้อมูลรายการไม่ครบถ้วน ที่ลำดับ " . ($index + 1)],
                ]);
            }

            if ($type === 'service') {
                $service = DB::table('services')
                    ->where('id', $itemId)
                    ->where('is_active', 1)
                    ->where('branch_id', $branchId)
                    ->first(['id', 'name', 'price']);

                if ($service === null) {
                    throw ValidationException::withMessages([
                        'items' => ['ไม่พบบริการที่เลือก หรือบริการถูกปิดใช้งาน'],
                    ]);
                }

                $unitPrice = (float) $service->price;
                $remainingQty = $qty;
                $coveredQty = $this->reservePackageCoverageForService(
                    (string) $service->name,
                    $qty,
                    $availablePackageBalances,
                    $packageUsagePlan
                );
                $remainingQty -= $coveredQty;

                if ($coveredQty > 0) {
                    $normalized[] = [
                        'item_type' => 'service',
                        'item_id' => (int) $service->id,
                        'qty' => $coveredQty,
                        'unit_price' => 0.0,
                        'line_total' => 0.0,
                        'masseuse_id' => $staffId,
                    ];
                }

                if ($remainingQty > 0) {
                    $normalized[] = [
                        'item_type' => 'service',
                        'item_id' => (int) $service->id,
                        'qty' => $remainingQty,
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice * $remainingQty,
                        'masseuse_id' => $staffId,
                    ];
                }
                continue;
            }

            if ($type === 'product') {
                $product = DB::table('products')
                    ->where('id', $itemId)
                    ->where('branch_id', $branchId)
                    ->first(['id', 'sell_price']);

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'items' => ['ไม่พบสินค้าที่เลือก'],
                    ]);
                }

                $unitPrice = (float) ($product->sell_price ?? 0);
                $normalized[] = [
                    'item_type' => 'product',
                    'item_id' => (int) $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $qty,
                    'masseuse_id' => null,
                ];
                continue;
            }

            if ($customerId === null || $customerId <= 0) {
                throw ValidationException::withMessages([
                    'customer_id' => ['กรุณาเลือกลูกค้าก่อนขายแพ็กเกจ'],
                ]);
            }

            $package = DB::table('packages')
                ->where('id', $itemId)
                ->where('branch_id', $branchId)
                ->first(['id', 'price']);

            if ($package === null) {
                throw ValidationException::withMessages([
                    'items' => ['ไม่พบแพ็กเกจที่เลือก'],
                ]);
            }

            $unitPrice = (float) ($package->price ?? 0);
            $normalized[] = [
                'item_type' => 'package',
                'item_id' => (int) $package->id,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $qty,
                'masseuse_id' => null,
            ];

            $packagePurchasePlan[] = [
                'package_id' => (int) $package->id,
                'qty' => $qty,
            ];
        }

        return [
            'items' => $normalized,
            'package_usage_plan' => $packageUsagePlan,
            'package_purchase_plan' => $packagePurchasePlan,
            'has_package_redemption' => !empty($packageUsagePlan),
        ];
    }

    private function loadCustomerPackageBalancesForRedeem(int $branchId, ?int $customerId): array
    {
        if (
            $customerId === null ||
            $customerId <= 0 ||
            !$this->tableExists('customer_packages') ||
            !$this->tableExists('packages')
        ) {
            return [];
        }

        return DB::table('customer_packages as cp')
            ->join('packages as p', 'p.id', '=', 'cp.package_id')
            ->where('cp.branch_id', $branchId)
            ->where('cp.customer_id', $customerId)
            ->where('cp.remaining_qty', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('cp.expired_at')
                    ->orWhere('cp.expired_at', '>=', Carbon::today()->toDateString());
            })
            ->orderBy('cp.bought_at')
            ->orderBy('cp.id')
            ->lockForUpdate()
            ->get([
                'cp.id as customer_package_id',
                'cp.package_id',
                'cp.remaining_qty',
                'p.name as package_name',
            ])
            ->map(static function ($row): array {
                return [
                    'customer_package_id' => (int) ($row->customer_package_id ?? 0),
                    'package_id' => (int) ($row->package_id ?? 0),
                    'remaining_qty' => (int) ($row->remaining_qty ?? 0),
                    'package_name' => (string) ($row->package_name ?? ''),
                ];
            })
            ->all();
    }

    private function reservePackageCoverageForService(
        string $serviceName,
        int $serviceQty,
        array &$availableBalances,
        array &$usagePlan
    ): int {
        if ($serviceQty <= 0 || empty($availableBalances)) {
            return 0;
        }

        $remainingToCover = $serviceQty;
        while ($remainingToCover > 0) {
            $matchIndex = $this->findBestPackageMatchIndex($serviceName, $availableBalances);
            if ($matchIndex === null) {
                break;
            }

            $balance = $availableBalances[$matchIndex];
            if ((int) ($balance['remaining_qty'] ?? 0) <= 0) {
                break;
            }

            $consumeQty = min($remainingToCover, (int) $balance['remaining_qty']);
            if ($consumeQty <= 0) {
                break;
            }

            $availableBalances[$matchIndex]['remaining_qty'] -= $consumeQty;
            $remainingToCover -= $consumeQty;

            $key = (string) $balance['customer_package_id'];
            if (!array_key_exists($key, $usagePlan)) {
                $usagePlan[$key] = [
                    'customer_package_id' => (int) $balance['customer_package_id'],
                    'package_id' => (int) $balance['package_id'],
                    'package_name' => (string) ($balance['package_name'] ?? ''),
                    'qty' => 0,
                ];
            }
            $usagePlan[$key]['qty'] += $consumeQty;
        }

        return $serviceQty - $remainingToCover;
    }

    private function findBestPackageMatchIndex(string $serviceName, array $balances): ?int
    {
        $serviceKey = $this->normalizeMatchKey($serviceName);
        if ($serviceKey === '') {
            return null;
        }

        $bestIndex = null;
        $bestScore = 0;

        foreach ($balances as $index => $balance) {
            $remainingQty = (int) ($balance['remaining_qty'] ?? 0);
            if ($remainingQty <= 0) {
                continue;
            }

            $packageName = (string) ($balance['package_name'] ?? '');
            $packageKey = $this->normalizeMatchKey($packageName);
            $keywordKey = $this->extractPackageKeyword($packageKey);
            $score = $this->calculatePackageMatchScore($serviceKey, $packageKey, $keywordKey);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        if ($bestIndex !== null) {
            return $bestIndex;
        }

        if (count($balances) === 1 && ((int) ($balances[0]['remaining_qty'] ?? 0) > 0)) {
            return 0;
        }

        return null;
    }

    private function calculatePackageMatchScore(string $serviceKey, string $packageKey, string $keywordKey): int
    {
        if ($packageKey === '' || $serviceKey === '') {
            return 0;
        }

        if ($serviceKey === $packageKey) {
            return 100;
        }

        if (str_contains($packageKey, $serviceKey) || str_contains($serviceKey, $packageKey)) {
            return 90;
        }

        if ($keywordKey !== '' && str_contains($serviceKey, $keywordKey)) {
            return 80;
        }

        return 0;
    }

    private function normalizeMatchKey(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $normalized = mb_strtolower($normalized, 'UTF-8');
        } else {
            $normalized = strtolower($normalized);
        }

        return preg_replace('/[^[:alnum:]ก-๙]+/u', '', $normalized) ?? '';
    }

    private function extractPackageKeyword(string $packageKey): string
    {
        $keyword = preg_replace('/[0-9]+/u', '', $packageKey) ?? '';
        $noiseWords = ['ครั้ง', 'คอร์ส', 'แพ็กเกจ', 'แพคเกจ', 'package', 'course', 'ฟรี', 'แถม'];

        foreach ($noiseWords as $noise) {
            $noiseKey = $this->normalizeMatchKey($noise);
            if ($noiseKey !== '') {
                $keyword = str_replace($noiseKey, '', $keyword);
            }
        }

        return trim($keyword);
    }

    private function grantPurchasedPackages(int $branchId, ?int $customerId, array $purchasePlan, ?int $orderId = null): array
    {
        if (empty($purchasePlan)) {
            return [];
        }

        if ($customerId === null || $customerId <= 0 || !$this->tableExists('customer_packages')) {
            return [];
        }

        $summary = [];
        foreach ($purchasePlan as $plan) {
            $packageId = isset($plan['package_id']) ? (int) $plan['package_id'] : 0;
            $qty = isset($plan['qty']) ? (int) $plan['qty'] : 0;
            if ($packageId <= 0 || $qty <= 0) {
                continue;
            }

            $package = DB::table('packages')
                ->where('id', $packageId)
                ->where('branch_id', $branchId)
                ->first(['id', 'name', 'branch_id', 'total_qty', 'valid_days', 'type', 'credit_amount']);

            if ($package === null) {
                continue;
            }

            if (($package->type ?? 'session') === 'wallet_credit') {
                $creditAmount = (float) ($package->credit_amount ?? 0);
                $totalCredit = $creditAmount * $qty;
                if ($totalCredit > 0) {
                    $this->walletService->topUp(
                        $branchId,
                        $customerId,
                        $totalCredit,
                        'ซื้อแพ็กเกจเติมเงิน: ' . ($package->name ?? ''),
                        0,
                        $orderId
                    );
                }
            } else {
                $remainingQty = max(1, (int) ($package->total_qty ?? 1));
                $now = now();
                $expiredAt = null;
                if (($package->valid_days ?? null) !== null && (int) $package->valid_days > 0) {
                    $expiredAt = Carbon::parse($now)->addDays((int) $package->valid_days)->toDateString();
                }

                for ($i = 0; $i < $qty; $i++) {
                    DB::table('customer_packages')->insert([
                        'branch_id' => (int) ($package->branch_id ?? $branchId),
                        'customer_id' => $customerId,
                        'package_id' => (int) $package->id,
                        'remaining_qty' => $remainingQty,
                        'expired_at' => $expiredAt,
                        'bought_at' => $now,
                    ]);
                }
            }

            $summary[] = [
                'package_id' => (int) $package->id,
                'package_name' => (string) ($package->name ?? ''),
                'qty' => $qty,
            ];
        }

        return $summary;
    }

    private function applyPackageRedemptions(int $branchId, array $usagePlan, int $orderId, ?int $actorUserId): array
    {
        if (empty($usagePlan) || !$this->tableExists('customer_packages')) {
            return [];
        }

        $summary = [];
        foreach ($usagePlan as $plan) {
            $customerPackageId = isset($plan['customer_package_id']) ? (int) $plan['customer_package_id'] : 0;
            $packageId = isset($plan['package_id']) ? (int) $plan['package_id'] : 0;
            $qty = isset($plan['qty']) ? (int) $plan['qty'] : 0;
            if ($customerPackageId <= 0 || $packageId <= 0 || $qty <= 0) {
                continue;
            }

            $affectedRows = DB::table('customer_packages')
                ->where('id', $customerPackageId)
                ->where('branch_id', $branchId)
                ->where('remaining_qty', '>=', $qty)
                ->decrement('remaining_qty', $qty);

            if ($affectedRows <= 0) {
                throw ValidationException::withMessages([
                    'package_redeem' => ['ยอดคงเหลือแพ็กเกจไม่เพียงพอ กรุณาตรวจสอบอีกครั้ง'],
                ]);
            }

            DB::table('order_items')->insert([
                'branch_id' => $branchId,
                'order_id' => $orderId,
                'item_type' => 'package',
                'item_id' => $packageId,
                'qty' => $qty,
                'unit_price' => 0,
                'masseuse_id' => $actorUserId,
            ]);

            $summary[] = [
                'package_id' => $packageId,
                'package_name' => (string) ($plan['package_name'] ?? ''),
                'qty' => $qty,
            ];
        }

        return $summary;
    }

    private function resolveBookingContext(User $user, array $query, int $branchId): ?array
    {
        $fromBooking = isset($query['from_booking']) && ((string) $query['from_booking'] === '1' || $query['from_booking'] === 1);
        if (!$fromBooking) {
            return null;
        }

        if (isset($query['booking_id']) && (int) $query['booking_id'] > 0) {
            $context = $this->bookingService->getBookingContextForCheckout(
                $user,
                (int) $query['booking_id'],
                $branchId
            );
            $context['fromBooking'] = true;

            // Override with values from query if provided (to support editing before checkout)
            $serviceIds = $this->normalizeBookingServiceIds($query);
            if (!empty($serviceIds)) {
                $context['serviceIds'] = $serviceIds;
                $context['serviceId'] = $serviceIds[0];
            }
            if (isset($query['customer_id']) && (int) $query['customer_id'] > 0) {
                $context['customerId'] = (int) $query['customer_id'];
            }
            if (isset($query['start_time'])) {
                $context['startTime'] = (string) $query['start_time'];
            }
            if (isset($query['end_time'])) {
                $context['endTime'] = (string) $query['end_time'];
            }
            if (isset($query['staff_id'])) {
                $context['staffId'] = $query['staff_id'] !== '' ? (int) $query['staff_id'] : null;
            }
            if (isset($query['bed_id'])) {
                $context['bedId'] = $query['bed_id'] !== '' ? (int) $query['bed_id'] : null;
            }
            if (isset($query['queue_date'])) {
                $context['queueDate'] = (string) $query['queue_date'];
            }

            // ส่ง re_checkout flag เพื่อให้หน้า POS รู้ว่าเป็น manager/owner ชำระใหม่
            if (isset($query['re_checkout']) && ((string) $query['re_checkout'] === '1' || $query['re_checkout'] === 1)) {
                $context['reCheckout'] = true;
            }
            return $context;
        }

        $queueDate = (string) ($query['queue_date'] ?? Carbon::today()->toDateString());
        $serviceIds = $this->normalizeBookingServiceIds($query);

        if (empty($serviceIds)) {
            return null;
        }

        return [
            'fromBooking' => true,
            'bookingId' => null,
            'queueDate' => $queueDate,
            'startTime' => (string) ($query['start_time'] ?? '10:00'),
            'endTime' => (string) ($query['end_time'] ?? '11:00'),
            'customerId' => isset($query['customer_id']) && (int) $query['customer_id'] > 0 ? (int) $query['customer_id'] : null,
            'staffId' => isset($query['staff_id']) && (int) $query['staff_id'] > 0 ? (int) $query['staff_id'] : null,
            'serviceId' => $serviceIds[0],
            'serviceIds' => $serviceIds,
            'bedId' => isset($query['bed_id']) && (int) $query['bed_id'] > 0 ? (int) $query['bed_id'] : null,
            'isPaid' => false,
        ];
    }

    private function normalizeBookingServiceIds(array $bookingContext): array
    {
        $rawIds = [];
        if (isset($bookingContext['service_ids']) && is_array($bookingContext['service_ids'])) {
            $rawIds = $bookingContext['service_ids'];
        } elseif (isset($bookingContext['service_id'])) {
            $rawIds = [$bookingContext['service_id']];
        }

        $serviceIds = [];
        foreach ($rawIds as $rawId) {
            $serviceId = (int) $rawId;
            if ($serviceId <= 0) {
                continue;
            }

            if (!in_array($serviceId, $serviceIds, true)) {
                $serviceIds[] = $serviceId;
            }
        }

        return array_slice($serviceIds, 0, 3);
    }

    private function normalizePaymentMethod(string $method): string
    {
        if ($method === 'card') {
            return 'credit_card';
        }

        if (in_array($method, ['cash', 'transfer', 'credit_card', 'package_redeem'], true)) {
            return $method;
        }

        return 'cash';
    }

    private function generateOrderNo(int $branchId): string
    {
        return 'PF' . $branchId . Carbon::now()->format('ymdHis') . random_int(10, 99);
    }

    private function firstServiceMasseuseId(array $normalizedItems): ?int
    {
        foreach ($normalizedItems as $item) {
            if ($item['item_type'] === 'service' && $item['masseuse_id'] !== null) {
                return (int) $item['masseuse_id'];
            }
        }

        return null;
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

    private function syncCustomerTierAfterPayment(?int $customerId, int $branchId): ?array
    {
        if ($customerId === null || $customerId <= 0) {
            return null;
        }

        if (
            !$this->tableExists('orders') ||
            !$this->tableExists('customers') ||
            !$this->tableExists('membership_tiers') ||
            !$this->hasColumn('customers', 'tier_id')
        ) {
            return null;
        }

        $tiersQuery = DB::table('membership_tiers')
            ->orderBy('min_spend')
            ->orderBy('id');

        if ($this->hasColumn('membership_tiers', 'branch_id')) {
            $tiersQuery->where('branch_id', $branchId);
        }

        $tiers = $tiersQuery->get(['id', 'name', 'min_spend']);

        if ($tiers->isEmpty()) {
            return null;
        }

        $totalSpentQuery = DB::table('orders')
            ->where('customer_id', $customerId)
            ->where('status', 'paid');

        if ($this->hasColumn('orders', 'branch_id')) {
            $totalSpentQuery->where('branch_id', $branchId);
        }

        $totalSpent = (float) $totalSpentQuery->sum('grand_total');

        $recommendedTier = null;
        foreach ($tiers as $tier) {
            if ($totalSpent >= (float) ($tier->min_spend ?? 0)) {
                $recommendedTier = $tier;
            }
        }

        if ($recommendedTier === null) {
            return null;
        }

        $customerQuery = DB::table('customers')
            ->where('id', $customerId);

        if ($this->hasColumn('customers', 'branch_id')) {
            $customerQuery->where('branch_id', $branchId);
        }

        $customer = $customerQuery->first(['id', 'tier_id']);

        if ($customer === null) {
            return null;
        }

        $currentTierMinSpend = -1.0;
        if ($customer->tier_id !== null) {
            foreach ($tiers as $tier) {
                if ((int) $tier->id === (int) $customer->tier_id) {
                    $currentTierMinSpend = (float) ($tier->min_spend ?? 0);
                    break;
                }
            }
        }

        $recommendedMinSpend = (float) ($recommendedTier->min_spend ?? 0);
        if ($recommendedMinSpend > $currentTierMinSpend) {
            $updates = ['tier_id' => (int) $recommendedTier->id];
            if ($this->hasColumn('customers', 'updated_at')) {
                $updates['updated_at'] = now();
            }

            DB::table('customers')
                ->where('id', $customerId)
                ->when($this->hasColumn('customers', 'branch_id'), function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->update($updates);
        }

        return [
            'tier_id' => (int) $recommendedTier->id,
            'tier_name' => (string) ($recommendedTier->name ?? ''),
            'total_spent' => $totalSpent,
        ];
    }

    private function tableExists(string $table): bool
    {
        if (!array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = Schema::hasTable($table);
        }

        return (bool) $this->tableExistsCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (!array_key_exists($cacheKey, $this->columnExistsCache)) {
            $this->columnExistsCache[$cacheKey] = $this->tableExists($table) && Schema::hasColumn($table, $column);
        }

        return (bool) $this->columnExistsCache[$cacheKey];
    }
}

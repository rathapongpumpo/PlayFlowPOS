<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointService
{
    private BranchContextService $branchContext;
    private float $pointsEarnRate = 100.0; // 100 THB = 1 Point
    private float $pointsRedeemRate = 1.0; // 1 Point = 1 THB discount

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getPoints(int $customerId): int
    {
        $customer = DB::table('customers')->where('id', $customerId)->first();
        return $customer ? (int)$customer->total_points : 0;
    }

    public function calculatePointsEarned(float $spendAmount): int
    {
        if ($spendAmount <= 0) return 0;
        return (int)floor($spendAmount / $this->pointsEarnRate);
    }

    public function calculateDiscount(int $pointsToRedeem): float
    {
        return $pointsToRedeem * $this->pointsRedeemRate;
    }

    public function earnPoints(int $branchId, int $customerId, int $points, ?int $orderId = null, ?string $note = null): array
    {
        if ($points <= 0) return ['success' => true, 'points_earned' => 0];

        return DB::transaction(function () use ($branchId, $customerId, $points, $orderId, $note) {
            $customer = DB::table('customers')->where('id', $customerId)->first();
            if (!$customer) {
                throw ValidationException::withMessages(['customer' => 'ไม่พบข้อมูลลูกค้า']);
            }

            $balanceBefore = (int)$customer->total_points;
            $balanceAfter = $balanceBefore + $points;

            // Update balance
            DB::table('customers')->where('id', $customerId)->update([
                'total_points' => $balanceAfter
            ]);

            // Record transaction
            $txId = DB::table('point_transactions')->insertGetId([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'type' => 'earn',
                'points' => $points,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $orderId,
                'note' => $note,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'transaction_id' => $txId,
                'new_balance' => $balanceAfter
            ];
        });
    }

    public function redeemPoints(int $branchId, int $customerId, int $points, ?int $orderId = null, ?string $note = null): array
    {
        if ($points <= 0) return ['success' => true, 'points_redeemed' => 0];

        return DB::transaction(function () use ($branchId, $customerId, $points, $orderId, $note) {
            $customer = DB::table('customers')->where('id', $customerId)->first();
            if (!$customer) {
                throw ValidationException::withMessages(['customer' => 'ไม่พบข้อมูลลูกค้า']);
            }

            $balanceBefore = (int)$customer->total_points;
            
            if ($balanceBefore < $points) {
                throw ValidationException::withMessages(['points' => 'คะแนนสะสมไม่เพียงพอ']);
            }

            $balanceAfter = $balanceBefore - $points;

            // Update balance
            DB::table('customers')->where('id', $customerId)->update([
                'total_points' => $balanceAfter
            ]);

            // Record transaction
            $txId = DB::table('point_transactions')->insertGetId([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'type' => 'redeem',
                'points' => $points,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $orderId,
                'note' => $note,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'transaction_id' => $txId,
                'new_balance' => $balanceAfter
            ];
        });
    }

    public function getTransactionHistory(int $customerId, int $limit = 50): array
    {
        return collect(DB::table('point_transactions')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get())->map(function($tx) {
                return (array)$tx;
            })->toArray();
    }
}

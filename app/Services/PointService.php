<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointService
{
    private BranchContextService $branchContext;

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getSettings(?int $branchId = null): object
    {
        if ($branchId === null) {
            $branchId = $this->branchContext->getCurrentBranchId() ?? 0;
        }

        $setting = null;
        if (DB::getSchemaBuilder()->hasTable('point_settings')) {
            $setting = DB::table('point_settings')
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if (!$setting) {
                // Fallback to global setting (branch_id = 0)
                $setting = DB::table('point_settings')
                    ->where('branch_id', 0)
                    ->first();
            }
        }

        if (!$setting) {
            return (object) [
                'branch_id' => $branchId,
                'earn_rate_thb' => 100.00,
                'redeem_rate_thb' => 1.00,
                'min_spend_thb' => 0.00,
                'is_active' => true,
            ];
        }

        return $setting;
    }

    public function saveSettings(int $branchId, float $earnRateThb, float $redeemRateThb = 1.0, float $minSpendThb = 0.0): bool
    {
        if (!DB::getSchemaBuilder()->hasTable('point_settings')) {
            return false;
        }

        $existing = DB::table('point_settings')->where('branch_id', $branchId)->first();

        if ($existing) {
            DB::table('point_settings')
                ->where('branch_id', $branchId)
                ->update([
                    'earn_rate_thb' => max(1, $earnRateThb),
                    'redeem_rate_thb' => max(0, $redeemRateThb),
                    'min_spend_thb' => max(0, $minSpendThb),
                    'updated_at' => Carbon::now(),
                ]);
        } else {
            DB::table('point_settings')->insert([
                'branch_id' => $branchId,
                'earn_rate_thb' => max(1, $earnRateThb),
                'redeem_rate_thb' => max(0, $redeemRateThb),
                'min_spend_thb' => max(0, $minSpendThb),
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return true;
    }

    public function getPoints(int $customerId): int
    {
        $customer = DB::table('customers')->where('id', $customerId)->first();
        return $customer ? (int)$customer->total_points : 0;
    }

    public function calculatePointsEarned(float $spendAmount, ?int $branchId = null): int
    {
        if ($spendAmount <= 0) return 0;

        $settings = $this->getSettings($branchId);
        if ($spendAmount < (float)$settings->min_spend_thb) {
            return 0;
        }

        $earnRate = (float)$settings->earn_rate_thb;
        if ($earnRate <= 0) $earnRate = 100.0;

        return (int)floor($spendAmount / $earnRate);
    }

    public function calculateDiscount(int $pointsToRedeem, ?int $branchId = null): float
    {
        if ($pointsToRedeem <= 0) return 0.0;

        $settings = $this->getSettings($branchId);
        $redeemRate = (float)$settings->redeem_rate_thb;

        return $pointsToRedeem * $redeemRate;
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

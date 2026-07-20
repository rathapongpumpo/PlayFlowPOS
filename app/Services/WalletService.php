<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    private BranchContextService $branchContext;

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getWalletBalance(int $customerId): float
    {
        $customer = DB::table('customers')->where('id', $customerId)->first();
        return $customer ? (float)$customer->wallet_balance : 0.00;
    }

    public function topUp(int $branchId, int $customerId, float $amount, ?string $note = null, float $bonus = 0, ?int $orderId = null): array
    {
        if ($amount <= 0 && $bonus <= 0) {
            throw ValidationException::withMessages(['amount' => 'ยอดรวมต้องมากกว่า 0']);
        }

        return DB::transaction(function () use ($branchId, $customerId, $amount, $bonus, $note, $orderId) {
            $customer = DB::table('customers')->where('id', $customerId)->first();
            if (!$customer) {
                throw ValidationException::withMessages(['customer' => 'ไม่พบข้อมูลลูกค้า']);
            }

            $totalAmount = $amount + $bonus;
            $balanceBefore = (float)$customer->wallet_balance;
            $balanceAfter = $balanceBefore + $totalAmount;

            // Update balance
            DB::table('customers')->where('id', $customerId)->update([
                'wallet_balance' => $balanceAfter
            ]);

            // Record transaction
            $txId = DB::table('wallet_transactions')->insertGetId([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'type' => 'topup',
                'amount' => $totalAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $orderId,
                'note' => $bonus > 0 ? trim($note . " (Bonus: {$bonus})") : $note,
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

    public function spend(int $branchId, int $customerId, float $amount, ?int $orderId = null, ?string $note = null): array
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'จำนวนเงินต้องมากกว่า 0']);
        }

        return DB::transaction(function () use ($branchId, $customerId, $amount, $orderId, $note) {
            $customer = DB::table('customers')->where('id', $customerId)->first();
            if (!$customer) {
                throw ValidationException::withMessages(['customer' => 'ไม่พบข้อมูลลูกค้า']);
            }

            $balanceBefore = (float)$customer->wallet_balance;
            
            if ($balanceBefore < $amount) {
                throw ValidationException::withMessages(['wallet' => 'ยอดเงินในกระเป๋าไม่เพียงพอ']);
            }

            $balanceAfter = $balanceBefore - $amount;

            // Update balance
            DB::table('customers')->where('id', $customerId)->update([
                'wallet_balance' => $balanceAfter
            ]);

            // Record transaction
            $txId = DB::table('wallet_transactions')->insertGetId([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'type' => 'spend',
                'amount' => $amount,
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
        return collect(DB::table('wallet_transactions')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get())->map(function($tx) {
                return (array)$tx;
            })->toArray();
    }
}

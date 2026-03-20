<?php

namespace App\Services\Finance;

use App\Models\Admin\LedgerEntry;
use App\Models\Admin\Wallet;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Str;

class WalletService
{
    public static function deposit($userId, $amount, $reference = null, $description = null, $entryType = 'CONTRIBUTION_DEPOSIT')
    {
        $wallet = Wallet::where('user_id', $userId)
            ->lockForUpdate()->firstOrFail();

        if ($entryType === 'CONTRIBUTION_DEPOSIT') {
            $balanceBefore = $wallet->total_contributions;
            $wallet->total_contributions += $amount;
            $balanceAfter = $wallet->total_contributions;
        } elseif ($entryType === 'SAVINGS_DEPOSIT') {
            $balanceBefore = $wallet->total_saving_amount;
            $wallet->total_saving_amount += $amount;
            $balanceAfter = $wallet->total_saving_amount;
            $wallet->locked_balance += $amount;
        } elseif ($entryType === 'TARGET_DEPOSIT') {
            $balanceBefore = $wallet->total_target_amount;
            $wallet->total_target_amount += $amount;
            $balanceAfter = $wallet->total_target_amount;
        } else {
            throw new Exception('Invalid deposit type');
        }

        $wallet->save();

        $ledgerEntry = LedgerEntry::create([
            'user_id' => $userId,
            'wallet_id' => $wallet->wallet_id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? Str::uuid(),
            'description' => $description,
            'transaction_type' => 'CREDIT',
            'created_by' => Auth::guard('admin')->user()->staff_id ?? $userId,
        ]);

        return $ledgerEntry;
    }

    public static function withdraw($userId, $amount, $reference = null, $description = null, $entryType = 'CONTRIBUTION_WITHDRAWAL')
    {
        $wallet = Wallet::where('user_id', $userId)
            ->lockForUpdate()->firstOrFail();

        if ($entryType === 'CONTRIBUTION_WITHDRAWAL') {
            if ($wallet->total_contributions < $amount) {
                throw new Exception('Insufficient contribution balance');
            }
            $balanceBefore = $wallet->total_contributions;
            $wallet->total_contributions -= $amount;
            $balanceAfter = $wallet->total_contributions;
        } elseif ($entryType === 'SAVINGS_WITHDRAWAL') {
            $totalSavings = $wallet->total_saving_amount;
            $totalLocked = $wallet->locked_balance;
            $totalAvailable = $totalSavings - $totalLocked;
            if ($totalAvailable < $amount) {
                throw new Exception('Insufficient savings balance');
            }
            $balanceBefore = $wallet->total_saving_amount;
            $wallet->total_saving_amount -= $amount;
            $balanceAfter = $wallet->total_saving_amount;
        } elseif ($entryType === 'LOCKED_WITHDRAWAL') {
            if ($wallet->locked_balance < $amount) {
                throw new Exception('Insufficient locked balance');
            }
            $balanceBefore = $wallet->locked_balance;
            $wallet->locked_balance -= $amount;
            $balanceAfter = $wallet->locked_balance;
        } elseif ($entryType === 'TARGET_WITHDRAWAL') {
            if ($wallet->total_target_amount < $amount) {
                throw new Exception('Insufficient target balance');
            }
            $balanceBefore = $wallet->total_target_amount;
            $wallet->total_target_amount -= $amount;
            $balanceAfter = $wallet->total_target_amount;
        } else {
            throw new Exception('Invalid withdrawal type');
        }

        $wallet->save();

        $ledgerEntry = LedgerEntry::create([
            'user_id' => $userId,
            'wallet_id' => $wallet->wallet_id,
            'entry_type' => $entryType,
            'amount' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? Str::uuid(),
            'description' => $description,
            'transaction_type' => 'DEBIT',
            'created_by' => Auth::guard('admin')->user()->staff_id ?? $userId,
        ]);

        return $ledgerEntry;
    }
}

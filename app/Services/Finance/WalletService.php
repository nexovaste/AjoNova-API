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
        } elseif ($entryType === 'TARGET_DEPOSIT') {
            $balanceBefore = $wallet->total_target_amount;
            $wallet->total_target_amount += $amount;
            $balanceAfter = $wallet->total_target_amount;
        } else {
            throw new Exception('Invalid deposit type');
        }

        $wallet->save();

        LedgerEntry::create([
            'user_id' => $userId,
            'wallet_id' => $wallet->wallet_id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference ?? Str::uuid(),
            'description' => $description,
            'created_by' => Auth::guard('admin')->user()->staff_id,
        ]);
    }
}

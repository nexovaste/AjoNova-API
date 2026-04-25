<?php

namespace App\Services\Finance;

use App\Models\Admin\LedgerEntry;
use App\Models\Admin\Wallet;
use App\Models\Admin\WithdrawalRequest;
use App\Models\User\User;
use Exception;
use Illuminate\Support\Facades\Auth;
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

    public static function withdraw($userId, $amount, $entryType = 'CONTRIBUTION_WITHDRAWAL')
    {
        $wallet = Wallet::where('user_id', $userId)
            ->lockForUpdate()->firstOrFail();

        if ($entryType === 'CONTRIBUTION_WITHDRAWAL') {
            if ($wallet->total_contributions < $amount) {
                throw new Exception('Insufficient contribution balance');
            }
            $wallet->total_contributions -= $amount;
        } elseif ($entryType === 'SAVINGS_WITHDRAWAL') {
            $totalSavings = $wallet->total_saving_amount;
            $totalLocked = $wallet->locked_balance;
            $totalAvailable = $totalSavings - $totalLocked;
            if ($totalAvailable < $amount) {
                throw new Exception('Insufficient savings balance');
            }
            $wallet->total_saving_amount -= $amount;
        } elseif ($entryType === 'LOCKED_WITHDRAWAL') {
            if ($wallet->locked_balance < $amount || $wallet->locked_balance < 1) {
                throw new Exception('Insufficient locked balance');
            }
            $wallet->locked_balance -= $amount;
        } elseif ($entryType === 'TARGET_WITHDRAWAL') {
            if ($wallet->total_target_amount < $amount) {
                throw new Exception('Insufficient target balance');
            }
            $wallet->total_target_amount -= $amount;
        } else {
            throw new Exception('Invalid withdrawal type');
        }

        $wallet->save();
    }

    public static function approveWithdrawal($id, $statusId, $reason = null, $description = null, $reference = null)
    {
        $userInfo = WithdrawalRequest::findOrFail($id);
        $wallet = Wallet::where('user_id', $userInfo->user_id)->firstOrFail();
        $entryType = strtoupper($userInfo->withdrawal_type);

        if ($userInfo->status_id == 6 || $userInfo->status_id == 8) {
            throw new Exception('This transaction has already been finalized');
        }

        if ($statusId === 6) {
            $userInfo->update([
                'status_id' => 6,
                'attended_at' => now(),
                'attended_by' => Auth::guard('admin')->user()->staff_id,
                'is_approved' => true
            ]);

            if ($entryType === 'CONTRIBUTION_WITHDRAWAL') {
                $balanceBefore = $wallet->total_contributions + $userInfo->amount;
                $balanceAfter = $wallet->total_contributions;
            } elseif ($entryType === 'SAVINGS_WITHDRAWAL') {
                $balanceBefore = $wallet->total_saving_amount + $userInfo->amount;
                $balanceAfter = $wallet->total_saving_amount;
            } elseif ($entryType === 'LOCKED_WITHDRAWAL') {
                $balanceBefore = $wallet->locked_balance + $userInfo->amount;
                $balanceAfter = $wallet->locked_balance;
            } elseif ($entryType === 'TARGET_WITHDRAWAL') {
                $balanceBefore = $wallet->total_target_amount + $userInfo->amount;
                $balanceAfter = $wallet->total_target_amount;
            }

            LedgerEntry::create([
                'user_id' => $userInfo->user_id,
                'wallet_id' => $wallet->wallet_id,
                'entry_type' => $entryType,
                'amount' => $userInfo->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $reference ?? Str::uuid(),
                'description' => $description,
                'transaction_type' => 'DEBIT',
                'created_by' => Auth::guard('admin')->user()->staff_id,
            ]);
        } elseif ($statusId === 8) {
            $userInfo->update([
                'status_id' => 8,
                'attended_at' => now(),
                'attended_by' => Auth::guard('admin')->user()->staff_id,
                'reason' => $reason,
            ]);

            if ($entryType === 'CONTRIBUTION_WITHDRAWAL') {
                $wallet->total_contributions += $userInfo->amount;
            } elseif ($entryType === 'SAVINGS_WITHDRAWAL') {
                $wallet->total_saving_amount += $userInfo->amount;
            } elseif ($entryType === 'LOCKED_WITHDRAWAL') {
                $wallet->locked_balance += $userInfo->amount;
                $user = User::whereKey($userInfo->user_id)->first();
                if ($user) {$user->status_id = 3;
                    $user->save();
                }
            } elseif ($entryType === 'TARGET_WITHDRAWAL') {
                $wallet->total_target_amount += $userInfo->amount;
            }

            $wallet->save();
        }
    }
}

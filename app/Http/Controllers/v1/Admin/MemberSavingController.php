<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MemberContributionSaving;
use App\Models\Admin\MemberSaving;
use App\Models\Admin\WithdrawalRequest;
use App\Models\User\User;
use App\Services\Finance\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class MemberSavingController extends Controller
{
    // Display a listing of the resource.
    public function fetchAllContributions()
    {
        //
    }

    // Store a newly created resource in storage.
    public function depositSavings(Request $request)
    {
        try {

            return DB::transaction(function () use ($request) {

                $userId = $request->header('X-User-ID');

                $savingAmount = MemberContributionSaving::where('user_id', $userId)
                    ->value('saving_amount');

                $user = User::where('user_id', $userId)->first();

                $ledgerEntry =  WalletService::deposit(
                    $userId,
                    $savingAmount,
                    null,
                    'Monthly savings deposit for user ' . $user->first_name . ' ' . $user->last_name . ' for ' . now()->format('F Y'),
                    'SAVINGS_DEPOSIT'
                );

                MemberSaving::create([
                    'user_id' => $userId,
                    'saving_amount' => $savingAmount,
                    'saving_date' => now(),
                    'status_id' => 21,
                    'ledger_entry_id' => $ledgerEntry->ledger_entry_id,
                    'reference' => $ledgerEntry->reference,
                    'processed_by' => $ledgerEntry->created_by,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Savings processed successfully'
                ], 201);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You have already made a savings for this month. Payment cannot be made twice.'
            ], 409);
        }
    }

    // Display the specified resource.
    public function fetchSingleSvings(string $id)
    {
        //
    }

    public function withdrawSavings(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $userId = $request->header('X-User-ID');

                WalletService::withdraw(
                    $userId,
                    $request->amount,
                    'LOCKED_WITHDRAWAL',
                );

                WithdrawalRequest::create([
                    'user_id' => $userId,
                    'withdrawal_type' => 'LOCKED_WITHDRAWAL',
                    'amount' => $request->amount,
                    'withdraw_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Savings withdrawn successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function approveWithdrawal(Request $request, string $id)
    {
        $request->validate([
            'statusId' => 'required|integer|exists:setup_statuses,status_id|in:6,8',
            'reason' => 'required_if:statusId,8|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {
                $userId = $request->header('X-User-ID');
                $user = User::where('user_id', $userId)->first();

                WalletService::approveWithdrawal(
                    $id,
                    $request->statusId,
                    $request->reason,
                    'Withdrawal request '  . ' by ' . $user->first_name . ' ' . $user->last_name . ' has been approved ',
                    null
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Withdrawal request processed successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\MemberTargetSavingResource;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\MemberTargetSavingSetting;
use App\Models\Admin\WithdrawalRequest;
use App\Models\User\User;
use App\Services\Finance\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberTargetSavingController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $cursor = $request->query('cursor');
            $cacheKey = "member_target_saving_list_" . ($cursor ?? 'first_page');
            $memberSaving = Cache::tags('member_target_saving_list_')->flexible($cacheKey,[now()->addMonth(), null],function () use ($cursor) {
                    return MemberTargetSaving::with([
                        'status:status_id,status_name',
                        'ledger:ledger_entry_id,entry_type',
                        'paymentChannel:payment_channel_type_id,payment_channel_type_name',
                        'setting:member_target_saving_setting_id,target_name,target_amount,monthly_amount,duration_months,start_date,end_date'
                    ])->cursorPaginate(30, ['*'], 'cursor', $cursor);
                }
            );

            if ($memberSaving->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No member target savings records found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member Target Saving records fetched successfully.',
                'data' => MemberTargetSavingResource::collection($memberSaving),
                'pagination' => [
                    'next_cursor' => $memberSaving->nextCursor()?->encode(),
                    'previous_cursor' => $memberSaving->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Store a newly created resource in storage.
    public function depositTargetSavings(Request $request)
    {
        try {

            return DB::transaction(function () use ($request) {

                $userId = $request->header('X-User-ID');

                $targetSettings = MemberTargetSavingSetting::where('user_id', $userId)->first();
                $user = User::where('user_id', $userId)->first();

                $ledgerEntry = WalletService::deposit(
                    $userId,
                    $targetSettings->monthly_amount,
                    null,
                    'Monthly target deposit for user ' . $user->first_name . ' ' . $user->last_name . ' for ' . now()->format('F Y'),
                    'TARGET_DEPOSIT'
                );

                $startDate = Carbon::parse($targetSettings->start_date);
                $endDate = Carbon::parse($targetSettings->end_date);
                $now = now();

                if ($now->lt($startDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. Your target saving start date is ' . $targetSettings->start_date->format('Y-m-d') . '. Please wait until the start date to make your first target saving.'
                    ], 400);
                } elseif ($now->gt($endDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. Your target saving end date is ' . $targetSettings->end_date->format('Y-m-d') . '. Your target saving period has ended.'
                    ], 400);
                } else {
                    MemberTargetSaving::create([
                        'user_id' => $userId,
                        'member_target_saving_setting_id' => $targetSettings->member_target_saving_setting_id,
                        'target_amount' => $targetSettings->monthly_amount,
                        'monthly_amount' => $targetSettings->monthly_amount,
                        'current_amount' => $targetSettings->current_amount + $ledgerEntry->amount,
                        'saving_date' => now(),
                        'status_id' => 21,
                        'ledger_entry_id' => $ledgerEntry->ledger_entry_id,
                        'reference' => $ledgerEntry->reference,
                        'processed_by' => $ledgerEntry->created_by,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Target saving processed successfully'
                ], 201);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You have already made a target saving for this month. Payment cannot be made twice.'
            ], 409);
        }
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
                    'TARGET_WITHDRAWAL',
                );

                WithdrawalRequest::create([
                    'user_id' => $userId,
                    'withdrawal_type' => 'TARGET_WITHDRAWAL',
                    'amount' => $request->amount,
                    'withdraw_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Target Savings withdrawn successfully'
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
                    null,
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

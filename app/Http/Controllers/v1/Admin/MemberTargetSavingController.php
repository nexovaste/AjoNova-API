<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\MemberTargetSavingResource;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\MemberTargetSavingSetting;
use App\Models\Admin\WithdrawalRequest;
use App\Models\User\User;
use App\Services\Cache\ClearCacheService;
use App\Services\Finance\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberTargetSavingController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $userId = Auth::guard('user')->user()?->user_id ?? $request->header('X-User-ID') ?? $request->query('user_id');
            $cursor = $request->query('cursor');
            $cacheKey = "member_target_saving_list_" . ($userId ?? 'all') . "_" . ($cursor ?? 'first_page');
            $memberSaving = Cache::tags('member_target_saving_list_' . ($userId ?? 'all'))->flexible($cacheKey, [now()->addMonth(), null], function () use ($cursor, $userId) {
                $query = MemberTargetSaving::with([
                    'status:status_id,status_name',
                    'ledger:ledger_entry_id,entry_type',
                    'paymentChannel:payment_channel_type_id,payment_channel_type_name',
                    'setting:member_target_saving_setting_id,target_name,target_amount,monthly_amount,duration_months,start_date,end_date'
                ]);
                if ($userId) {
                    $query->where('user_id', $userId);
                }
                return $query->latest('member_target_saving_id')->cursorPaginate(30, ['*'], 'cursor', $cursor);
            });

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
                if (!$targetSettings) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. This member has not set up or configured a target savings plan yet.'
                    ], 400);
                }

                $user = User::where('user_id', $userId)->first();
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User record not found.'
                    ], 404);
                }

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
                        'message' => 'Target saving cannot be processed. Target saving start date is ' . $startDate->format('Y-m-d') . '. Please wait until the start date to make a target saving.'
                    ], 400);
                } elseif ($now->gt($endDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target saving cannot be processed. Target saving end date was ' . $endDate->format('Y-m-d') . '. Target saving period has ended.'
                    ], 400);
                } else {
                    MemberTargetSaving::create([
                        'user_id' => $userId,
                        'member_target_saving_setting_id' => $targetSettings->member_target_saving_setting_id,
                        'target_amount' => $targetSettings->monthly_amount,
                        'monthly_amount' => $targetSettings->monthly_amount ?: null,
                        'current_amount' => $targetSettings->current_amount + $ledgerEntry->amount,
                        'saving_date' => now(),
                        'status_id' => 21,
                        'ledger_entry_id' => $ledgerEntry->ledger_entry_id,
                        'reference' => $ledgerEntry->reference,
                        'processed_by' => $ledgerEntry->created_by,
                    ]);
                }

                 Cache::tags('member_target_saving_list_' . $userId)->flush();

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
            'reason' => 'required|string|min:3',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $userId = Auth::guard('user')->user()?->user_id ?? $request->header('X-User-ID');

                WalletService::withdraw(
                    $userId,
                    $request->amount,
                    'TARGET_WITHDRAWAL',
                );

                WithdrawalRequest::create([
                    'user_id' => $userId,
                    'withdrawal_type' => 'TARGET_WITHDRAWAL',
                    'amount' => $request->amount,
                    'reason' => trim($request->reason),
                    'withdraw_at' => now(),
                ]);

                ClearCacheService::clearListCache('withdrawal_request_list');

                return response()->json([
                    'success' => true,
                    'message' => 'Target savings withdrawn successfully'
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
                $withdrawalRequest = \App\Models\Admin\WithdrawalRequest::find($id);
                $user = $withdrawalRequest ? User::where('user_id', $withdrawalRequest->user_id)->first() : null;
                $userName = $user ? ($user->first_name . ' ' . $user->last_name) : 'Member';

                WalletService::approveWithdrawal(
                    $id,
                    $request->statusId,
                    $request->reason,
                    'Withdrawal request by ' . $userName . ' has been processed',
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

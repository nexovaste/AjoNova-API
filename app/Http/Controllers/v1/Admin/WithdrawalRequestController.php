<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WithdrawalRequestResource;
use App\Models\Admin\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class WithdrawalRequestController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $cursor = $request->query('cursor');
            $cacheKey = "withdrawal_request_list_" . ($cursor ?? 'first_page');
            $withdrawalRequestData = Cache::tags('withdrawal_request_list')->flexible(
                $cacheKey,
                [now()->addMonth(), null],
                function () use ($cursor) {
                    return WithdrawalRequest::with([
                        'status:status_id,status_name',
                        'user:user_id,title_id,first_name,middle_name,last_name,passport',
                        'user.title:title_id,title_name',
                        'attendedByStaff:staff_id,first_name,last_name'
                    ])->cursorPaginate(30, ['*'], 'cursor', $cursor);
                }
            );

            $pendingLoansCount = \App\Models\Admin\Loan::where('status_id', 5)->count();
            $pendingWithdrawalsCount = WithdrawalRequest::where('status_id', 5)->count();
            $summary = [
                'pending_loans_count' => $pendingLoansCount,
                'pending_withdrawals_count' => $pendingWithdrawalsCount,
                'total_pending_count' => $pendingLoansCount + $pendingWithdrawalsCount,
            ];

            if ($withdrawalRequestData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No withdrawal requests found.',
                    'summary' => $summary,
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal requests fetched successfully.',
                'summary' => $summary,
                'data' => WithdrawalRequestResource::collection($withdrawalRequestData),
                'pagination' => [
                    'next_cursor' => $withdrawalRequestData->nextCursor()?->encode(),
                    'previous_cursor' => $withdrawalRequestData->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    // Display the specified resource.
    public function show(string $id)
    {

        try {
            $withdrawalRequest = Cache::remember("withdrawal_request_{$id}", now()->addMonth(), function () use ($id) {
                return new WithdrawalRequestResource(WithdrawalRequest::with([
                    'status:status_id,status_name',
                    'user:user_id,title_id,first_name,middle_name,last_name,passport',
                    'user.title:title_id,title_name',
                    'attendedByStaff:staff_id,first_name,last_name'
                ])->findOrFail($id));
            });

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request fetched successfully.',
                'data' => $withdrawalRequest
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve withdrawal request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveWithdrawal(Request $request, string $id)
    {
        $request->validate([
            'statusId' => 'required|integer|exists:setup_statuses,status_id|in:6,8',
            'reason' => 'required_if:statusId,8|string',
        ]);

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
                $withdrawalRequest = WithdrawalRequest::findOrFail($id);
                $user = \App\Models\User\User::where('user_id', $withdrawalRequest->user_id)->first();
                $userName = $user ? ($user->first_name . ' ' . $user->last_name) : 'Member';

                \App\Services\Finance\WalletService::approveWithdrawal(
                    $id,
                    (int) $request->statusId,
                    $request->reason,
                    'Withdrawal request by ' . $userName . ' has been processed',
                    null
                );

                \App\Services\Cache\ClearCacheService::clearListCache('withdrawal_request_list');
                \Illuminate\Support\Facades\Cache::forget("withdrawal_request_{$id}");

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

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
                    ])->cursorPaginate(30, ['*'], 'cursor', $cursor);
                }
            );

            if ($withdrawalRequestData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No withdrawal requests found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal requests fetched successfully.',
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
                    'status:status_id,status_name'
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
}

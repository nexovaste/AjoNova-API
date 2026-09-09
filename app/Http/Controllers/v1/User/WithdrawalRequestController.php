<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WithdrawalRequestResource;
use App\Models\Admin\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WithdrawalRequestController extends Controller
{
    //Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $memberId = Auth::guard('user')->user()->user_id;
            $cursor = $request->query('cursor');
            $withdrawalRequestData = WithdrawalRequest::where('user_id', $memberId)->with([
                'status:status_id,status_name',
                'user:user_id,first_name,last_name,title_id,passport',
                'user.title:title_id,title_name',
                'attendedByStaff:staff_id,first_name,last_name'
            ])->orderBy('withdrawal_request_id', 'desc')->cursorPaginate(30, ['*'], 'cursor', $cursor);

            if ($withdrawalRequestData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No withdrawal requests found.',
                    'data' => []
                ], 200);
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
}

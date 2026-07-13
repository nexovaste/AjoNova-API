<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LoanRepaymentScheduleResource;
use App\Models\Admin\LoanRepaymentSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoanRepaymentScheduleController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $userId = $request->header('X-User-ID');
            $cursor = $request->query('cursor');
            $cacheKey = "loan_repayment_schedule_user_{$userId}_cursor_" . ($cursor ?? 'first_page');
            $loanRepaymentSchedule = Cache::tags("loan_repayment_schedule_user_{$userId}")->flexible($cacheKey, [now()->addMonth(), null], function () use ($userId, $cursor) {
                return LoanRepaymentSchedule::with([
                    'status:status_id,status_name',
                ])->orderBy('created_at', 'desc')
                    ->orderBy('loan_id', 'desc')
                    ->where('user_id', $userId)
                    ->cursorPaginate(30, ['*'], 'cursor', $cursor);
            });
            if ($loanRepaymentSchedule->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found.',
                    'data' => []
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Records fetched successfully.',
                'data' => LoanRepaymentScheduleResource::collection($loanRepaymentSchedule),
                'pagination' => [
                    'per_page' => $loanRepaymentSchedule->perPage(),
                    'next_cursor' => optional($loanRepaymentSchedule->nextCursor())->encode(),
                    'prev_cursor' => optional($loanRepaymentSchedule->previousCursor())->encode(),
                    'has_more' => $loanRepaymentSchedule->hasMorePages(),
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

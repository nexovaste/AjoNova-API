<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LoanResource;
use App\Models\Admin\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoanController extends Controller
{
    //Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $memberId = Auth::guard('user')->user()->user_id;

            $cursor = $request->query('cursor');
            $loanData = Loan::where('user_id', $memberId)->with([
                'status:status_id,status_name',
                'user:user_id,first_name,last_name,title_id,passport',
                'user.title:title_id,title_name',
                'attendedByStaff:staff_id,first_name,last_name'
            ])->orderBy('loan_id', 'desc')->cursorPaginate(30, ['*'], 'cursor', $cursor);

            if ($loanData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No loan records found.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Loan records fetched successfully.',
                'data' => LoanResource::collection($loanData),
                'pagination' => [
                    'next_cursor' => $loanData->nextCursor()?->encode(),
                    'previous_cursor' => $loanData->previousCursor()?->encode(),
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

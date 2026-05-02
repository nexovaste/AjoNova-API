<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\admin\GuarantorResource;
use App\Http\Resources\Admin\LoanResource;
use App\Models\Admin\Guarantor;
use App\Models\Admin\Loan;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $cursor = $request->query('cursor');
            $cacheKey = "loan_list_" . ($cursor ?? 'first_page');
            $loanData = Cache::tags('loan_list')->flexible(
                $cacheKey,
                [now()->addMonth(), null],
                function () use ($cursor) {
                    return Loan::with([
                        'status:status_id,status_name',
                        'user:user_id,title_id,first_name,middle_name,last_name,passport',
                        'user.title:title_id,title_name'
                    ])->cursorPaginate(30, ['*'], 'cursor', $cursor);
                }
            );

            if ($loanData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No loan records found.',
                    'data' => []
                ], 404);
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

    // Store a newly created resource in storage.

    public function applyLoan(Request $request)
    {
        $request->validate([
            'principalAmount' => 'required|numeric|min:0.01',
            'titleId' => 'required|exists:setup_titles,title_id',
            'genderId' => 'required|exists:setup_genders,gender_id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'phoneNumber' => 'required|string|max:20',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'meansOfIdentificationId' => 'required|exists:means_of_identifications,means_of_identification_id',
            'identificationNumber' => 'required|string|max:255|unique:guarantors,id_number',
            'relationshipToBorrower' => 'required|string|max:255',
            'guaranteedAmount' => 'required|numeric|min:0.01',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                LoanService::applyLoan(
                    $userId = Auth::guard('user')->user()->user_id,
                    $request->input('principalAmount'),
                    $request->input('titleId'),
                    $request->input('genderId'),
                    $request->input('firstName'),
                    $request->input('lastName'),
                    $request->input('middleName'),
                    $request->input('phoneNumber'),
                    $request->input('email'),
                    $request->input('address'),
                    $request->input('occupation'),
                    $request->input('meansOfIdentificationId'),
                    $request->input('identificationNumber'),
                    $request->input('relationshipToBorrower'),
                    $request->input('guaranteedAmount')
                );
                 Cache::tags('loan_list')->flush();
                Cache::tags(['guarantor'])->forget("guarantor_user_" . $userId);
                return response()->json([
                    'success' => true,
                    'message' => 'Loan application submitted successfully'
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    // Update the specified resource in storage.

    public function approveLoan(Request $request, string $id)
    {
        $request->validate([
            'statusId' => 'required|integer|exists:setup_statuses,status_id|in:6,8',
            'reason' => 'required_if:statusId,8|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {

                LoanService::loanApproval(
                    $id,
                    Auth::guard('admin')->user()->staff_id,
                    $request->input('statusId'),
                    $request->input('reason')
                );
                Cache::tags('loan_list')->flush();
                return response()->json([
                    'success' => true,
                    'message' => 'Loan application processed successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function loanRepayment(Request $request)
    {
        try {
            $installmentNumber = $request->header('X-Installment-Number');
            $loanId = $request->header('X-Loan-ID');
            $userId = $request->header('X-User-ID');
            $amount = $request->header('X-Amount');
            return DB::transaction(function () use ($installmentNumber, $loanId, $userId, $amount) {

                LoanService::loanRepayment(
                    $loanId,
                    $installmentNumber,
                    $userId,
                    $amount,
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Loan repayment processed successfully'
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function fetchGuarantors()
    {
        try {
            $userId = Auth::guard('user')->user()->user_id;
            $cacheKey = "guarantor_user_" . $userId;

            $guarantors = Cache::tags(['guarantor'])
                ->remember($cacheKey, now()->addMinutes(10), function () use ($userId) {
                    return Guarantor::whereHas('loan', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                        ->orderBy('created_at', 'desc')
                        ->get();
                });

            return response()->json([
                'success' => true,
                'message' => 'Guarantor records fetched successfully.',
                'data' => GuarantorResource::collection($guarantors),
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching guarantor: ' . $e->getMessage(),
            ], 500);
        }
    }
}

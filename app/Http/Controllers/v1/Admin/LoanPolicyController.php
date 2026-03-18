<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\LoanPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanPolicyController extends Controller
{
    //  Display a listing of the resource.
    public function index() {}

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $request->validate([
            'loanMultiplier' => 'required|integer|min:1',
            'minimumAmount' => 'required|numeric|min:0',
            'maximumAmount' => 'required|numeric|min:0|gte:minimumAmount',
            'minDurationMonths' => 'required|integer|min:1',
            'maxDurationMonths' => 'required|integer|min:1|gte:minDurationMonths',
            'interestRate' => 'required|numeric|min:0',
            'processingFee' => 'nullable|numeric|min:0',
            'penaltyRate' => 'nullable|numeric|min:0',
            'eligibilityMonths' => 'required|integer|min:0',
            'allowMultipleLoans' => 'required|boolean',
        ]);

        $admin = Auth::guard('admin')->user();
        LoanPolicy::create([
            'loan_multiplier' => $request->loanMultiplier,
            'minimum_amount' => $request->minimumAmount,
            'maximum_amount' => $request->maximumAmount,
            'min_duration_months' => $request->minDurationMonths,
            'max_duration_months' => $request->maxDurationMonths,
            'interest_rate' => $request->interestRate,
            'processing_fee' => $request->processingFee ?? 0,
            'penalty_rate' => $request->penaltyRate ?? 0,
            'eligibility_months' => $request->eligibilityMonths,
            'allow_multiple_loans' => $request->allowMultipleLoans,
            'created_by' => $admin->staff_id ?? 'system',
        ]);

        return response()->json(
            [
                'success' => true,
                'message' => 'Loan policy created successfully'
            ],
            201
        );
    }

    // Display the specified resource.
    public function show(string $id)
    {
        //
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        //
    }

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {
        //
    }
}

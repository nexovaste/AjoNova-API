<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LoanPolicyResource;
use App\Jobs\ActivityLogJob;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\LoanPolicy;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoanPolicyController extends Controller
{
    //  Display a listing of the resource.
    public function index()
    {
        try {
            Auth::guard('admin')->user();
            $cacheKey = "loan_policies_with_status";
            $loanPolicies = Cache::remember($cacheKey, now()->addMonth(), function () {
                return LoanPolicy::with('status:status_id,status_name')
                    ->orderBy('created_at', 'desc')
                    ->get();
            });
            return response()->json([
                'success' => true,
                'message' => 'Loan policies retrieved successfully',
                'data' => LoanPolicyResource::collection($loanPolicies)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve loan policy records: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'loanMultiplier' => 'required|integer|min:1',
            'minimumAmount' => 'required|numeric|min:0',
            'minDurationMonths' => 'required|integer|min:1',
            'maxDurationMonths' => 'required|integer|min:1|gte:minDurationMonths',
            'interestRate' => 'required|numeric|min:0',
            'processingFee' => 'nullable|numeric|min:0',
            'penaltyRate' => 'nullable|numeric|min:0',
            'eligibilityMonths' => 'required|integer|min:0',
            'allowMultipleLoans' => 'required|boolean',
        ]);

        try {
            $loanPolicy = LoanPolicy::findOrFail($id);
            $dataBeforeUpdate = $loanPolicy->getOriginal();
            $admin = Auth::guard('admin')->user();

            $loanPolicy->update([
                'loan_multiplier' => $validated['loanMultiplier'],
                'minimum_amount' => $validated['minimumAmount'],
                'min_duration_months' => $validated['minDurationMonths'],
                'max_duration_months' => $validated['maxDurationMonths'],
                'interest_rate' => $validated['interestRate'],
                'processing_fee' => $validated['processingFee'] ?? 0,
                'penalty_rate' => $validated['penaltyRate'] ?? 0,
                'eligibility_months' => $validated['eligibilityMonths'],
                'allow_multiple_loans' => $validated['allowMultipleLoans'],
                'updated_by' => $admin->staff_id ?? 'system',
            ]);

            $dataAfterUpdate = $loanPolicy->getChanges();
            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'Loan Policy Updated',
                description: 'Loan policy [ID: ' . $loanPolicy->loan_policy_id . '] was updated.',
                userType: 'Staff',
                performedBy: $admin->staff_id ?? "System",
                roleId: $admin?->roles?->pluck('id')->first() ?? 0,
                deviceInfo: Config::requestDetails(),
                metadata: [
                    'Before Update' => $dataBeforeUpdate,
                    'After Update' => $dataAfterUpdate,
                ]
            );
            Cache::forget("loan_policy_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Loan policy updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loan policy: ' . $e->getMessage()
            ], 500);
        }
    }
}

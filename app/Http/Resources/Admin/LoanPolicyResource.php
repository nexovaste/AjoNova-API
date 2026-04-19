<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'loanPolicyId' => $this->loan_policy_id,
            'loanMultiplier' => $this->loan_multiplier,
            'minimumAmount' => $this->minimum_amount,
            'maximumAmount' => $this->maximum_amount,
            'minDurationMonths' => $this->min_duration_months,
            'maxDurationMonths' => $this->max_duration_months,
            'interestRate' => $this->interest_rate,
            'processingFee' => $this->processing_fee,
            'penaltyRate' => $this->penalty_rate,
            'eligibilityMonths' => $this->eligibility_months,
            'allowMultipleLoans' => $this->allow_multiple_loans,
            'status' => [
                'statusId' => $this->status_id ?? null,
                'statusName' => $this->status->status_name ?? null,
            ],
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'createdAt' => Carbon::parse($this->created_at)->toDayDateTimeString(),
            'updatedAt' => Carbon::parse($this->updated_at)->toDayDateTimeString(),
        ];
    }
}

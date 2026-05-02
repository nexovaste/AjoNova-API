<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'loanId' => $this->loan_id,
            'userId' => $this->user_id,
            'durationMonths' => $this->duration_months,
            'principalAmount' => $this->principal_amount,
            'interestAmount' => $this->interest_amount,
            'loanReference' => $this->loan_reference,
            'requestAt' => Carbon::parse($this->requested_at)->toDateTimeString(),
            'disbursementAt' => $this->disbursement_at?->toDateTimeString(),
            'attendedBy' => $this->attended_by,
            'attendedAt' => Carbon::parse($this->attended_at)->toDateTimeString(),
            'rejectionReason' => $this->rejection_reason,
            'user' => [
                'firstName' => $this->user->first_name ?? null,
                'middleName' => $this->user->middle_name ?? null,
                'lastName' => $this->user->last_name ?? null,
                
                'title' => [
                    'titleId' => $this->user->title->title_id ?? null,
                    'titleName' => $this->user->title->title_name ?? null,
                ],
            ],

            'status' => [
                'statusId' => $this->status_id,
                'statusName' => $this->status->status_name ?? null,
            ],
            'createdAt' => Carbon::parse($this->created_at)->toDateTimeString(),
            'updatedAt' => Carbon::parse($this->updated_at)->toDateTimeString(),
        ];
    }
}

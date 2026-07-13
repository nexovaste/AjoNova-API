<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRepaymentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'loanId' => $this->loan_id,
            'installmentNumber' => $this->installment_number,
            'dueDate' => Carbon::parse($this->due_date)->toDateString(),
            'principalAmount' => $this->principal_amount,
            'repaymentAmount' => $this->repayment_amount,
            'interestAmount' => $this->interest_amount,
            'monthlyRepayment' => $this->monthly_repayment,
            'amountPaid' => $this->amount_paid,
            'paidAt' => Carbon::parse($this->paid_at)->toDateString(),
            'processedBy' => $this->processed_at,
            'createdAt' => Carbon::parse($this->created_at)->toDayDateTimeString(),
            'updatedAt' => Carbon::parse($this->updated_at)->toDayDateTimeString(),
            'status' => [
                'statusId' => $this->status_id ?? null,
                'statusName' => $this->status->status_name ?? null,
            ],
            


            
        ];
    }
}

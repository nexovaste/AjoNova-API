<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'withdrawalRequestId' => $this->withdrawal_request_id,
            'userId' => $this->user_id,
            'amount' => $this->amount,
            'withdrawalType' => $this->withdrawal_type,
            'status' => $this->status?->status_name,
            'reason' => $this->reason,
            'attendedBy' => $this->attended_by,
            'attendedAt' => $this->attended_at?->toDateTimeString()
        ];
    }
}

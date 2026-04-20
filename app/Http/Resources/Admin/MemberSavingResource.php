<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSavingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'member_saving_id' => $this->member_saving_id,
            'user_id' => $this->user_id,
            'saving_amount' => $this->saving_amount,
            'saving_date' => $this->saving_date,
            'payment_channel_type_id' => $this->payment_channel_type_id,
            'contribution_month' => $this->contribution_month,
            'contribution_year' => $this->contribution_year,
            'contribution_period' => $this->contribution_period,
            'reference' => $this->reference,
            'ledger_entry_id' => $this->ledger_entry_id,
            'status_id' => $this->status_id,
            'processed_by' => $this->processed_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'status' => [
                'status_id' => $this->status?->status_id,
                'status_name' => $this->status?->status_name,
            ],
            'payment_channel' => [
                'payment_channel_type_id' => $this->paymentChannel?->payment_channel_type_id,
                'payment_channel_type_name' => $this->paymentChannel?->payment_channel_type_name,
            ],
            'ledger' => [
                'ledger_entry_id' => $this->ledger?->ledger_entry_id,
                'entry_type' => $this->ledger?->entry_type,
            ],
        ];
    }
}

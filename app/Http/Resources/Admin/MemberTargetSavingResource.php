<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberTargetSavingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'memberTargetSaving_id' => $this->member_target_saving_id,
            'user_id' => $this->user_id,
            'member_target_saving_setting_id' => $this->member_target_saving_setting_id,
            'monthly_amount' => $this->monthly_amount,
            'current_amount' => $this->current_amount,
            'reference' => $this->reference,
            'processed_by' => $this->processed_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => [
                'status_id' => $this->status?->status_id,
                'status_name' => $this->status?->status_name,
            ],
            'ledger' => [
                'ledger_entry_id' => $this->ledger?->ledger_entry_id,
                'entry_type' => $this->ledger?->entry_type,
            ],
            'payment_channel' => [
                'payment_channel_type_id' => $this->paymentChannel?->payment_channel_type_id,
                'payment_channel_type_name' => $this->paymentChannel?->payment_channel_type_name,
            ],
            'setting' => [
                'member_target_saving_setting_id' => $this->setting?->member_target_saving_setting_id,
                'target_name' => $this->setting?->target_name,
                'target_amount' => $this->setting?->target_amount,
                'monthly_amount' => $this->setting?->monthly_amount,
                'duration_months' => $this->setting?->duration_months,
                'start_date' => $this->setting?->start_date,
                'end_date' => $this->setting?->end_date,
            ],
        ];
    }
}

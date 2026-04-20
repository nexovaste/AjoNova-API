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
            'userId' => $this->user_id,
            'memberTargetSavingSettingId' => $this->member_target_saving_setting_id,
            'monthlyAmount' => $this->monthly_amount,
            'currentAmount' => $this->current_amount,
            'reference' => $this->reference,
            'processedBy' => $this->processed_by,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'status' => [
                'statusId' => $this->status?->status_id,
                'statusName' => $this->status?->status_name,
            ],
            'ledger' => [
                'ledgerEntryId' => $this->ledger?->ledger_entry_id,
                'entryType' => $this->ledger?->entry_type,
            ],
            'paymentChannel' => [
                'paymentChannelTypeId' => $this->paymentChannel?->payment_channel_type_id,
                'paymentChannelTypeName' => $this->paymentChannel?->payment_channel_type_name,
            ],
            'setting' => [
                'memberTargetSavingSettingId' => $this->setting?->member_target_saving_setting_id,
                'targetName' => $this->setting?->target_name,
                'targetAmount' => $this->setting?->target_amount,
                'monthlyAmount' => $this->setting?->monthly_amount,
                'durationMonths' => $this->setting?->duration_months,
                'startDate' => $this->setting?->start_date,
                'endDate' => $this->setting?->end_date,
            ],
        ];
    }
}

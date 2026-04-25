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
            'memberSavingId' => $this->member_saving_id,
            'userId' => $this->user_id,
            'savingAmount' => $this->saving_amount,
            'savingDate' => $this->saving_date,
            'paymentChannelTypeId' => $this->payment_channel_type_id,
            'contributionMonth' => $this->contribution_month,
            'contributionYear' => $this->contribution_year,
            'contributionPeriod' => $this->contribution_period,
            'reference' => $this->reference,
            'processedBy' => $this->processed_by,
            'createdAt' => $this->created_at?->toDateTimeString(),
            'updatedAt' => $this->updated_at?->toDateTimeString(),
            'status' => [
                'statusId' => $this->status?->status_id,
                'statusName' => $this->status?->status_name,
            ],
            'paymentChannel' => [
                'paymentChannelTypeId' => $this->paymentChannel?->payment_channel_type_id,
                'paymentChannelTypeName' => $this->paymentChannel?->payment_channel_type_name,
            ],
            'ledger' => [
                'ledgerEntryId' => $this->ledger?->ledger_entry_id,
                'entryType' => $this->ledger?->entry_type,
            ],
        ];
    }
}

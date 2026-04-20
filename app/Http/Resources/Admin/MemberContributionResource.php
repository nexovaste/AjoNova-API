<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberContributionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'memberContributionId' => $this->member_contribution_id,
            'userId' => $this->user_id,
            'contributionAmount' => $this->contribution_amount,
            'paymentChannelTypeId' => $this->payment_channel_type_id,
            'paymentReference' => $this->payment_reference,
            'createdAt' => $this->created_at?->toDateTimeString(),
            'updatedAt' => $this->updated_at?->toDateTimeString(),
            'status' => [
                'statusId' => $this->status?->status_id,
                'statusName' => $this->status?->status_name,
            ],
            'ledger' => [
                'ledgerEntryId' => $this->ledger?->ledger_entry_id,
                'entryType' => $this->ledger?->entry_type,
            ],
            'payment_channel' => [
                'paymentChannelTypeId' => $this->paymentChannel?->payment_channel_type_id,
                'paymentChannelTypeName' => $this->paymentChannel?->payment_channel_type_name,
            ],
        ];
    }
}

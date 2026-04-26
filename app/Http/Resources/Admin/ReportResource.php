<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'walletId' => $this->wallet_id,
            'entryType' => $this->entry_type,
            'transactionType' => $this->transaction_type,
            'amount' => $this->amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'reference' => $this->reference,
            'description' => $this->description,
            'createdBy' => $this->created_by,
            'createdAt' => Carbon::parse($this->created_at)->toDayDateTimeString(),

        ];
    }
}

<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'createdAt' => $this->withdraw_at ? \Carbon\Carbon::parse($this->withdraw_at)->format('d M, Y h:i A') : 'N/A',
            'requestedDate' => $this->withdraw_at ? \Carbon\Carbon::parse($this->withdraw_at)->format('d M, Y') : 'N/A',
            'attendedBy' => ($this->relationLoaded('attendedByStaff') && $this->attendedByStaff) ? ($this->attendedByStaff->first_name . ' ' . $this->attendedByStaff->last_name) : 'N/A',
            'attendedAt' => $this->attended_at ? $this->attended_at->toDateTimeString() : 'N/A',
            'user' => [
                'firstName' => $this->user?->first_name ?? null,
                'middleName' => $this->user?->middle_name ?? null,
                'lastName' => $this->user?->last_name ?? null,

                'title' => [
                    'titleId' => $this->user?->title?->title_id ?? null,
                    'titleName' => $this->user?->title?->title_name ?? null,
                ],
                'passport' => [
                    'passportUrl' => $this->user?->passport ? Storage::url("passports/userPictures/{$this->user->passport}") : null
                ],
            ]
        ];
    }
}

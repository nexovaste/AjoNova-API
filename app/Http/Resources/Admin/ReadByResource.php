<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadByResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $staff = $this->relationLoaded('staff') ? $this->staff : $this->staff;
        $name = $staff ? trim($staff->first_name . ' ' . $staff->last_name) : 'Staff Member';
        $role = $staff && $staff->roles ? $staff->roles->pluck('name')->implode(', ') : 'Staff';
        $readAt = $this->read_at ? \Carbon\Carbon::parse($this->read_at) : null;

        return [
            'staffId'         => $this->staff_id,
            'name'            => $name,
            'role'            => $role ?: 'Staff',
            'readAt'          => $readAt ? $readAt->toDateTimeString() : 'N/A',
            'readAtFormatted' => $readAt ? $readAt->format('Y-m-d H:i:s') : 'N/A',
            'timeAgo'         => $readAt ? ($readAt->isFuture() ? 'Just now' : $readAt->diffForHumans()) : 'Just now',
        ];
    }
}

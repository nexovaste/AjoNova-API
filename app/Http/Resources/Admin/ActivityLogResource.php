<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $staff = $this->relationLoaded('performedByStaff') ? $this->performedByStaff : null;
        $user = $this->relationLoaded('performedByUser') ? $this->performedByUser : null;

        $actorName = 'System / Administrator';
        if ($staff) {
            $actorName = trim($staff->first_name . ' ' . $staff->last_name);
        } elseif ($user) {
            $actorName = trim($user->first_name . ' ' . $user->last_name);
        } elseif ($this->performed_by) {
            $actorName = $this->performed_by;
        }

        $createdAt = $this->created_at ? Carbon::parse($this->created_at) : null;

        return [
            'id'                 => $this->activity_log_id,
            'action'             => $this->action,
            'description'        => $this->description,
            'performedBy'        => $this->performed_by,
            'performedByName'    => $actorName,
            'userType'           => $this->user_type,
            'ip'                 => $this->ip_address,
            'device'             => $this->device,
            'browser'            => $this->browser,
            'createdAt'          => $createdAt ? $createdAt->toDateTimeString() : 'N/A',
            'createdAtFormatted' => $createdAt ? $createdAt->format('Y-m-d H:i:s') : 'N/A',
            'timeAgo'            => $createdAt ? ($createdAt->isFuture() ? 'Just now' : $createdAt->diffForHumans()) : 'Just now',
            'isRead'             => (bool) ($this->is_read ?? false),
        ];
    }
}

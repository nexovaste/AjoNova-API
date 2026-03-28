<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->activity_log_id,
            'action'        => $this->action,
            'description'   => $this->description,
            'performedBy'   => $this->performed_by,
            'userType'      => $this->user_type,
            'iP'            => $this->ip_address,
            'device'        => $this->device,
            'browser'       => $this->browser,
            $this->mergeWhen(
                $request->user()?->can('manage activity logs'),
                ['metadata' => $this->metadata]
            ),
            'createdAt'     => $this->created_at,
            'isRead'        => (bool) ($this->is_read ?? false),
        ];
    }
}

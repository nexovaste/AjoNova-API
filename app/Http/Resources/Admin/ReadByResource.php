<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadByResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'staffId' => $this->staff_id,
            'name'     => $this->staff->first_name . ' '. $this->staff->last_name,
            'role'     => $this->staff->roles?->pluck('name')->implode(', '),
            'readAt'  => $this->read_at,
        ];
    }
}

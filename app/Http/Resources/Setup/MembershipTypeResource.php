<?php

namespace App\Http\Resources\Setup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipTypeResource  extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'membershipTypeId' => $this->membership_type_id,
            'membershipTypeName' => $this->membership_type_name,
        ];
    }
}

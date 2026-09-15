<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'roleId' => $this->id,
            'roleName' => $this->name,
            'userCount' => $this->user_count ?? $this->users_count ?? 0,
            'permissions' => $this->permissions->map(function ($permission) {
                return [
                    'permissionId' => $permission->id,
                    'permissionName' => $permission->name,
                ];
            }),
        ];
    }
}

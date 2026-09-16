<?php

namespace App\Services;

class ActivityLogService
{
    public static function log(
        string $modelClass,
        string $action,
        string $description,
        string $userType,
        string $performedBy,
        ?int $roleId = null,
        array $metadata = [],
        array $deviceInfo = [],
    ): void {
       

        $data = [
            'performed_by' => $performedBy,
            'role_id'      => $roleId,
            'user_type'    => $userType,
            'action'       => $action,
            'description'  => $description,
            'metadata'     => $metadata,
            'ip_address'   => $deviceInfo['ip_address'] ?? request()->ip(),
            'device'       => $deviceInfo['device'] ?? 'Unknown',
            'browser'      => $deviceInfo['browser'] ?? 'Unknown',
            'created_at'   => now(),
        ];

        $modelClass::create($data);
    }
}
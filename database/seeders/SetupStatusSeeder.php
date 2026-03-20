<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupStatus;


class SetupStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'ACTIVE',
            'INACTIVE',
            'SUSPENDED',
            'DELETED',
            'PENDING',
            'APPROVED',
            'DECLINED',
            'REJECTED',
            'CANCELLED',
            'PROCESSING',
            'COMPLETED',
            'FAILED',
            'REVERSED',
            'DISBURSED',
            'ONGOING',
            'OVERDUE',
            'CLOSED',
            'DEFAULTED',
            'LOCKED',
            'UNLOCKED',
            'PAID',
            'UNPAID',
            'SUCCESS',
        ];

        $insertData = [];

        foreach ($statuses as $status) {
            $insertData[] = [
                'status_name' => $status,
            ];
        }

        SetupStatus::insertOrIgnore($insertData);
    }
}

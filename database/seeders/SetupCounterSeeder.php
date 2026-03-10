<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupCounter;

class SetupCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['counter_id' => 'USER', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF USER'],
            ['counter_id' => 'STAFF', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF STAFF'],
            ['counter_id' => 'LOAN', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF LOAN'],
        ];

        SetupCounter::insertOrIgnore($counters);
    }
}

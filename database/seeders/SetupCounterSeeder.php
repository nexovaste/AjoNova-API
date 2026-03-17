<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupCounter;

class SetupCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['counter_id' => 'MEM', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF MEMBER'],
            ['counter_id' => 'STFF', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF STAFF'],
            ['counter_id' => 'LN', 'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF LOAN'],
        ];

        SetupCounter::insertOrIgnore($counters);
    }
}

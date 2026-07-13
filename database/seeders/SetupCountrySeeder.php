<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupCountry;

class SetupCountrySeeder extends Seeder
{

    // Run the database seeds.
    public function run(): void
    {
        $countries = [
            [
                'country_name' => 'Nigeria',
                'country_code' => 'NG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        SetupCountry::insertOrIgnore($countries);
    }
}

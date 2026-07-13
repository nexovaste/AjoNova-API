<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupGender;

class SetupGenderSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $genders = [
            ['gender_name' => 'MALE'],
            ['gender_name' => 'FEMALE'],
        ];

        SetupGender::insertOrIgnore($genders);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupState;

class SetupStateSeeder extends Seeder
{

    // Run the database seeds.
    public function run(): void
    {
        $states = [
            'Abia',
            'Adamawa',
            'Akwa Ibom',
            'Anambra',
            'Bauchi',
            'Bayelsa',
            'Benue',
            'Borno',
            'Cross River',
            'Delta',
            'Ebonyi',
            'Edo',
            'Ekiti',
            'Enugu',
            'Gombe',
            'Imo',
            'Jigawa',
            'Kaduna',
            'Kano',
            'Katsina',
            'Kebbi',
            'Kogi',
            'Kwara',
            'Lagos',
            'Nasarawa',
            'Niger',
            'Ogun',
            'Ondo',
            'Osun',
            'Oyo',
            'Plateau',
            'Rivers',
            'Sokoto',
            'Taraba',
            'Yobe',
            'Zamfara',
            'Federal Capital Territory',
        ];

        $insertData = [];
        foreach ($states as $state) {
            $insertData[] = [
                'country_id' => 1,   // Nigeria
                'state_name' => $state,
            ];
        }

        SetupState::insertOrIgnore($insertData);
    }
}

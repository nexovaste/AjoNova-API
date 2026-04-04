<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\MeansOfIdentification;

class MeansOfIdentificationSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $identifications = ['NIN', 'PASSPORT', 'DRIVER_LICENSE', 'VOTER_CARD'];

        $insertData = [];
        foreach ($identifications as $identification) {
            $insertData[] = [
                'means_of_identification_name' => $identification,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MeansOfIdentification::insertOrIgnore($insertData);
    }
}

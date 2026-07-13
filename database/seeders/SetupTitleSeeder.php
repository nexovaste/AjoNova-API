<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupTitle;

class SetupTitleSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $titles = ['MR', 'MRS', 'MISS', 'DR', 'PROF', 'ENGR', 'HON', 'CHIEF', 'REV', 'PASTOR'];
        $insertData = [];
        foreach ($titles as $title) {
            $insertData[] = [
                'title_name' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        SetupTitle::insertOrIgnore($insertData);
    }
}

<?php


namespace Database\Seeders;

use App\Models\Setup\StaffCategory;
use Illuminate\Database\Seeder;



class StaffCategorySeeder extends Seeder
{
    public function run(): void
    {
        $genders = [
            ['staff_category_name' => 'ACADEMIC'],
            ['staff_category_name' => 'NON-ACADEMIC'],
        ];

        StaffCategory::insertOrIgnore($genders);
    }
}

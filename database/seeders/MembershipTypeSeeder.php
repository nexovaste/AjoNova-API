<?php


namespace Database\Seeders;

use App\Models\Setup\MembershipType;
use Illuminate\Database\Seeder;



class MembershipTypeSeeder extends Seeder
{
    public function run(): void
    {
        $genders = [
            ['membership_type_name' => 'MEMBER'],
            ['membership_type_name' => 'NON-MEMBER'],
        ];

        MembershipType::insertOrIgnore($genders);
    }
}

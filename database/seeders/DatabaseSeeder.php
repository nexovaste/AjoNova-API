<?php

namespace Database\Seeders;



use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
  
    // Seed the application's database.
    public function run(): void
       {
        $this->call([
        MeansOfIdentificationSeeder::class,
        SetupCounterSeeder::class,
        SetupGenderSeeder::class,
        SetupTitleSeeder::class,
        SetupStatusSeeder::class,
        SetupCountrySeeder::class,
        SetupStateSeeder::class,
        SetupLgaSeeder::class,
        PermissionSeeder::class,
        MembershipTypeSeeder::class,
        StaffCategorySeeder::class,
        PaymentChannelTypeSeeder::class,
        ]);
    }
}

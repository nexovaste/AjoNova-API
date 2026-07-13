<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Run the migrations.
    public function up(): void
    {
        Schema::create('setup_countries', function (Blueprint $table) {
            $table->id('country_id');
            $table->string('country_name', 100)->unique();
            $table->string('country_code', 10)->unique();
            $table->timestamps();
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('setup_countries');
    }
};

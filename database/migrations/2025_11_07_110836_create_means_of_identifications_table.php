<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('means_of_identifications', function (Blueprint $table) {
            $table->id('means_of_identification_id');
            $table->string('means_of_identification_name', 255)->unique();
            $table->timestamps();
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('means_of_identifications');
    }
};

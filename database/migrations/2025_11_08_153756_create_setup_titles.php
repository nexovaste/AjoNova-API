<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('setup_titles', function (Blueprint $table) {
            $table->id('title_id');
            $table->string('title_name')->unique();
            $table->timestamps();
        });
    }
    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('setup_titles');
    }
};

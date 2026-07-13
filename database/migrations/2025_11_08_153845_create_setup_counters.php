<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    // Run the migrations.
    public function up(): void
      {
        Schema::create('setup_counters', function (Blueprint $table) {
            $table->string('counter_id')->primary();
            $table->unsignedBigInteger('counter_value');
            $table->string('counter_description');
            $table->timestamps();
        });
    }
    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('setup_counters');
    }
};

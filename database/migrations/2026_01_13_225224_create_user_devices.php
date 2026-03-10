<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    // Run the migrations.
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->index();
            $table->string('device_id')->index();
            $table->string('device_type')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->unique(['user_id','device_id']);
        });
    }

  
    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};

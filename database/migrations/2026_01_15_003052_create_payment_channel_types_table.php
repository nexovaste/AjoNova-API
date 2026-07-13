<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('payment_channel_types', function (Blueprint $table) {// E.G salary, manual, transfer
            $table->id('payment_channel_type_id');
            $table->string('payment_channel_type_name', 100)->unique();
            $table->timestamps();
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('payment_channel_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {// E.G MEMBER AND NON-MEMBER(SAVINGS ONLY)
            $table->id('membership_type_id');
            $table->string('membership_type_name')->unique();
            $table->boolean('can_take_loan')->default(true);
            $table->timestamps();
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Run the migrations.
    public function up(): void
    {
        Schema::create('setup_lgas', function (Blueprint $table) {
            $table->id('lga_id');
            $table->unsignedBigInteger('state_id');
            $table->string('lga_name', 100);
            $table->timestamps();

            $table->unique(['state_id', 'lga_name']);
            $table->foreign('state_id')->references('state_id')->on('setup_states')->onDelete('restrict')->onUpdate('cascade');
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('setup_lgas');
    }
};

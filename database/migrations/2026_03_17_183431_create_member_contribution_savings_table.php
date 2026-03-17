<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('member_contribution_savings', function (Blueprint $table) {
            $table->id('member_contribution_saving');
            $table->string('user_id');
            $table->decimal('contribution_amount', 14, 2)->nullable();
            $table->decimal('saving_amount', 14, 2)->nullable();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('member_contribution_savings');
    }
};

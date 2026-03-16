<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id('wallet_id');
            $table->string('user_id')->unique(); // One wallet per member
            $table->decimal('savings_balance', 14, 2)->default(0.00);
            $table->decimal('total_contributions',14,2)->default(0);
            $table->decimal('outstanding_loan_balance', 14, 2)->default(0.00);
            $table->decimal('locked_balance', 14, 2)->default(0.00);
            $table->unsignedBigInteger('status_id')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status_id');
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

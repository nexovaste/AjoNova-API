<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id('ledger_entry_id');
            $table->string('user_id');
            $table->unsignedBigInteger('wallet_id');
            $table->string('entry_type')->nullable();// SAVINGS_DEPOSIT, LOAN_DISBURSEMENT, REPAYMENT, WITHDRAWAL, INTEREST, ADJUSTMENT
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            $table->string('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entry_type', 'created_at']);
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('wallet_id')->references('wallet_id')->on('wallets')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};

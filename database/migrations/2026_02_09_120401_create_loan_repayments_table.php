<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id('loan_repayment_id');
            $table->string('loan_id');
            $table->unsignedBigInteger('duration_months');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('interest_amount', 14, 2);
            $table->decimal('monthly_repayment', 14, 2);
            $table->decimal('total_payable', 14, 2);
            $table->date('repayment_date');
            $table->unsignedBigInteger('status_id')->default(22); //UNPAID
            $table->string('payment_reference')->unique();
            $table->unsignedBigInteger('payment_channel_type_id');
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->string('processed_by')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('loan_id')->on('loans')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('payment_channel_type_id')->references('payment_channel_type_id')->on('payment_channel_types')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('ledger_entry_id')->references('ledger_entry_id')->on('ledger_entries')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};

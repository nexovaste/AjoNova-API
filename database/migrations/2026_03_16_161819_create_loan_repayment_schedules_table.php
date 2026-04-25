<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {

            $table->id('loan_repayment_schedule_id');
            $table->string('user_id');
            $table->string('loan_id');
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('repayment_amount', 14, 2);
            $table->decimal('interest_amount', 14, 2);
            $table->decimal('monthly_repayment', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0.00);
            $table->timestamp('paid_at')->nullable();
            $table->string('processed_by')->nullable();
            $table->unsignedBigInteger('status_id')->default(22);
            $table->timestamps();
            $table->index(['loan_id', 'due_date']);

            $table->foreign('loan_id')->references('loan_id')->on('loans')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('loan_repayment_schedules');
    }
};

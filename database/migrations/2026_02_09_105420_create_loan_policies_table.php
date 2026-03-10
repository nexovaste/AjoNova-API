<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('loan_policies', function (Blueprint $table) {
            $table->id('loan_policy_id');
            $table->unsignedBigInteger('loan_multiplier'); // e.g. 10x savings
            $table->decimal('minimum_amount', 12, 2);
            $table->decimal('maximum_amount', 12, 2);
            $table->unsignedBigInteger('min_duration_months');
            $table->unsignedBigInteger('max_duration_months');
            $table->decimal('interest_rate', 5, 2); // 10%
            $table->unsignedBigInteger('eligibility_months');
            $table->boolean('allow_multiple_loans')->default(false);
            $table->unsignedBigInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('eligibility_months');
            $table->index('status_id');
            $table->unique(['loan_multiplier', 'min_duration_months', 'max_duration_months'], 'unique_loan_policy');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('loan_policies');
    }
};

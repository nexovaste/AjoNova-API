<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->string('loan_id')->primary();
            $table->string('user_id');
            $table->integer('duration_months');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('interest_amount', 14, 2);
            $table->string('loan_reference')->unique()->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->date('disbursed_at')->nullable();
            $table->string('attended_by')->nullable();
            $table->dateTime('attended_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('status_id')->default(5);
            $table->timestamps();

            $table->index('user_id');
            $table->index('status_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

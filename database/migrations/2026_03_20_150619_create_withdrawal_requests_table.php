<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id('withdrawal_request_id');
            $table->string('user_id')->index();
            $table->string('withdrawal_type'); // CONTRIBUTION, TARGET, COMPULSORY, LOCKED
            $table->decimal('amount', 14, 2);
            $table->unsignedBigInteger('status_id')->default(5); // 1 = PENDING
            $table->text('reason')->nullable();
            $table->string('attended_by')->nullable();
            $table->dateTime('attended_at')->nullable();
            $table->dateTime('withdraw_at');
            $table->boolean('is_approved')->default(false);

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};

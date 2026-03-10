<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('member_target_savings', function (Blueprint $table) {
            $table->id('member_target_saving_id');
            $table->string('user_id')->index();
            $table->string('target_name');
            $table->decimal('target_amount', 14, 2);
            $table->decimal('monthly_amount', 12, 2)->nullable(); // advisory only
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->unsignedBigInteger('status_id')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'target_name'], 'uniq_user_target');
            $table->index(['user_id', 'status_id']);
            $table->index(['status_id', 'end_date']);
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('member_target_savings');
    }
};

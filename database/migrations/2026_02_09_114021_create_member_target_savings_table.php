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
            $table->unsignedBigInteger('member_target_saving_setting_id')->index();
            $table->decimal('monthly_amount', 12, 2)->default(0.00); // advisory only
            $table->decimal('current_amount', 14, 2)->default(0.00);
            $table->unsignedBigInteger('payment_channel_type_id')->default(1);
            $table->string('reference')->unique()->nullable();
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->unsignedBigInteger('status_id')->default(1)->index();
            $table->string('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'member_target_saving_setting_id',], 'unique_active_target_per_setting');
            $table->index(['user_id', 'status_id']);
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('member_target_saving_setting_id')->references('member_target_saving_setting_id')->on('member_target_saving_settings')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('payment_channel_type_id')->references('payment_channel_type_id')->on('payment_channel_types')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('ledger_entry_id')->references('ledger_entry_id')->on('ledger_entries')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('member_target_savings');
    }
};

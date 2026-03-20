<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('member_savings', function (Blueprint $table) {
            $table->id('member_saving_id');
            $table->string('user_id');
            $table->decimal('saving_amount', 14, 2);
            $table->date('saving_date');
            $table->unsignedBigInteger('payment_channel_type_id')->default(1);
            $table->unsignedTinyInteger('saving_month')->storedAs('MONTH(saving_date)');
            $table->unsignedSmallInteger('saving_year')->storedAs('YEAR(saving_date)');
            $table->string('saving_period')->storedAs("DATE_FORMAT(saving_date, '%M %Y')");
            $table->string('reference')->unique()->nullable();
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->unsignedBigInteger('status_id')->default(5); // PENDING
            $table->string('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'saving_month', 'saving_year'], 'unique_saving_per_period');
            $table->index(['user_id', 'saving_date']);
            $table->index(['status_id', 'saving_date']);
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('payment_channel_type_id')->references('payment_channel_type_id')->on('payment_channel_types')->OnDelete('restrict')->OnUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('member_savings');
    }
};

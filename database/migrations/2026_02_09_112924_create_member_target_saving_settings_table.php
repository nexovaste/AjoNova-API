<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('member_target_saving_settings', function (Blueprint $table) {
            $table->id('member_target_saving_setting_id');
            $table->string('user_id')->index();
            $table->string('target_name');// e.g. "Buy a Car", "Home Renovation", "Education Fund"
            $table->decimal('target_amount', 14, 2);// total amount the member aims to save
            $table->unsignedSmallInteger('duration_months'); //6, 12, 18, 24
            $table->decimal('monthly_amount', 12, 2)->default(0.00); // advisory only
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
           
            $table->foreign('user_id')->references('user_id')->on('users')->OnDelete('restrict')->OnUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('member_target_saving_settings');
    }
};

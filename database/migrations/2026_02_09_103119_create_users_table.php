<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Run the migrations.
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('membership_number')->unique()->nullable();
            $table->unsignedBigInteger('title_id');
            $table->unsignedBigInteger('staff_category_id')->nullable();
            $table->unsignedBigInteger('membership_type_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('date_of_birth')->nullable();
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->string('email')->unique();
            $table->string('mobile_number')->unique();
            $table->string('home_address')->nullable();
            $table->unsignedBigInteger('lga_id')->nullable();
            $table->string('nin', 11)->unique()->nullable();
            $table->string('passport')->default('default.png')->nullable();
            $table->unsignedBigInteger('status_id')->default(1);
            $table->string('password');
            $table->decimal('monthly_salary', 14, 2)->nullable();
            $table->date('date_joined')->nullable();
            $table->date('date_exited')->nullable();
            $table->string('created_by');
            $table->string('updated_by');
            $table->unsignedInteger('login_attempt')->default(0);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();

            $table->index('title_id');
            $table->index('staff_category_id');
            $table->index('membership_type_id');
            $table->index('gender_id');
            $table->index('status_id');
            $table->index('lga_id');
            $table->index(['staff_category_id', 'membership_type_id'], 'idx_user_category_membership');

            $table->foreign('staff_category_id')->references('staff_category_id')->on('staff_categories')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('membership_type_id')->references('membership_type_id')->on('membership_types')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('title_id')->references('title_id')->on('setup_titles')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('gender_id')->references('gender_id')->on('setup_genders')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('lga_id')->references('lga_id')->on('setup_lgas')->onDelete('restrict')->onUpdate('cascade');
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

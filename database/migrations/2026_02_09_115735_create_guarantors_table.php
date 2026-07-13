<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id('guarantor_id');
            $table->string('loan_id');
            $table->unsignedBigInteger('title_id');
            $table->unsignedBigInteger('gender_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->unsignedBigInteger('means_of_identification_id')->nullable(); // NIN, Passport, Driver License
            $table->string('id_number')->nullable();
            $table->string('relationship_to_borrower')->nullable();
            $table->decimal('guaranteed_amount', 14, 2);
            $table->unsignedBigInteger('status_id');
            $table->timestamps();

            $table->unique(['loan_id', 'guarantor_id', 'id_number', 'email'], 'unique_guarantor');
            $table->foreign('means_of_identification_id')->references('means_of_identification_id')->on('means_of_identifications')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('title_id')->references('title_id')->on('setup_titles')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('gender_id')->references('gender_id')->on('setup_genders')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('loan_id')->references('loan_id')->on('loans')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
           
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};

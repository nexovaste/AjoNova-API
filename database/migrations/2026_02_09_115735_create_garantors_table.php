<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('garantors', function (Blueprint $table) {
            $table->id('garantor_id');
            $table->string('loan_id');
            $table->string('guarantor_user_id');
            $table->decimal('guaranteed_amount', 14, 2);
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamps();

            $table->unique(['loan_id', 'guarantor_user_id']);
            $table->foreign('loan_id')->references('loan_id')->on('loans')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('guarantor_user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }


    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('garantors');
    }
};

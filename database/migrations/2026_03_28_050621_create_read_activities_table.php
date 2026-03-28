<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('read_activities', function (Blueprint $table) {
            $table->id('read_activity_id');
            $table->unsignedBigInteger('activity_log_id');
            $table->string('staff_id')->index(); // Assuming this references the staff_id in the staff table, this will allow us to associate read activities with specific staff members
            $table->timestamp('read_at')->useCurrent()->index(); // To store the timestamp of when the activity was read, this can be useful for tracking and analytics purposes
            $table->timestamps();

            $table->unique(['activity_log_id', 'staff_id']); 
            $table->index(['activity_log_id', 'staff_id']);
            $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('activity_log_id')->references('activity_log_id')->on('activity_logs')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('read_activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reset_password_tokens', function (Blueprint $table) {
            $table->string('email')->unique();
            $table->string('token', 100)->unique();
            $table->timestamp('created_at');
    
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reset_password_tokens');
    }
};

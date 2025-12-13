<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['ADMIN', 'LEAD', 'WORKER'])->default('WORKER');
            $table->enum('dept', ['COCR', 'SOLIDEX', 'PRINT', 'FD', 'MULTI'])->nullable();
            $table->string('initials', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'dept']);
            $table->index('active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

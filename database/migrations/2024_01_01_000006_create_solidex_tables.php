<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solidex_case_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->unsignedInteger('count')->default(0);
            $table->string('implant_system', 100)->nullable();
            $table->string('lot', 100)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('solidex_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->string('tooth_number', 10);
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'DONE'])->default('OPEN');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['case_id', 'tooth_number']);
            $table->index('status');
        });

        Schema::create('solidex_tooth_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_id')->constrained('solidex_teeth')->onDelete('cascade');
            $table->enum('stage', ['TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'CNC', 'QC']);
            $table->json('precam_subtasks')->nullable();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'DONE'])->default('PENDING');
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('completed_by_initials', 10)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('machine', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['tooth_id', 'stage']);
            $table->index(['stage', 'status']);
            $table->index('assignee_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solidex_tooth_stages');
        Schema::dropIfExists('solidex_teeth');
        Schema::dropIfExists('solidex_case_meta');
    }
};

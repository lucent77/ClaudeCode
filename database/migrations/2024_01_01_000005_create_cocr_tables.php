<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cocr_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->string('type', 100)->nullable();
            $table->string('disk_material', 100)->nullable();
            $table->enum('mi', ['MODEL', 'IO', 'FILE'])->nullable();
            $table->string('shade', 50)->nullable();
            $table->string('milling_shade', 50)->nullable();
            $table->boolean('fc')->default(false);
            $table->boolean('ah')->default(false);
            $table->string('contact', 100)->nullable();
            $table->string('occ', 100)->nullable();
            $table->string('implant_type', 100)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('cocr_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->enum('stage', ['TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS', 'QC']);
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'DONE'])->default('PENDING');
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('completed_by_initials', 10)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('machine', 100)->nullable();
            $table->string('oven', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['case_id', 'stage']);
            $table->index(['stage', 'status']);
            $table->index('assignee_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cocr_stages');
        Schema::dropIfExists('cocr_meta');
    }
};

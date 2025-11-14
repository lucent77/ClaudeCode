<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('slack_case_id', 100)->unique()->nullable();
            $table->string('patient_name');
            $table->string('assignee_name')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_time')->nullable();
            $table->date('due_date')->nullable();
            $table->date('preop_scan_date')->nullable();
            $table->date('surgery_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->text('notes')->nullable();
            $table->string('slack_canvas_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('surgery_date');
            $table->index('due_date');
            $table->index('assignee_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};

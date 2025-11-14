<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->enum('type', ['photo', 'stl', 'cbct', 'scan', 'other'])->default('other');
            $table->string('label', 100)->nullable();
            $table->string('slack_file_id', 100)->nullable();
            $table->text('file_url')->nullable()->comment('Slack URL or external URL');
            $table->text('local_path')->nullable()->comment('Hostinger local storage path');
            $table->text('preview_url')->nullable()->comment('Image thumbnail URL');
            $table->string('filename')->nullable();
            $table->string('mimetype', 100)->nullable();
            $table->bigInteger('filesize')->nullable()->comment('File size in bytes');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('case_id');
            $table->index('type');
            $table->index('slack_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_attachments');
    }
};

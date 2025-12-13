<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsheet_links', function (Blueprint $table) {
            $table->id();
            $table->enum('dept', ['COCR', 'SOLIDEX', 'PRINT', 'CASES']);
            $table->string('sheet_id');
            $table->string('tab_name');
            $table->json('mapping');
            $table->enum('direction', ['PULL', 'PUSH', 'BIDI'])->default('PULL');
            $table->dateTime('last_sync_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['dept', 'active']);
        });

        Schema::create('gsync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gsheet_link_id')->nullable()->constrained('gsheet_links')->nullOnDelete();
            $table->json('payload');
            $table->enum('direction', ['PULL', 'PUSH']);
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->text('error_text')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsync_queue');
        Schema::dropIfExists('gsheet_links');
    }
};

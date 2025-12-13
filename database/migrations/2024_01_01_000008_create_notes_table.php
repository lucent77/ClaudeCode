<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['CASE', 'COCR', 'SOLIDEX', 'PRINT', 'TOOTH']);
            $table->unsignedBigInteger('scope_id');
            $table->json('tags')->nullable();
            $table->text('body');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->enum('target_dept', ['COCR', 'SOLIDEX', 'PRINT']);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['case_id', 'target_dept']);
            $table->index(['target_dept', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_routes');
    }
};

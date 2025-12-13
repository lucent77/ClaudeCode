<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->enum('dept', ['COCR', 'SOLIDEX', 'PRINT']);
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['dept', 'active']);
        });

        Schema::create('ovens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('shades', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('stage_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('dept', ['COCR', 'SOLIDEX', 'PRINT']);
            $table->string('stage');
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('enable_subtasks')->default(false);
            $table->json('allowed_fields')->nullable();
            $table->json('required_fields')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['dept', 'stage']);
            $table->index(['dept', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_configs');
        Schema::dropIfExists('shades');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('ovens');
        Schema::dropIfExists('machines');
    }
};

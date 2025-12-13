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
            $table->string('case_number')->unique();
            $table->enum('nychv', ['NYC', 'HV'])->default('NYC');
            $table->dateTime('time_stamp')->nullable();
            $table->boolean('combo')->default(false);
            $table->date('due_date')->nullable();
            $table->enum('ld', ['LAB', 'DOC'])->nullable();
            $table->string('pan', 100)->nullable();
            $table->string('lab', 255)->nullable();
            $table->string('patient', 255)->nullable();
            $table->integer('tooth_count')->default(0);
            $table->json('tooth_map')->nullable();
            $table->text('instructions')->nullable();
            $table->text('preferences')->nullable();
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'DONE', 'ON_HOLD'])->default('OPEN');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index(['nychv', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};

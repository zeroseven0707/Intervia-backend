<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Latest score per user per skill (upserted after each session)
        Schema::create('user_skill_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->decimal('confidence', 3, 2)->default(1.00); // 0.00–1.00
            $table->foreignId('source_session_id')
                  ->nullable()
                  ->constrained('interview_sessions')
                  ->nullOnDelete();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'skill_id']);
            $table->index('user_id');
        });

        // Progress history per user per skill
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('previous_score');
            $table->unsignedTinyInteger('current_score');
            $table->tinyInteger('improvement');          // can be negative
            $table->timestamp('last_assessed_at')->nullable();

            $table->index(['user_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
        Schema::dropIfExists('user_skill_scores');
    }
};

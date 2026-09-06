<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->text('job_description')->nullable();
            $table->json('job_analysis')->nullable();   // cached AI job analysis result
            $table->string('mode');                     // practice | simulation | challenging
            $table->string('difficulty')->default('adaptive');
            $table->unsignedTinyInteger('question_count')->default(8);
            $table->string('status')->default('pending'); // pending | analyzing | interviewing | evaluating | completed | failed
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};

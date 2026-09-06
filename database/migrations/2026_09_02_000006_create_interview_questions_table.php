<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                  ->constrained('interview_sessions')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->text('question_text');
            $table->string('category');             // technical | behavioral | project_experience | ...
            $table->string('difficulty');            // easy | medium | hard
            $table->string('skill')->nullable();     // primary skill being tested (free text from AI)
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->foreignId('parent_question_id')->nullable()->constrained('interview_questions')->nullOnDelete();
            $table->boolean('is_follow_up')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['session_id', 'sequence']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
    }
};

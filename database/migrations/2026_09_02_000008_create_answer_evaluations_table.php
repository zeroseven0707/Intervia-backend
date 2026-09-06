<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')
                  ->constrained('interview_answers')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score');
            $table->unsignedTinyInteger('relevance_score');
            $table->unsignedTinyInteger('knowledge_score');
            $table->unsignedTinyInteger('clarity_score');
            $table->unsignedTinyInteger('completeness_score');
            $table->unsignedTinyInteger('reasoning_score');
            $table->json('strengths');
            $table->json('weaknesses');
            $table->json('missing_points');
            $table->text('improvement_advice');
            $table->text('example_answer');
            $table->string('evaluator_version')->default('EVALUATOR_V1');
            $table->timestamp('created_at')->nullable();

            $table->unique('answer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_evaluations');
    }
};

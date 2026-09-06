<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                  ->constrained('interview_sessions')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score');
            $table->unsignedTinyInteger('technical_score')->nullable();
            $table->unsignedTinyInteger('communication_score')->nullable();
            $table->unsignedTinyInteger('problem_solving_score')->nullable();
            $table->unsignedTinyInteger('answer_structure_score')->nullable();
            $table->text('summary');
            $table->json('strengths');
            $table->json('weaknesses');
            $table->json('skill_gaps');         // aggregated skill gap array
            $table->json('recommendations');
            $table->string('report_version')->default('REPORT_V1');
            $table->timestamp('created_at')->nullable();

            $table->unique('session_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_reports');
    }
};

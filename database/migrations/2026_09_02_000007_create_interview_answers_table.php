<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                  ->constrained('interview_questions')
                  ->cascadeOnDelete();
            $table->text('answer_text');
            $table->timestamp('submitted_at')->nullable();
            $table->string('processing_status')->default('pending'); // pending | processing | done | failed

            // one answer per question
            $table->unique('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_answers');
    }
};

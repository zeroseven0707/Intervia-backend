<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_topics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_sources', function (Blueprint $table) {
            $table->id();
            $table->string('type');          // youtube | article | documentation | pdf
            $table->string('title');
            $table->string('url');
            $table->string('publisher')->nullable();
            $table->string('author')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('external_id')->nullable(); // e.g. YouTube video ID
            $table->string('status')->default('pending'); // pending | approved | rejected | archived
            $table->json('metadata')->nullable();   // thumbnails, duration, captions availability, etc.
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('learning_sources')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('learning_topics')->cascadeOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->longText('content')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('difficulty')->nullable(); // junior | mid | senior
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['topic_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('learning_sources');
        Schema::dropIfExists('learning_topics');
    }
};

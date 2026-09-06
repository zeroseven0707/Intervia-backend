<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('importance')->default('required'); // required | preferred
            $table->timestamp('created_at')->nullable();

            $table->unique(['position_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_skills');
    }
};

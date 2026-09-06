<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerEvaluation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'answer_id',
        'overall_score',
        'relevance_score',
        'knowledge_score',
        'clarity_score',
        'completeness_score',
        'reasoning_score',
        'strengths',
        'weaknesses',
        'missing_points',
        'improvement_advice',
        'example_answer',
        'evaluator_version',
    ];

    protected function casts(): array
    {
        return [
            'strengths'      => 'array',
            'weaknesses'     => 'array',
            'missing_points' => 'array',
            'created_at'     => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function answer()
    {
        return $this->belongsTo(InterviewAnswer::class, 'answer_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'overall_score',
        'technical_score',
        'communication_score',
        'problem_solving_score',
        'answer_structure_score',
        'summary',
        'strengths',
        'weaknesses',
        'skill_gaps',
        'recommendations',
        'report_version',
    ];

    protected function casts(): array
    {
        return [
            'strengths'       => 'array',
            'weaknesses'      => 'array',
            'skill_gaps'      => 'array',
            'recommendations' => 'array',
            'created_at'      => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }
}

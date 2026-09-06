<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'answer_text',
        'submitted_at',
        'processing_status',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function question()
    {
        return $this->belongsTo(InterviewQuestion::class, 'question_id');
    }

    public function evaluation()
    {
        return $this->hasOne(AnswerEvaluation::class, 'answer_id');
    }
}

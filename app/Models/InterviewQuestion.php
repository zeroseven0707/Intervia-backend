<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'sequence',
        'question_text',
        'category',
        'difficulty',
        'skill',
        'skill_id',
        'parent_question_id',
        'is_follow_up',
    ];

    protected function casts(): array
    {
        return [
            'is_follow_up' => 'boolean',
            'created_at'   => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    public function skillModel()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }

    public function parentQuestion()
    {
        return $this->belongsTo(InterviewQuestion::class, 'parent_question_id');
    }

    public function followUps()
    {
        return $this->hasMany(InterviewQuestion::class, 'parent_question_id');
    }

    public function answer()
    {
        return $this->hasOne(InterviewAnswer::class, 'question_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSkillScore extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'skill_id',
        'score',
        'confidence',
        'source_session_id',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function sourceSession()
    {
        return $this->belongsTo(InterviewSession::class, 'source_session_id');
    }
}

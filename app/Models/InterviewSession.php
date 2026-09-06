<?php

namespace App\Models;

use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'position_id',
        'job_description',
        'job_analysis',
        'mode',
        'difficulty',
        'question_count',
        'status',
        'overall_score',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'job_analysis'  => 'array',
            'mode'          => InterviewMode::class,
            'status'        => InterviewStatus::class,
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', InterviewStatus::Completed);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === InterviewStatus::Completed;
    }

    public function currentQuestionNumber(): int
    {
        return $this->questions()->count() + 1;
    }

    public function isFinished(): bool
    {
        return $this->questions()->count() >= $this->question_count;
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function questions()
    {
        return $this->hasMany(InterviewQuestion::class, 'session_id')->orderBy('sequence');
    }

    public function report()
    {
        return $this->hasOne(InterviewReport::class, 'session_id');
    }
}

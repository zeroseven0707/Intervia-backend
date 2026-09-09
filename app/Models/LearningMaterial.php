<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    protected $fillable = [
        'source_id',
        'topic_id',
        'title',
        'summary',
        'content',
        'duration_seconds',
        'difficulty',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Filter materials relevant to a skill name (via topic name match).
     */
    public function scopeForSkill($query, string $skill)
    {
        return $query->whereHas('topic', function ($q) use ($skill) {
            $q->where('name', 'like', "%{$skill}%")
              ->orWhere('slug', 'like', '%' . str($skill)->slug() . '%');
        });
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('source', fn($q) => $q->where('status', 'approved'));
    }

    public function scopeByDifficulty($query, string $seniority)
    {
        return $query->where(function ($q) use ($seniority) {
            $q->where('difficulty', $seniority)->orWhereNull('difficulty');
        });
    }

    public function scopeByDifficultyList($query, array $levels)
    {
        return $query->where(function ($q) use ($levels) {
            $q->whereIn('difficulty', $levels)->orWhereNull('difficulty');
        });
    }

    public function scopeOrderByQuality($query)
    {
        // Prefer shorter content (lower time commitment first), then by newest
        return $query->orderBy('duration_seconds')->orderByDesc('published_at');
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function source()
    {
        return $this->belongsTo(LearningSource::class, 'source_id');
    }

    public function topic()
    {
        return $this->belongsTo(LearningTopic::class, 'topic_id');
    }
}

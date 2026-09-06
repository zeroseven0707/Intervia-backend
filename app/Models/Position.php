<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'position_skills')
                    ->withPivot('importance')
                    ->using(PositionSkill::class);
    }

    public function positionSkills()
    {
        return $this->hasMany(PositionSkill::class);
    }

    public function interviewSessions()
    {
        return $this->hasMany(InterviewSession::class);
    }
}

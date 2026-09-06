<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'category', 'is_active'];

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

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_skills')
                    ->withPivot('importance');
    }

    public function userScores()
    {
        return $this->hasMany(UserSkillScore::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }
}

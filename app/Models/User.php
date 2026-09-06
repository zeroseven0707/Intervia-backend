<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'experience_level',
        'target_position_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'experience_level'  => ExperienceLevel::class,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function targetPosition()
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }

    public function interviewSessions()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function skillScores()
    {
        return $this->hasMany(UserSkillScore::class);
    }

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }
}

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
        'subscribed_until',
        'credit_sessions',
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
            'subscribed_until'  => 'datetime',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed_until !== null && $this->subscribed_until->isFuture();
    }

    public function hasSessionCredit(): bool
    {
        return $this->credit_sessions > 0;
    }

    public function canStartInterview(): bool
    {
        if ($this->isAdmin()) return true;

        $settings = PaymentSetting::getSettings();
        if (!$settings->require_payment) return true;

        if ($this->hasActiveSubscription()) return true;
        if ($this->hasSessionCredit()) return true;

        $completedCount = $this->interviewSessions()->count();
        if ($completedCount < $settings->free_trial_sessions) return true;

        return false;
    }

    public function consumeSessionCredit(): void
    {
        if ($this->credit_sessions > 0) {
            $this->decrement('credit_sessions');
        }
    }

    public function addSessions(int $count): void
    {
        $this->increment('credit_sessions', $count);
    }

    public function extendSubscription(int $days): void
    {
        $start = $this->subscribed_until && $this->subscribed_until->isFuture()
            ? $this->subscribed_until
            : now();

        $this->update(['subscribed_until' => $start->addDays($days)]);
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

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->latest();
    }
}

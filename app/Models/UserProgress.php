<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'skill_id',
        'previous_score',
        'current_score',
        'improvement',
        'last_assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_assessed_at' => 'datetime',
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
}

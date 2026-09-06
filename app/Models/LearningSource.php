<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSource extends Model
{
    protected $fillable = [
        'type',
        'title',
        'url',
        'publisher',
        'author',
        'language',
        'external_id',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class, 'source_id');
    }
}

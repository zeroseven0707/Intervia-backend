<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PositionSkill extends Pivot
{
    public $timestamps = false;

    protected $fillable = ['position_id', 'skill_id', 'importance'];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}

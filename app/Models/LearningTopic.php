<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningTopic extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'category'];

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class, 'topic_id');
    }
}

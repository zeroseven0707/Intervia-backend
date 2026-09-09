<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'session_count',
        'duration_days',
        'price',
        'discounted_price',
        'is_active',
        'is_popular',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price'            => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'is_active'        => 'boolean',
            'is_popular'       => 'boolean',
            'features'         => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function getEffectivePrice(): float
    {
        return $this->discounted_price ?? $this->price;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

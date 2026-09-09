<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_production',
        'midtrans_merchant_id',
        'default_single_session_price',
        'require_payment',
        'free_trial_sessions',
    ];

    protected function casts(): array
    {
        return [
            'midtrans_production'          => 'boolean',
            'require_payment'              => 'boolean',
            'default_single_session_price' => 'decimal:2',
        ];
    }

    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'default_single_session_price' => 3000,
            'require_payment'              => true,
            'free_trial_sessions'          => 0,
        ]);
    }
}

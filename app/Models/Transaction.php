<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'order_id',
        'midtrans_transaction_id',
        'package_name',
        'gross_amount',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'payment_link',
        'paid_at',
        'expires_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'paid_at'      => 'datetime',
            'expires_at'   => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function isPaid(): bool
    {
        return in_array($this->transaction_status, ['capture', 'settlement']);
    }

    public function isFinal(): bool
    {
        return in_array($this->transaction_status, ['capture', 'settlement', 'deny', 'cancel', 'expire']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}

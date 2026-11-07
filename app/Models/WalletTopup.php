<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopup extends Model
{
    public const GATEWAY_KASHIER = 'kashier';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'gateway',
        'merchant_order_id',
        'session_id',
        'payment_url',
        'amount',
        'currency',
        'status',
        'gateway_status',
        'transaction_id',
        'reference',
        'payment_method',
        'paid_at',
        'failed_at',
        'verified_at',
        'request_payload',
        'create_session_response',
        'callback_payload',
        'webhook_payload',
        'reconcile_payload',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'verified_at' => 'datetime',
            'request_payload' => 'array',
            'create_session_response' => 'array',
            'callback_payload' => 'array',
            'webhook_payload' => 'array',
            'reconcile_payload' => 'array',
            'meta' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
        ], true);
    }
}

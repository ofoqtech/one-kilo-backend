<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public const REASON_ORDER_PAYMENT = 'order_payment';
    public const REASON_REFUND = 'refund';
    public const REASON_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const REASON_BONUS = 'bonus';
    public const REASON_REVERSAL = 'reversal';
    public const REASON_TOPUP = 'topup';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'order_id',
        'type',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'status' => 'boolean',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

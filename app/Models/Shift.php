<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Shift extends Model
{
    protected $fillable = [
        'shiftable_type',
        'shiftable_id',
        'started_at',
        'ended_at',
        'opening_cash',
        'closing_cash',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
        ];
    }

    public function shiftable(): MorphTo
    {
        return $this->morphTo();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_shift_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function close(?float $closingCash = null): void
    {
        $this->update([
            'ended_at' => now(),
            'closing_cash' => $closingCash,
        ]);
    }
}

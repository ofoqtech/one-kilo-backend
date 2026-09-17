<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class AppPopup extends Model
{
    use HasTranslations;

    public const LINK_TYPE_NONE = 'none';
    public const LINK_TYPE_PRODUCT = 'product';
    public const LINK_TYPE_CATEGORY = 'category';
    public const LINK_TYPE_COUPON = 'coupon';
    public const LINK_TYPE_URL = 'url';

    public $translatable = [
        'title',
    ];

    protected $fillable = [
        'title',
        'image',
        'link_type',
        'link_id',
        'link_url',
        'starts_at',
        'ends_at',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function linkTypes(): array
    {
        return [
            self::LINK_TYPE_NONE,
            self::LINK_TYPE_PRODUCT,
            self::LINK_TYPE_CATEGORY,
            self::LINK_TYPE_COUPON,
            self::LINK_TYPE_URL,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeCurrentlyRunning(Builder $query, ?CarbonInterface $moment = null): Builder
    {
        $moment ??= now();

        return $query
            ->where(function (Builder $subQuery) use ($moment) {
                $subQuery->whereNull('starts_at')->orWhere('starts_at', '<=', $moment);
            })
            ->where(function (Builder $subQuery) use ($moment) {
                $subQuery->whereNull('ends_at')->orWhere('ends_at', '>=', $moment);
            });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'link_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'link_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'link_id');
    }
}

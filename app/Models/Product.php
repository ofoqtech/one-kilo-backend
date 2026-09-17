<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use Sluggable, HasTranslations;

    public $translatable = [
        'name',
        'short_description',
        'description',
    ];

    protected $fillable = [
        'category_id',
        'sort_order',
        'name',
        'slug',
        'short_description',
        'description',
        'image',
        'price',
        'discount_type',
        'discount_value',
        'discount_starts_at',
        'discount_ends_at',
        'sku',
        'stock',
        'is_featured',
        'status',
        'has_variants',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
            'stock' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'status' => 'boolean',
            'has_variants' => 'boolean',
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class, 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeSkus(): HasMany
    {
        return $this->skus()->where('status', true);
    }

    public function priceBeforeDiscount(): ?float
    {
        if ($this->price === null) {
            return null;
        }

        return round((float) $this->price, 2);
    }

    public function hasActiveDiscount(?CarbonInterface $moment = null): bool
    {
        $moment ??= now();
        $price = $this->priceBeforeDiscount();

        if ($price === null || $price <= 0) {
            return false;
        }

        return $this->discountSettingsAreActive($moment);
    }

    public function priceAfterDiscount(?CarbonInterface $moment = null): ?float
    {
        $price = $this->priceBeforeDiscount();

        if ($price === null) {
            return null;
        }

        return $this->priceAfterDiscountForPrice($price, $moment);
    }

    public function activeDiscountAmount(?CarbonInterface $moment = null): ?float
    {
        $price = $this->priceBeforeDiscount();

        if ($price === null) {
            return null;
        }

        return $this->discountAmountForPrice($price, $moment);
    }

    public function discountPercentage(?CarbonInterface $moment = null): ?float
    {
        if (! $this->hasActiveDiscount($moment)) {
            return null;
        }

        if ($this->discount_type === 'percentage') {
            return round((float) $this->discount_value, 2);
        }

        $price = $this->priceBeforeDiscount();

        if ($price === null || $price <= 0) {
            return null;
        }

        return round(($this->activeDiscountAmount($moment) / $price) * 100, 2);
    }

    public function discountSettingsAreActive(?CarbonInterface $moment = null): bool
    {
        $moment ??= now();

        $discountValue = (float) ($this->discount_value ?? 0);

        if (! in_array($this->discount_type, ['amount', 'percentage'], true)) {
            return false;
        }

        if ($discountValue <= 0) {
            return false;
        }

        if ($this->discount_type === 'percentage' && $discountValue > 100) {
            return false;
        }

        if ($this->discount_starts_at && $moment->lt($this->discount_starts_at)) {
            return false;
        }

        if ($this->discount_ends_at && $moment->gt($this->discount_ends_at)) {
            return false;
        }

        return true;
    }

    public function discountAmountForPrice(float $basePrice, ?CarbonInterface $moment = null): float
    {
        if ($basePrice <= 0 || ! $this->discountSettingsAreActive($moment)) {
            return 0.0;
        }

        $discountValue = (float) $this->discount_value;

        if ($this->discount_type === 'amount') {
            return round(min($discountValue, $basePrice), 2);
        }

        return round(min($basePrice * $discountValue / 100, $basePrice), 2);
    }

    public function priceAfterDiscountForPrice(float $basePrice, ?CarbonInterface $moment = null): float
    {
        return round(max($basePrice - $this->discountAmountForPrice($basePrice, $moment), 0), 2);
    }

    public function hasVariants(): bool
    {
        return $this->has_variants;
    }

    public function isSimple(): bool
    {
        return ! $this->hasVariants();
    }

    public function isVariantProduct(): bool
    {
        return $this->hasVariants();
    }

    public function requiresVariantSelection(): bool
    {
        return $this->hasVariants();
    }

    public function minVariantPrice(): ?float
    {
        if (! $this->hasVariants()) {
            return $this->priceAfterDiscount();
        }

        $minPrice = $this->getAttribute('active_skus_min_price');

        if ($minPrice === null) {
            $minPrice = $this->relationLoaded('activeSkus')
                ? $this->activeSkus->min('price')
                : $this->activeSkus()->min('price');
        }

        return $minPrice !== null
            ? $this->priceAfterDiscountForPrice((float) $minPrice)
            : null;
    }

    public function maxVariantPrice(): ?float
    {
        if (! $this->hasVariants()) {
            return $this->priceAfterDiscount();
        }

        $maxPrice = $this->getAttribute('active_skus_max_price');

        if ($maxPrice === null) {
            $maxPrice = $this->relationLoaded('activeSkus')
                ? $this->activeSkus->max('price')
                : $this->activeSkus()->max('price');
        }

        return $maxPrice !== null
            ? $this->priceAfterDiscountForPrice((float) $maxPrice)
            : null;
    }
}

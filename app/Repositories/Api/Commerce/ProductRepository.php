<?php

namespace App\Repositories\Api\Commerce;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    public function paginateForIndex(array $filters, ?int $userId = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->select('products.*')
            ->with(['category.parent:id,slug', 'images'])
            ->withMin('activeSkus', 'price')
            ->withMax('activeSkus', 'price');

        $this->attachFavoriteState($query, $userId);
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? 'latest');

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function findActiveBySlug(string $slug, ?int $userId = null): Product
    {
        $query = Product::query()
            ->select('products.*')
            ->active()
            ->with(['category.parent:id,slug', 'images'])
            ->where('slug', $slug);

        $this->attachFavoriteState($query, $userId);

        $product = $query->firstOrFail();

        if ($product->hasVariants()) {
            $product->load([
                'activeSkus' => fn($skuQuery) => $skuQuery
                    ->with(['product', 'items.variant', 'items.item'])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        return $product;
    }

    public function findActiveBySlugForCart(string $slug): Product
    {
        return Product::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function findActiveSkuForProduct(int $skuId, int $productId): ProductSku
    {
        return ProductSku::query()
            ->whereKey($skuId)
            ->where('product_id', $productId)
            ->where('status', true)
            ->with(['product', 'items.variant', 'items.item'])
            ->firstOrFail();
    }

    public function paginateBestSelling(array $filters, ?int $userId = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->select('products.*')
            ->selectRaw('SUM(order_items.quantity) as sold_quantity')
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->active()
            ->with('category.parent:id,slug')
            ->withMin('activeSkus', 'price')
            ->withMax('activeSkus', 'price')
            ->whereIn('orders.status', Order::salesStatuses())
            ->groupBy('products.id')
            ->orderByDesc('sold_quantity')
            ->orderByDesc('products.id');

        $this->attachFavoriteState($query, $userId);

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    private function attachFavoriteState(Builder $query, ?int $userId): void
    {
        if ($userId) {
            $query->withExists([
                'favorites as is_favorite' => fn(Builder $favoriteQuery) => $favoriteQuery->where('user_id', $userId),
            ]);

            return;
        }

        $query->selectRaw('0 as is_favorite');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (array_key_exists('status', $filters)) {
            $query->where('status', $filters['status'] ?? 1);
        }

        if (! empty($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $searchQuery) use ($filters) {
                $searchQuery
                    ->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('short_description', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (array_key_exists('is_featured', $filters)) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (array_key_exists('in_stock', $filters)) {
            $query->where(function (Builder $subQuery) use ($filters) {
                if ($filters['in_stock']) {
                    $subQuery
                        ->where(function (Builder $simpleQuery) {
                            $simpleQuery->where('has_variants', false)->where('stock', '>', 0);
                        })
                        ->orWhere(function (Builder $variantQuery) {
                            $variantQuery
                                ->where('has_variants', true)
                                ->whereExists(function ($existsQuery) {
                                    $existsQuery
                                        ->selectRaw('1')
                                        ->from('product_skus')
                                        ->whereColumn('product_skus.product_id', 'products.id')
                                        ->where('product_skus.status', true)
                                        ->where('product_skus.quantity', '>', 0);
                                });
                        });

                    return;
                }

                $subQuery
                    ->where(function (Builder $simpleQuery) {
                        $simpleQuery->where('has_variants', false)->where('stock', '<=', 0);
                    })
                    ->orWhere(function (Builder $variantQuery) {
                        $variantQuery
                            ->where('has_variants', true)
                            ->whereNotExists(function ($existsQuery) {
                                $existsQuery
                                    ->selectRaw('1')
                                    ->from('product_skus')
                                    ->whereColumn('product_skus.product_id', 'products.id')
                                    ->where('product_skus.status', true)
                                    ->where('product_skus.quantity', '>', 0);
                            });
                    });
            });
        }

        if (array_key_exists('has_discount', $filters)) {
            [$discountSql, $discountBindings] = $this->activeDiscountSql();

            $filters['has_discount']
                ? $query->whereRaw($discountSql, $discountBindings)
                : $query->whereRaw('NOT (' . $discountSql . ')', $discountBindings);
        }

        if (array_key_exists('min_price', $filters)) {
            [$priceSql, $bindings] = $this->effectivePriceSql();
            $query->whereRaw('(' . $priceSql . ') >= ?', [...$bindings, $filters['min_price']]);
        }

        if (array_key_exists('max_price', $filters)) {
            [$priceSql, $bindings] = $this->effectivePriceSql();
            $query->whereRaw('(' . $priceSql . ') <= ?', [...$bindings, $filters['max_price']]);
        }
    }

    private function applySorting(Builder $query, string $sort): void
    {
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            [$priceSql, $bindings] = $this->effectivePriceSql();

            $query->orderByRaw(
                $priceSql . ' ' . ($sort === 'price_asc' ? 'asc' : 'desc'),
                $bindings
            );

            return;
        }

        match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query
                ->orderByRaw('CASE WHEN products.sort_order IS NULL THEN 1 ELSE 0 END')
                ->orderBy('products.sort_order')
                ->latest(),
        };
    }

    private function activeDiscountSql(): array
    {
        $now = now()->toDateTimeString();

        return [
            "discount_type IS NOT NULL
            AND discount_value IS NOT NULL
            AND discount_value > 0
            AND (discount_starts_at IS NULL OR discount_starts_at <= ?)
            AND (discount_ends_at IS NULL OR discount_ends_at >= ?)",
            [$now, $now],
        ];
    }

    private function effectivePriceSql(): array
    {
        [$activeDiscountSql, $bindings] = $this->activeDiscountSql();

        $minSkuPriceSql = "(SELECT MIN(product_skus.price)
            FROM product_skus
            WHERE product_skus.product_id = products.id
                AND product_skus.status = 1)";

        $basePriceSql = "CASE
            WHEN has_variants = 1 THEN {$minSkuPriceSql}
            ELSE price
        END";

        $sql = "CASE
            WHEN ({$activeDiscountSql}) AND discount_type = 'amount'
                THEN CASE WHEN {$basePriceSql} - discount_value < 0 THEN 0 ELSE {$basePriceSql} - discount_value END
            WHEN ({$activeDiscountSql}) AND discount_type = 'percentage'
                THEN CASE WHEN {$basePriceSql} - ({$basePriceSql} * discount_value / 100) < 0 THEN 0 ELSE {$basePriceSql} - ({$basePriceSql} * discount_value / 100) END
            ELSE {$basePriceSql}
        END";

        return [$sql, [...$bindings, ...$bindings]];
    }
}

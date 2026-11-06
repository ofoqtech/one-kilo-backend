<?php

namespace App\Services\Api\Commerce;

use App\Exceptions\ApiBusinessException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\Api\Commerce\CartRepository;
use App\Repositories\Api\Commerce\CouponRepository;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected ProductService $productService,
        protected CouponRepository $couponRepository
    ) {}

    public function current(int $userId): Cart
    {
        $cart = $this->cartRepository->getOrCreateForUser($userId);

        return $this->syncCouponState($cart, $userId);
    }

    public function addItem(int $userId, string $productSlug, int $quantity, ?int $skuId = null): Cart
    {
        $product = $this->productService->findActiveBySlugForCart($productSlug);
        $cart = $this->cartRepository->getOrCreateForUser($userId);

        if ($product->hasVariants()) {
            if (! $skuId) {
                throw new ApiBusinessException(
                    __('front.sku-required'),
                    422,
                    ['sku_id' => [__('front.sku-required')]]
                );
            }

            $sku = $this->productService->findActiveSkuForProduct($skuId, $product->id);
            $existingQuantity = $cart->items
                ->firstWhere('product_sku_id', $skuId)?->quantity ?? 0;

            $this->assertSkuIsPurchasable($sku, $existingQuantity + $quantity);
            $this->cartRepository->addOrIncrementSkuItem($cart, $product, $sku, $quantity);
        } else {
            if ($skuId) {
                throw new ApiBusinessException(
                    __('front.sku-not-allowed'),
                    422,
                    ['sku_id' => [__('front.sku-not-allowed')]]
                );
            }

            $existingQuantity = $cart->items
                ->firstWhere('product_id', $product->id)?->quantity ?? 0;

            $this->assertProductIsPurchasable($product, $existingQuantity + $quantity);
            $this->cartRepository->addOrIncrementItem($cart, $product, $quantity);
        }

        return $this->syncCouponState(
            $this->cartRepository->getOrCreateForUser($userId),
            $userId
        );
    }

    public function updateItem(int $userId, int $itemId, int $quantity): Cart
    {
        $item = $this->cartRepository->findUserItem($userId, $itemId);

        if ($item->product_sku_id) {
            $sku = $item->relationLoaded('sku') ? $item->sku : $item->sku()->with('product')->first();

            if (! $sku) {
                throw new ApiBusinessException(
                    __('front.cart-sku-not-available'),
                    422,
                    ['sku_id' => [__('front.cart-sku-not-available')]]
                );
            }

            $this->assertSkuIsPurchasable($sku, $quantity);
        } else {
            $this->assertProductIsPurchasable($item->product, $quantity);
        }

        $this->cartRepository->updateItemQuantity($item, $quantity);

        return $this->syncCouponState(
            $this->cartRepository->getOrCreateForUser($userId),
            $userId
        );
    }

    public function removeItem(int $userId, int $itemId): Cart
    {
        $item = $this->cartRepository->findUserItem($userId, $itemId);
        $this->cartRepository->removeItem($item);

        return $this->syncCouponState(
            $this->cartRepository->getOrCreateForUser($userId),
            $userId
        );
    }

    public function clear(int $userId): Cart
    {
        $cart = $this->cartRepository->getOrCreateForUser($userId);
        $this->cartRepository->clear($cart);

        return $this->cartRepository->getOrCreateForUser($userId);
    }

    public function applyCoupon(int $userId, string $code): Cart
    {
        $cart = $this->cartRepository->getOrCreateForUser($userId);
        $coupon = $this->couponRepository->findByCode($code);

        if (! $coupon) {
            throw new ApiBusinessException(
                __('front.invalid-or-expired-coupon-code'),
                422,
                ['code' => [__('front.invalid-or-expired-coupon-code')]]
            );
        }

        $this->assertCouponCanBeApplied($coupon, $cart, $userId);
        $this->cartRepository->setCoupon($cart, $coupon->id);

        return $this->cartRepository->getOrCreateForUser($userId);
    }

    public function removeCoupon(int $userId): Cart
    {
        $cart = $this->cartRepository->getOrCreateForUser($userId);
        $this->cartRepository->setCoupon($cart, null);

        return $this->cartRepository->getOrCreateForUser($userId);
    }

    private function assertProductIsPurchasable(Product $product, int $requestedQuantity): void
    {
        if (! $product->status) {
            throw new ApiBusinessException(
                __('front.cart-product-not-available'),
                422,
                ['product' => [__('front.cart-product-not-available')]]
            );
        }

        if ($product->stock < 1) {
            throw new ApiBusinessException(
                __('front.cart-product-out-of-stock'),
                422,
                ['product' => [__('front.cart-product-out-of-stock')]]
            );
        }

        if ($requestedQuantity > $product->stock) {
            throw new ApiBusinessException(
                __('front.cart-insufficient-stock'),
                422,
                ['quantity' => [__('front.cart-insufficient-stock')]]
            );
        }
    }

    private function assertSkuIsPurchasable(ProductSku $sku, int $requestedQuantity): void
    {
        if (! $sku->status) {
            throw new ApiBusinessException(
                __('front.cart-sku-not-available'),
                422,
                ['sku_id' => [__('front.cart-sku-not-available')]]
            );
        }

        if ($sku->quantity < 1) {
            throw new ApiBusinessException(
                __('front.cart-sku-out-of-stock'),
                422,
                ['sku_id' => [__('front.cart-sku-out-of-stock')]]
            );
        }

        if ($requestedQuantity > $sku->quantity) {
            throw new ApiBusinessException(
                __('front.cart-insufficient-sku-stock'),
                422,
                ['quantity' => [__('front.cart-insufficient-sku-stock')]]
            );
        }
    }

    private function assertCouponCanBeApplied(Coupon $coupon, Cart $cart, int $userId): void
    {
        if ($cart->itemsCount() === 0) {
            throw new ApiBusinessException(
                __('front.cart-empty'),
                422,
                ['cart' => [__('front.cart-empty')]]
            );
        }

        if (! $coupon->isActive()) {
            throw new ApiBusinessException(
                __('front.invalid-or-expired-coupon-code'),
                422,
                ['code' => [__('front.invalid-or-expired-coupon-code')]]
            );
        }

        if (! $coupon->hasRemainingUsage()) {
            throw new ApiBusinessException(
                __('front.coupon-usage-limit-reached'),
                422,
                ['code' => [__('front.coupon-usage-limit-reached')]]
            );
        }

        if (
            $coupon->usage_limit_per_user !== null
            && $this->couponRepository->userUsageCount($coupon->id, $userId) >= $coupon->usage_limit_per_user
        ) {
            throw new ApiBusinessException(
                __('front.coupon-user-usage-limit-reached'),
                422,
                ['code' => [__('front.coupon-user-usage-limit-reached')]]
            );
        }

        if (! $coupon->canApplyToSubtotal($cart->subtotal())) {
            throw new ApiBusinessException(
                __('front.coupon-minimum-order-not-met'),
                422,
                ['code' => [__('front.coupon-minimum-order-not-met')]]
            );
        }

        $cartCategoryIds = $cart->items->pluck('product.category_id')->filter()->unique()->values()->all();

        if (! $coupon->canApplyToCategories($cartCategoryIds)) {
            throw new ApiBusinessException(
                __('front.coupon-not-applicable-to-cart-items'),
                422,
                ['code' => [__('front.coupon-not-applicable-to-cart-items')]]
            );
        }
    }

    private function syncCouponState(Cart $cart, int $userId): Cart
    {
        if (! $cart->coupon) {
            return $cart;
        }

        $shouldRemoveCoupon = $cart->subtotal() <= 0
            || ! $cart->coupon->canApplyToSubtotal($cart->subtotal())
            || ($cart->coupon->usage_limit_per_user !== null
                && $this->couponRepository->userUsageCount($cart->coupon->id, $userId) >= $cart->coupon->usage_limit_per_user);

        if (! $shouldRemoveCoupon) {
            return $cart;
        }

        $this->cartRepository->setCoupon($cart, null);

        return $this->cartRepository->getOrCreateForUser($userId);
    }
}

<?php

namespace App\Services\Api\Commerce;

use App\Exceptions\ApiBusinessException;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Repositories\Api\Commerce\AddressRepository;
use App\Repositories\Api\Commerce\CartRepository;
use App\Repositories\Api\Commerce\CouponRepository;
use App\Repositories\Api\Commerce\OrderRepository;
use App\Repositories\Api\Commerce\WorkingHoursRepository;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected WorkingHoursRepository $workingHoursRepository,
        protected AddressRepository $addressRepository,
        protected CouponRepository $couponRepository,
        protected OrderRepository $orderRepository,
        protected WalletService $walletService,
        protected CardPaymentService $cardPaymentService
    ) {}

    public function checkout(int $userId, array $data): array
    {
        return DB::transaction(function () use ($userId, $data) {
            $address = $this->addressRepository->findActiveForUser($userId, (int) $data['address_id']);
            $cart = $this->cartRepository->findForUser($userId);

          $open_status = $this->workingHoursRepository->checkStatus();

            if (! $cart || $cart->itemsCount() === 0) {
                throw new ApiBusinessException(
                    __('front.cart-empty'),
                    422,
                    ['cart' => [__('front.cart-empty')]]
                );
            }

            if(!$open_status){
                throw new ApiBusinessException(
                    __('front.close-time'),
                    422,
                    ['close' => [__('front.close-time')]]
                );
            }

            $this->assertAddressIsReadyForCheckout($address);
            $this->assertCartIsReadyForCheckout($cart);

            $coupon = $this->resolveCheckoutCoupon($cart, $userId, $address);
            $totals = $this->calculateTotals($cart, $address, $coupon);
            $order = $this->createOrder($userId, $address, $cart, $coupon, $totals, $data);

            $this->orderRepository->createItems($order, $this->buildOrderItemsPayload($cart));

            if ($order->payment_method !== Order::PAYMENT_METHOD_CARD) {
                $this->deductStock($cart);
            }

            $wallet = null;

            if ($order->payment_method === Order::PAYMENT_METHOD_CARD) {
                $order = $this->orderRepository->update($order, [
                    'payment_url' => $this->cardPaymentService->generatePaymentUrl($order),
                ]);
            }

            if ($order->payment_method === Order::PAYMENT_METHOD_WALLET) {
                $walletTransaction = $this->walletService->debitForOrder($userId, $order, $totals['total']);
                $order = $this->orderRepository->update($order, [
                    'wallet_transaction_id' => $walletTransaction->id,
                ]);
                $wallet = $this->walletService->refreshWallet($walletTransaction->wallet);
            }

            if ($coupon && $this->shouldConsumeCouponOnPlacement($order->payment_method)) {
                $this->couponRepository->registerUsage($coupon, $userId, $order->id, $totals['discount_amount']);
            }

            if ($order->payment_method !== Order::PAYMENT_METHOD_CARD) {
                $this->cartRepository->clear($cart);
            }

            return [
                'order' => $this->orderRepository->loadDetails($order->fresh()),
                'wallet' => $wallet,
            ];
        });
    }

    private function createOrder(
        int $userId,
        Address $address,
        Cart $cart,
        ?Coupon $coupon,
        array $totals,
        array $data
    ): Order {
        $now = now();
        $state = $this->resolveOrderState($data['payment_method'], $now);

        return $this->orderRepository->create([
            'user_id' => $userId,
            'address_id' => $address->id,
            'coupon_id' => $coupon?->id,
            'order_number' => $this->orderRepository->generateOrderNumber(),
            'status' => $state['status'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $state['payment_status'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount_amount'],
            'delivery_fee' => $totals['delivery_fee'],
            'total' => $totals['total'],
            'notes' => $data['notes'] ?? null,
            'placed_at' => $now,
            'paid_at' => $state['paid_at'],
            'meta' => [
                'address_snapshot' => $this->buildAddressSnapshot($address),
                'coupon_snapshot' => $coupon ? [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_amount' => $totals['discount_amount'],
                ] : null,
                // For card payments, we defer stock deduction and cart clearing until payment is confirmed.
                // This flag makes the post-payment finalization step safe and idempotent, and prevents
                // double-deducting stock for legacy orders created before this rule existed.
                'card_payment_finalization' => $data['payment_method'] === Order::PAYMENT_METHOD_CARD
                    ? ['required' => true]
                    : null,
            ],
        ]);
    }

    private function resolveOrderState(string $paymentMethod, $now): array
    {
        return match ($paymentMethod) {
            Order::PAYMENT_METHOD_CASH_ON_DELIVERY => [
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'paid_at' => null,
            ],
            Order::PAYMENT_METHOD_CARD => [
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'paid_at' => null,
            ],
            Order::PAYMENT_METHOD_WALLET => [
                'status' => Order::STATUS_CONFIRMED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'paid_at' => $now,
            ],
            default => throw new ApiBusinessException(
                __('front.invalid-payment-method'),
                422,
                ['payment_method' => [__('front.invalid-payment-method')]]
            ),
        };
    }

    private function shouldConsumeCouponOnPlacement(string $paymentMethod): bool
    {
        return in_array($paymentMethod, [
            Order::PAYMENT_METHOD_CASH_ON_DELIVERY,
            Order::PAYMENT_METHOD_WALLET,
        ], true);
    }

    private function buildOrderItemsPayload(Cart $cart): array
    {
        return $cart->items
            ->map(function (CartItem $item) {
                $skuLabel = $item->product_sku_id
                    ? $item->sku?->label()
                    : null;

                return [
                    'product_id' => $item->product_id,
                    'product_sku_id' => $item->product_sku_id,
                    'sku_label' => $skuLabel,
                    'product_name' => $item->product?->name ?? '',
                    'product_image' => $item->product?->image,
                    'unit_price' => $item->unitPrice(),
                    'quantity' => (int) $item->quantity,
                    'line_total' => $item->lineTotal(),
                ];
            })
            ->all();
    }

    private function calculateTotals(Cart $cart, Address $address, ?Coupon $coupon): array
    {
        $subtotal = round($cart->subtotal(), 2);
        $discountAmount = $coupon ? $coupon->calculateDiscount($subtotal) : 0.0;
        $deliveryFeeBeforeDiscount = round((float) ($address->region?->shipping_price ?? 0), 2);
        $deliveryDiscount = $coupon ? $coupon->calculateDeliveryDiscount($deliveryFeeBeforeDiscount) : 0.0;
        $deliveryFee = round(max($deliveryFeeBeforeDiscount - $deliveryDiscount, 0), 2);
        $total = round(max($subtotal - $discountAmount + $deliveryFee, 0), 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'delivery_fee' => $deliveryFee,
            'delivery_fee_before_discount' => $deliveryFeeBeforeDiscount,
            'delivery_discount' => $deliveryDiscount,
            'total' => $total,
        ];
    }

    private function resolveCheckoutCoupon(Cart $cart, int $userId, Address $address): ?Coupon
    {
        if (! $cart->coupon_id) {
            return null;
        }

        $coupon = $this->couponRepository->lockById($cart->coupon_id);

        if (! $coupon || ! $coupon->isActive()) {
            throw new ApiBusinessException(
                __('front.invalid-or-expired-coupon-code'),
                422,
                ['coupon' => [__('front.invalid-or-expired-coupon-code')]]
            );
        }

        if (! $coupon->hasRemainingUsage()) {
            throw new ApiBusinessException(
                __('front.coupon-usage-limit-reached'),
                422,
                ['coupon' => [__('front.coupon-usage-limit-reached')]]
            );
        }

        if (
            $coupon->usage_limit_per_user !== null
            && $this->couponRepository->userUsageCount($coupon->id, $userId) >= $coupon->usage_limit_per_user
        ) {
            throw new ApiBusinessException(
                __('front.coupon-user-usage-limit-reached'),
                422,
                ['coupon' => [__('front.coupon-user-usage-limit-reached')]]
            );
        }

        if (! $coupon->canApplyToSubtotal($cart->subtotal())) {
            throw new ApiBusinessException(
                __('front.coupon-minimum-order-not-met'),
                422,
                ['coupon' => [__('front.coupon-minimum-order-not-met')]]
            );
        }

        if (! $coupon->canApplyToRegion($address->region_id)) {
            throw new ApiBusinessException(
                __('front.coupon-not-available-in-region'),
                422,
                ['coupon' => [__('front.coupon-not-available-in-region')]]
            );
        }

        $cartCategoryIds = $cart->items->pluck('product.category_id')->filter()->unique()->values()->all();

        if (! $coupon->canApplyToCategories($cartCategoryIds)) {
            throw new ApiBusinessException(
                __('front.coupon-not-applicable-to-cart-items'),
                422,
                ['coupon' => [__('front.coupon-not-applicable-to-cart-items')]]
            );
        }

        return $coupon;
    }

    private function assertAddressIsReadyForCheckout(Address $address): void
    {
        $locationMismatch = ! $address->country
            || ! $address->country->status
            || ! $address->governorate
            || ! $address->governorate->status
            || (int) $address->governorate->country_id !== (int) $address->country_id
            || ! $address->region
            || ! $address->region->status
            || (int) $address->region->governorate_id !== (int) $address->governorate_id;

        if (! $locationMismatch) {
            return;
        }

        throw new ApiBusinessException(
            __('front.invalid-address-location'),
            422,
            ['address_id' => [__('front.invalid-address-location')]]
        );
    }

    private function assertCartIsReadyForCheckout(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            if (! $item->product || ! $item->product->status) {
                throw new ApiBusinessException(
                    __('front.cart-product-not-available'),
                    422,
                    ['product' => [__('front.cart-product-not-available')]]
                );
            }

            if ($item->product->hasVariants() && ! $item->product_sku_id) {
                throw new ApiBusinessException(
                    __('front.sku-required'),
                    422,
                    ['sku_id' => [__('front.sku-required')]]
                );
            }

            if (! $item->product->hasVariants() && $item->product_sku_id) {
                throw new ApiBusinessException(
                    __('front.sku-not-allowed'),
                    422,
                    ['sku_id' => [__('front.sku-not-allowed')]]
                );
            }

            if ((int) $item->quantity < 1) {
                throw new ApiBusinessException(
                    __('front.invalid-cart-item-quantity'),
                    422,
                    ['quantity' => [__('front.invalid-cart-item-quantity')]]
                );
            }

            if ($item->product_sku_id) {
                if (! $item->sku || ! $item->sku->status || (int) $item->sku->product_id !== (int) $item->product_id) {
                    throw new ApiBusinessException(
                        __('front.cart-sku-not-available'),
                        422,
                        ['sku_id' => [__('front.cart-sku-not-available')]]
                    );
                }

                $availableStock = (int) $item->sku->quantity;
            } else {
                $availableStock = (int) $item->product->stock;
            }

            if ($availableStock < 1) {
                throw new ApiBusinessException(
                    $item->product_sku_id
                        ? __('front.cart-sku-out-of-stock')
                        : __('front.cart-product-out-of-stock'),
                    422,
                    $item->product_sku_id
                        ? ['sku_id' => [__('front.cart-sku-out-of-stock')]]
                        : ['product' => [__('front.cart-product-out-of-stock')]]
                );
            }

            if ((int) $item->quantity > $availableStock) {
                throw new ApiBusinessException(
                    $item->product_sku_id
                        ? __('front.cart-insufficient-sku-stock')
                        : __('front.cart-insufficient-stock'),
                    422,
                    ['quantity' => [$item->product_sku_id ? __('front.cart-insufficient-sku-stock') : __('front.cart-insufficient-stock')]]
                );
            }
        }
    }

    private function buildAddressSnapshot(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'contact_name' => $address->contact_name,
            'phone' => $address->phone,
            'country_id' => $address->country_id,
            'country_name' => $address->country?->name,
            'governorate_id' => $address->governorate_id,
            'governorate_name' => $address->governorate?->name,
            'region_id' => $address->region_id,
            'region_name' => $address->region?->name,
            'region_shipping_price' => $address->region ? round((float) $address->region->shipping_price, 2) : null,
            'city' => $address->city,
            'area' => $address->area,
            'street' => $address->street,
            'building_number' => $address->building_number,
            'floor' => $address->floor,
            'apartment_number' => $address->apartment_number,
            'landmark' => $address->landmark,
            'latitude' => $address->latitude !== null ? (float) $address->latitude : null,
            'longitude' => $address->longitude !== null ? (float) $address->longitude : null,
            'full_address' => $address->fullAddress(),
        ];
    }

    private function deductStock(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            if ($item->product_sku_id) {
                $item->sku->decrement('quantity', $item->quantity);
            } else {
                $item->product->decrement('stock', $item->quantity);
            }
        }
    }
}

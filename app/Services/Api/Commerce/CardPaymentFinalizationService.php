<?php

namespace App\Services\Api\Commerce;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\Api\Commerce\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardPaymentFinalizationService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected FirebaseService $firebaseService,
        protected OrderAnnouncementService $announcementService
    ) {
    }

    public function finalizeSuccessfulCardPayment(Order $order, Payment $payment): void
    {
        if ($order->payment_method !== Order::PAYMENT_METHOD_CARD) {
            return;
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            return;
        }

        $now = now();
        $finalized = false;

        DB::transaction(function () use ($order, $payment, $now, &$finalized) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_method !== Order::PAYMENT_METHOD_CARD) {
                return;
            }

            if ($lockedOrder->payment_status !== Order::PAYMENT_STATUS_PAID) {
                return;
            }

            if (! $this->shouldFinalize($lockedOrder)) {
                return;
            }

            $items = $lockedOrder->items()
                ->get(['id', 'product_id', 'product_sku_id', 'quantity']);

            $this->deductStockForItems($items);
            $this->clearUserCart((int) $lockedOrder->user_id);

            $meta = is_array($lockedOrder->meta) ? $lockedOrder->meta : [];
            $meta['card_payment_finalization'] = [
                'required' => true,
                'finalized_at' => $now->toIso8601String(),
                'payment_id' => $payment->id,
                'gateway' => $payment->gateway,
                'session_id' => $payment->session_id,
            ];

            $lockedOrder->update([
                'meta' => $meta,
            ]);

            Log::channel((string) config('kashier.log_channel', 'stack'))
                ->info('Card order finalized after successful payment', [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'payment_id' => $payment->id,
                    'gateway' => $payment->gateway,
                ]);

            $finalized = true;
        });

        // Only notify/announce once, the first time this order actually gets
        // finalized (shouldFinalize() above makes this block idempotent
        // across repeated webhook/reconciliation calls for the same order).
        // This is also the first time a card order becomes visible to the
        // dashboard — it was deliberately withheld at creation time (see
        // OrderObserver) while payment was still unconfirmed.
        if ($finalized) {
            $this->notifyCustomerOrderReceived($order);
            $this->announcementService->announce($order->fresh());
        }
    }

    private function notifyCustomerOrderReceived(Order $order): void
    {
        try {
            $token = $order->user?->fcm_token;

            if (! $token) {
                return;
            }

            $this->firebaseService->sendNotification(
                $token,
                'تم استلام طلبك بنجاح',
                'رقم الطلب #' . $order->order_number,
            );
        } catch (\Throwable $e) {
            Log::channel((string) config('kashier.log_channel', 'stack'))
                ->warning('Failed to send order-received push notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
        }
    }

    protected function shouldFinalize(Order $order): bool
    {
        if ($this->isOrderFinalized($order)) {
            return false;
        }

        return data_get($order->meta, 'card_payment_finalization.required') === true;
    }

    protected function isOrderFinalized(Order $order): bool
    {
        $finalizedAt = data_get($order->meta, 'card_payment_finalization.finalized_at');

        return is_string($finalizedAt) && trim($finalizedAt) !== '';
    }

    /**
     * @param  iterable<int, OrderItem>  $items
     */
    protected function deductStockForItems(iterable $items): void
    {
        foreach ($items as $item) {
            $quantity = (int) $item->quantity;

            if ($quantity < 1) {
                continue;
            }

            if ($item->product_sku_id) {
                ProductSku::query()
                    ->whereKey($item->product_sku_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->decrement('quantity', $quantity);
            } else {
                Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->decrement('stock', $quantity);
            }
        }
    }

    protected function clearUserCart(int $userId): void
    {
        $cart = Cart::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (! $cart) {
            return;
        }

        $this->cartRepository->clear($cart);
    }
}

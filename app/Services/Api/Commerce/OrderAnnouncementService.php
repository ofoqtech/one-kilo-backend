<?php

namespace App\Services\Api\Commerce;

use App\Events\OrderCreatedEvent;
use App\Models\Order;
use Kreait\Firebase\Factory;

class OrderAnnouncementService
{
    /**
     * Makes a new order visible to the dashboard: pushes it to the Firebase
     * RTDB feed and broadcasts the "order.created" event. Call this the
     * moment an order should first appear to staff — immediately for
     * cash/wallet orders, but only once payment is confirmed for card
     * orders (see OrderObserver and CardPaymentFinalizationService).
     */
    public function announce(Order $order): void
    {
        $factory = (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'))
            ->withDatabaseUri('https://one-kilo-d1a84-default-rtdb.firebaseio.com');

        $database = $factory->createDatabase();

        $database->getReference('orders/' . $order->id)
            ->set([
                'id' => $order->order_number,
                'user_name' => $order->user->name,
                'total' => $order->total,
                'created_at' => $order->created_at->toDateTimeString(),
            ]);

        OrderCreatedEvent::dispatch($order);
    }

    public function shouldAnnounceOnCreation(Order $order): bool
    {
        if ($order->payment_method === Order::PAYMENT_METHOD_CARD) {
            return $order->payment_status === Order::PAYMENT_STATUS_PAID;
        }

        return true;
    }
}

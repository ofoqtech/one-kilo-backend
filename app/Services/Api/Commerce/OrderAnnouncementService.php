<?php

namespace App\Services\Api\Commerce;

use App\Events\OrderCreatedEvent;
use App\Models\Order;
use Kreait\Firebase\Factory;

class OrderAnnouncementService
{
    /**
     * Makes a new order visible to the dashboard: pushes it to the Firebase
     * RTDB feed and broadcasts the "order.created" event. Call this only
     * once the order is durably committed and actually paid/placed — for
     * cash and wallet orders that's right after checkout() commits (see
     * CheckoutService), and for card orders it's once payment is confirmed
     * (see CardPaymentFinalizationService). Never call this from inside a
     * transaction that could still roll back: both the broadcast and the
     * Firebase write fire immediately and can't be undone.
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
}

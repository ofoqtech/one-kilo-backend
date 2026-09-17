<?php

namespace App\Observers;

use App\Events\OrderCreatedEvent;
use App\Models\Order;
use Kreait\Firebase\Factory;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order)
    {
        // Fire Event
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

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}

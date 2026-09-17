<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(Order $order)
    {
        $this->data = [
            'id' => $order->id,
            'order_number' => $order->order_number ?? $order->id,
            'customer_name' => $order->user->name ?? 'Customer',
            'status' => $order->status,
            'total' => $order->total ?? 0,
            'created_at' => now()->timestamp,
            'message' => 'تم انشاء طلب جديد #' . ($order->order_number ?? $order->id),
        ];
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('orders-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}

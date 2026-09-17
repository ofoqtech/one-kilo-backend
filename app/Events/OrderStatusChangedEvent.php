<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(Order $order, string $oldStatus)
    {
        $this->data = [
            'id' => $order->id,
            'order_number' => $order->order_number ?? $order->id,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'updated_at' => now()->timestamp,
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
        return 'order.status-changed';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}

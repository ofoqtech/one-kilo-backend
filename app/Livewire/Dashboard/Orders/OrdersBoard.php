<?php

namespace App\Livewire\Dashboard\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class OrdersBoard extends Component
{
    public string $activeTab = 'incoming';

    public int $incomingPerPage = 10;

    public int $inDeliveryPerPage = 10;

    public int $completedPerPage = 10;

    public int $canceledPerPage = 10;

    protected function statusGroups(): array
    {
        return [
            'incoming' => [Order::STATUS_PENDING, Order::STATUS_AWAITING_PAYMENT, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY],
            'in_delivery' => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_PICKED_UP],
            'completed' => [Order::STATUS_DELIVERED],
            'canceled' => [Order::STATUS_CANCELED, Order::STATUS_FAILED],
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[On('echo:orders-dashboard,order.created')]
    #[On('echo:orders-dashboard,order.status-changed')]
    public function refreshBoard(): void
    {
        // no-op: triggers a re-render since this is a stateless computed render
    }

    protected function countsByGroup(): array
    {
        $counts = [];

        foreach ($this->statusGroups() as $group => $statuses) {
            $counts[$group] = Order::query()->whereIn('status', $statuses)->count();
        }

        return $counts;
    }

    protected function ordersForGroup(string $group, int $perPage)
    {
        $statuses = $this->statusGroups()[$group] ?? [];

        return Order::query()
            ->whereIn('status', $statuses)
            ->with([
                'user:id,name,phone',
                'delivery:id,full_name,phone',
            ])
            ->withCount('items')
            ->latest('placed_at')
            ->latest('id')
            ->paginate($perPage, ['*'], $group . 'Page');
    }

    public function render()
    {
        return view('dashboard.orders.orders-board', [
            'counts' => $this->countsByGroup(),
            'incomingOrders' => $this->activeTab === 'incoming' ? $this->ordersForGroup('incoming', $this->incomingPerPage) : null,
            'inDeliveryOrders' => $this->activeTab === 'in_delivery' ? $this->ordersForGroup('in_delivery', $this->inDeliveryPerPage) : null,
            'completedOrders' => $this->activeTab === 'completed' ? $this->ordersForGroup('completed', $this->completedPerPage) : null,
            'canceledOrders' => $this->activeTab === 'canceled' ? $this->ordersForGroup('canceled', $this->canceledPerPage) : null,
        ]);
    }
}

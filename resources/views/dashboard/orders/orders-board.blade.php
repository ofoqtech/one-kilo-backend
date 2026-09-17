<div>
    @php
        $orderStatusClasses = [
            \App\Models\Order::STATUS_PENDING => 'warning',
            \App\Models\Order::STATUS_AWAITING_PAYMENT => 'info',
            \App\Models\Order::STATUS_CONFIRMED => 'primary',
            \App\Models\Order::STATUS_PREPARING => 'secondary',
            \App\Models\Order::STATUS_READY => 'success',
            \App\Models\Order::STATUS_OUT_FOR_DELIVERY => 'info',
            \App\Models\Order::STATUS_PICKED_UP => 'info',
            \App\Models\Order::STATUS_DELIVERED => 'success',
            \App\Models\Order::STATUS_CANCELED => 'danger',
            \App\Models\Order::STATUS_FAILED => 'danger',
        ];

        $tabs = [
            'incoming' => __('dashboard.orders-incoming'),
            'in_delivery' => __('dashboard.orders-in-delivery'),
            'completed' => __('dashboard.orders-completed'),
            'canceled' => __('dashboard.orders-canceled'),
        ];
    @endphp

    <ul class="nav nav-pills mb-1">
        @foreach ($tabs as $key => $label)
            <li class="nav-item">
                <button type="button" class="nav-link {{ $activeTab === $key ? 'active' : '' }}"
                    wire:click="setTab('{{ $key }}')">
                    {{ $label }}
                    <span class="badge bg-light-{{ $activeTab === $key ? 'light' : 'dark' }} ms-50">{{ $counts[$key] }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    @php
        $currentOrders = match ($activeTab) {
            'incoming' => $incomingOrders,
            'in_delivery' => $inDeliveryOrders,
            'completed' => $completedOrders,
            'canceled' => $canceledOrders,
        };
    @endphp

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('dashboard.order-number') }}</th>
                    <th>{{ __('dashboard.customer') }}</th>
                    <th>{{ __('dashboard.order-status') }}</th>
                    <th>{{ __('dashboard.delivery-agent-name') }}</th>
                    <th>{{ __('dashboard.item-count') }}</th>
                    <th>{{ __('dashboard.total') }}</th>
                    <th>{{ __('dashboard.placed-at') }}</th>
                    <th>{{ __('dashboard.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($currentOrders as $index => $order)
                    <tr wire:key="board-order-{{ $order->id }}">
                        <td>{{ $currentOrders->firstItem() + $index }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user?->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-light-{{ $orderStatusClasses[$order->status] ?? 'secondary' }}">
                                {{ __('dashboard.order-status-' . str_replace('_', '-', $order->status)) }}
                            </span>
                        </td>
                        <td>{{ $order->delivery?->full_name ?? '-' }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td>{{ number_format((float) $order->total, 2) }}</td>
                        <td>{{ $order->placed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('dashboard.orders.show', $order->id) }}" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-2">{{ __('dashboard.no-data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-1">
        {{ $currentOrders?->links() }}
    </div>
</div>

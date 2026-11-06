<?php

namespace App\Repositories\Api\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Resources\NotificationsResource;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderRepository
{
    public function paginateForUser(int $userId, array $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('user_id', $userId)
            ->with(['address.country', 'address.governorate', 'address.region'])
            ->withSum('items as items_count', 'quantity')
            ->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }


    public function paginateCurrentForDelivery(int $userId, array $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('delivery_id', $userId)
            ->whereIn('status', ['out_for_delivery', 'preparing', 'confirmed', 'picked_up','ready'])
            ->with(['address.country', 'address.governorate', 'address.region'])
            ->withSum('items as items_count', 'quantity')
            ->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function paginatePastForDelivery(int $userId, array $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('delivery_id', $userId)
            ->whereIn('status', ['delivered'])
            ->with(['address.country', 'address.governorate', 'address.region'])
            ->withSum('items as items_count', 'quantity')
            ->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function findForUser(int $userId, string $reference): Order
    {
        $query = Order::query()
            ->where('user_id', $userId)
            ->with([
                'coupon',
                'items',
                'delivery',
                'address.country',
                'address.governorate',
                'address.region',
                'walletTransaction',
            ])
            ->withSum('items as items_count', 'quantity');

        if (ctype_digit($reference)) {
            $query->where(function ($builder) use ($reference) {
                $builder->whereKey((int)$reference)
                    ->orWhere('order_number', $reference);
            });
        } else {
            $query->where('order_number', $reference);
        }

        return $query->firstOrFail();
    }


    public function findForDelivery(int $userId, string $reference): Order
    {
        $query = Order::query()
            ->where('delivery_id', $userId)
            ->with([
                'coupon',
                'items',
                'address.country',
                'address.governorate',
                'address.region',
                'walletTransaction',
            ])
            ->withSum('items as items_count', 'quantity');

        if (ctype_digit($reference)) {
            $query->where(function ($builder) use ($reference) {
                $builder->whereKey((int)$reference)
                    ->orWhere('order_number', $reference);
            });
        } else {
            $query->where('order_number', $reference);
        }

        return $query->firstOrFail();
    }

    public function findLocationForDelivery(int $userId, string $reference): Order
    {
        $query = Order::query()
            ->with([
                'delivery',

            ]);

        if (ctype_digit($reference)) {
            $query->where(function ($builder) use ($reference) {
                $builder->whereKey((int)$reference)
                    ->orWhere('order_number', $reference);
            });
        } else {
            $query->where('order_number', $reference);
        }

        return $query->firstOrFail();
    }

    public function create(array $data): Order
    {
        return Order::query()->create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order;
    }

    public function createItems(Order $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    public function createStatusLog(Order $order, array $data): \App\Models\OrderStatusLog
    {
        return $order->statusLogs()->create($data);
    }

    public function loadDetails(Order $order): Order
    {
        return $order->load([
            'coupon',
            'items',
            'address.country',
            'address.governorate',
            'address.region',
            'walletTransaction',
        ]);
    }

    public function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'OK-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (
            Order::query()->where('order_number', $orderNumber)->exists()
        );

        return $orderNumber;
    }

    public function updateStatusForDelivery($reference, $request)
    {

        $order = Order::find($reference);
        $order->status = $request->status;
        $order->update();

        return $order;
    }


    public function getNotifications()
    {
        $user = Auth::user();
        $notifications = Notification::query()
            ->where(function ($query) use ($user) {
                $query->whereHasMorph(
                    'notifiable',
                    [get_class($user)],
                    function ($q) use ($user) {
                        $q->where('id', $user->id);
                    }
                )
                    ->orWhere(function ($q) {
                        $q->whereNull('notifiable_type')
                            ->whereNull('notifiable_id');
                    });
            })
            ->latest()
            ->paginate(15);

        return $notifications;
    }

}


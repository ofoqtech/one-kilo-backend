<?php

namespace App\Services\Dashboard;

use App\Models\Admin;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function open(Admin|Delivery $user, ?float $openingCash = null): Shift
    {
        if ($user->currentShift()) {
            throw ValidationException::withMessages([
                'shift' => __('dashboard.shift-already-open'),
            ]);
        }

        return $user->shifts()->create([
            'started_at' => now(),
            'opening_cash' => $openingCash,
        ]);
    }

    public function close(Shift $shift, ?float $closingCash = null): Shift
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => __('dashboard.shift-already-closed'),
            ]);
        }

        $shift->close($closingCash);

        return $shift->fresh();
    }

    public function report(Shift $shift): array
    {
        $orders = Order::query()
            ->where(function ($query) use ($shift) {
                $query->where('cashier_shift_id', $shift->id);

                if ($shift->shiftable instanceof Delivery) {
                    $query->orWhere(function ($deliveryQuery) use ($shift) {
                        $deliveryQuery->where('delivery_id', $shift->shiftable_id)
                            ->whereBetween('placed_at', [
                                $shift->started_at,
                                $shift->ended_at ?? now(),
                            ]);
                    });
                }
            })
            ->get();

        $salesStatuses = Order::salesStatuses();

        return [
            'shift' => $shift,
            'orders_count' => $orders->count(),
            'sales_orders_count' => $orders->whereIn('status', $salesStatuses)->count(),
            'canceled_orders_count' => $orders->where('status', Order::STATUS_CANCELED)->count(),
            'sales_total' => (float) $orders->whereIn('status', $salesStatuses)->sum('total'),
            'cash_orders_total' => (float) $orders->where('payment_method', Order::PAYMENT_METHOD_CASH_ON_DELIVERY)
                ->whereIn('status', $salesStatuses)
                ->sum('total'),
            'card_orders_total' => (float) $orders->where('payment_method', Order::PAYMENT_METHOD_CARD)
                ->whereIn('status', $salesStatuses)
                ->sum('total'),
            'wallet_orders_total' => (float) $orders->where('payment_method', Order::PAYMENT_METHOD_WALLET)
                ->whereIn('status', $salesStatuses)
                ->sum('total'),
        ];
    }
}

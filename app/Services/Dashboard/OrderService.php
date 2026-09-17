<?php

namespace App\Services\Dashboard;

use App\Events\OrderStatusChangedEvent;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\Dashboard\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected const EDITABLE_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_AWAITING_PAYMENT,
    ];

    public function __construct(protected OrderRepository $orderRepository)
    {
    }

    public function ensureItemsEditable(Order $order): void
    {
        if (! in_array($order->status, self::EDITABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'items' => __('dashboard.order-items-not-editable'),
            ]);
        }
    }

    public function updateItemQuantity(Order $order, OrderItem $item, int $quantity, ?Admin $admin = null): Order
    {
        $this->ensureItemsEditable($order);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('dashboard.invalid-item-quantity'),
            ]);
        }

        return DB::transaction(function () use ($order, $item, $quantity, $admin) {
            $this->orderRepository->updateItemQuantity($item, $quantity);
            $updatedOrder = $this->orderRepository->recalculateTotals($order);

            $this->logItemChange($updatedOrder, $admin, __('dashboard.order-item-quantity-updated'));

            return $this->orderRepository->loadDetails($updatedOrder);
        });
    }

    public function deleteItem(Order $order, OrderItem $item, ?Admin $admin = null): Order
    {
        $this->ensureItemsEditable($order);

        if ($order->items()->count() <= 1) {
            throw ValidationException::withMessages([
                'items' => __('dashboard.order-must-have-at-least-one-item'),
            ]);
        }

        return DB::transaction(function () use ($order, $item, $admin) {
            $this->orderRepository->deleteItem($item);
            $updatedOrder = $this->orderRepository->recalculateTotals($order);

            $this->logItemChange($updatedOrder, $admin, __('dashboard.order-item-removed'));

            return $this->orderRepository->loadDetails($updatedOrder);
        });
    }

    public function addItem(Order $order, Product $product, ?ProductSku $sku, int $quantity, ?Admin $admin = null): Order
    {
        $this->ensureItemsEditable($order);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('dashboard.invalid-item-quantity'),
            ]);
        }

        return DB::transaction(function () use ($order, $product, $sku, $quantity, $admin) {
            $this->orderRepository->addItem($order, $product, $sku, $quantity);
            $updatedOrder = $this->orderRepository->recalculateTotals($order);

            $this->logItemChange($updatedOrder, $admin, __('dashboard.order-item-added'));

            return $this->orderRepository->loadDetails($updatedOrder);
        });
    }

    public function replaceItem(Order $order, OrderItem $item, Product $product, ?ProductSku $sku, int $quantity, ?Admin $admin = null): Order
    {
        $this->ensureItemsEditable($order);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('dashboard.invalid-item-quantity'),
            ]);
        }

        return DB::transaction(function () use ($order, $item, $product, $sku, $quantity, $admin) {
            $this->orderRepository->deleteItem($item);
            $this->orderRepository->addItem($order, $product, $sku, $quantity);
            $updatedOrder = $this->orderRepository->recalculateTotals($order);

            $this->logItemChange($updatedOrder, $admin, __('dashboard.order-item-replaced'));

            return $this->orderRepository->loadDetails($updatedOrder);
        });
    }

    private function logItemChange(Order $order, ?Admin $admin, string $description): void
    {
        $this->orderRepository->createStatusLog($order, [
            'old_status' => $order->status,
            'new_status' => $order->status,
            'changed_by_admin_id' => $admin?->id,
            'title' => __('dashboard.order-items-updated'),
            'description' => $description,
            'meta' => [
                'source' => 'dashboard',
                'type' => 'items_update',
            ],
        ]);
    }

    public function getOrderDetails(Order $order): Order
    {
        return $this->orderRepository->loadDetails($order);
    }

    public function updateStatus(Order $order, string $newStatus, ?Admin $admin = null): Order
    {
        $oldStatus = $order->status;

        if (! $order->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => __('dashboard.invalid-order-status-transition'),
            ]);
        }

        return DB::transaction(function () use ($order, $oldStatus, $newStatus, $admin) {
            $updatedOrder = $this->orderRepository->updateStatus($order, $newStatus);

            if ($newStatus === Order::STATUS_CONFIRMED && $updatedOrder->cashier_shift_id === null) {
                $openShift = $admin?->currentShift();

                if ($openShift) {
                    $updatedOrder->update(['cashier_shift_id' => $openShift->id]);
                }
            }

            $this->orderRepository->createStatusLog($updatedOrder, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by_admin_id' => $admin?->id,
                'title' => __('dashboard.order-status-updated'),
                'description' => __('dashboard.order-status-changed-description', [
                    'from' => $this->statusLabel($oldStatus),
                    'to' => $this->statusLabel($newStatus),
                ]),
                'meta' => [
                    'source' => 'dashboard',
                ],
            ]);

            OrderStatusChangedEvent::dispatch($updatedOrder, $oldStatus);

            return $this->orderRepository->loadDetails($updatedOrder);
        });
    }

    public function cancel(Order $order, string $reason, ?Admin $admin = null): Order
    {
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELED, Order::STATUS_FAILED], true)) {
            throw ValidationException::withMessages([
                'status' => __('dashboard.invalid-order-status-transition'),
            ]);
        }

        $oldStatus = $order->status;
        $hadDelivery = $order->delivery_id !== null;

        return DB::transaction(function () use ($order, $oldStatus, $reason, $admin, $hadDelivery) {
            $order->update([
                'status' => Order::STATUS_CANCELED,
                'delivery_id' => null,
            ]);

            $this->orderRepository->createStatusLog($order, [
                'old_status' => $oldStatus,
                'new_status' => Order::STATUS_CANCELED,
                'changed_by_admin_id' => $admin?->id,
                'title' => __('dashboard.order-canceled'),
                'description' => $reason,
                'meta' => [
                    'source' => 'dashboard',
                    'type' => 'cancellation',
                    'reason' => $reason,
                    'unassigned_delivery' => $hadDelivery,
                ],
            ]);

            OrderStatusChangedEvent::dispatch($order, $oldStatus);

            return $this->orderRepository->loadDetails($order);
        });
    }

    private function statusLabel(string $status): string
    {
        return __('dashboard.order-status-' . str_replace('_', '-', $status));
    }


    public function getDeliveries($orderId){

        $order = Order::findOrFail($orderId);

        $availableDeliveries = $this->orderRepository->availableDeliveries();

        $busyDeliveries = $this->orderRepository->busyDeliveries();


        $data = array(
            "order" => $order,
            "availableDeliveries" => $availableDeliveries,
            "busyDeliveries" => $busyDeliveries
        );

        return $data;

    }

    public function assign($request,$orderId){

        return $this->orderRepository->assign($request,$orderId);
    }
}

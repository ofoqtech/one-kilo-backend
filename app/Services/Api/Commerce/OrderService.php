<?php

namespace App\Services\Api\Commerce;

use App\Models\Order;
use App\Repositories\Api\Commerce\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(protected OrderRepository $orderRepository , protected FirebaseService $firebaseService)
    {
    }

    public function paginate(int $userId, array $filters): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForUser($userId, $filters);
    }

    public function show(int $userId, string $reference): Order
    {
        return $this->orderRepository->findForUser($userId, $reference);
    }

    public function showForDelivery(int $userId, string $reference): Order
    {
        return $this->orderRepository->findForDelivery($userId, $reference);
    }

    public function showLocationForDelivery(int $userId, string $reference): Order
    {
        return $this->orderRepository->findLocationForDelivery($userId, $reference);
    }

    public function currentOrders(int $userId, array $filters): LengthAwarePaginator
    {
        return $this->orderRepository->paginateCurrentForDelivery($userId, $filters);
    }

    public function pastOrders(int $userId, array $filters): LengthAwarePaginator
    {
        return $this->orderRepository->paginatePastForDelivery($userId, $filters);
    }

    public function updateStatus(string $orderId,$request)
    {
        return $this->orderRepository->updateStatusForDelivery($orderId,$request);

    }

    public function getNotifications(){
        return $this->orderRepository->getNotifications();
    }

    public function cancel(int $userId, string $reference, ?string $reason): Order
    {
        $order = $this->orderRepository->findForUser($userId, $reference);

        $cancellableStatuses = [Order::STATUS_PENDING, Order::STATUS_AWAITING_PAYMENT, Order::STATUS_CONFIRMED];
        if (!in_array($order->status, $cancellableStatuses, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => __('front.order-cannot-be-cancelled'),
            ]);
        }

        $oldStatus = $order->status;
        $order = $this->orderRepository->update($order, ['status' => Order::STATUS_CANCELED]);

        $this->orderRepository->createStatusLog($order, [
            'old_status' => $oldStatus,
            'new_status' => Order::STATUS_CANCELED,
            'title' => __('front.order-cancelled-by-customer-title'),
            'description' => $reason ?? __('front.cancelled-by-customer-default-reason'),
            'meta' => [
                'source' => 'customer',
                'user_id' => $userId,
            ],
        ]);

        event(new \App\Events\OrderStatusChangedEvent($order, $oldStatus));

        $this->notifyCustomerOrderCanceled($order);

        return $this->orderRepository->loadDetails($order);
    }

    private function notifyCustomerOrderCanceled(Order $order): void
    {
        try {
            $token = $order->user?->fcm_token;

            if (! $token) {
                return;
            }

            $this->firebaseService->sendNotification(
                $token,
                'تم إلغاء طلبك بنجاح',
                'رقم الطلب #' . $order->order_number,
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send order-canceled push notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

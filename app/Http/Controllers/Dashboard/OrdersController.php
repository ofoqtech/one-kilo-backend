<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\Api\Commerce\FirebaseService;
use App\Services\Dashboard\OrderService;

use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct(protected OrderService $orderService, protected FirebaseService $firebaseService)
    {
    }

    public function index()
    {
        return view('dashboard.orders.index');
    }

    public function show(Order $order)
    {
        $data = $this->buildOrderViewData($order);

        return view('dashboard.orders.show', [
            ...$data,
            'allowedNextStatuses' => $data['order']->allowedNextStatuses(),
            'canChangeStatus' => (bool) auth('admin')->user()?->hasAccess('orders_change_status'),
        ]);
    }

    public function print(Order $order)
    {
        return view('dashboard.orders.print', [
            ...$this->buildOrderViewData($order),
            'printedAt' => now(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->orderService->updateStatus(
            $order,
            (string) $request->string('status'),
            $request->user('admin')
        );

        $data = Order::STATUS_MESSAGES[(string)$request->string('status')] ?? null;

        $msg = $data['title']['ar'];
        $title = $data['message']['ar'];

        $this->firebaseService->sendNotification($order->user->fcm_token??'',$title,$msg);


        $this->firebaseService->saveNotification($order->user,$order->id,$data['title'],$data['message']);


        flash()->success(__('dashboard.status-updated-successfully'));

        return back();
    }

    public function cancel(CancelOrderRequest $request, Order $order)
    {
        $previousDelivery = $order->delivery;

        $this->orderService->cancel(
            $order,
            (string) $request->string('reason'),
            $request->user('admin')
        );

        $cancelData = Order::STATUS_MESSAGES[Order::STATUS_CANCELED] ?? null;

        if ($cancelData && $order->user) {
            $this->firebaseService->sendNotification($order->user->fcm_token ?? '', $cancelData['message']['ar'], $cancelData['title']['ar']);
            $this->firebaseService->saveNotification($order->user, $order->id, $cancelData['title'], $cancelData['message']);
        }

        if ($previousDelivery) {
            $title = ['en' => 'Order Canceled', 'ar' => 'تم إلغاء الطلب'];
            $message = ['en' => 'An order assigned to you has been canceled', 'ar' => 'تم إلغاء طلب كان مسندًا إليك'];

            $this->firebaseService->sendNotification($previousDelivery->fcm_token ?? '', $message['ar'], $title['ar']);
            $this->firebaseService->saveNotification($previousDelivery, $order->id, $title, $message);
        }

        flash()->success(__('dashboard.order-canceled-successfully'));

        return back();
    }

    private function resolveAddress(Order $order): array
    {
        $snapshot = $order->addressSnapshot();

        if ($snapshot !== []) {
            return [
                'id' => $snapshot['id'] ?? $order->address_id,
                'label' => $snapshot['label'] ?? null,
                'contact_name' => $snapshot['contact_name'] ?? null,
                'phone' => $snapshot['phone'] ?? null,
                'country_name' => $snapshot['country_name'] ?? null,
                'governorate_name' => $snapshot['governorate_name'] ?? null,
                'region_name' => $snapshot['region_name'] ?? null,
                'city' => $snapshot['city'] ?? null,
                'area' => $snapshot['area'] ?? null,
                'street' => $snapshot['street'] ?? null,
                'building_number' => $snapshot['building_number'] ?? null,
                'floor' => $snapshot['floor'] ?? null,
                'apartment_number' => $snapshot['apartment_number'] ?? null,
                'landmark' => $snapshot['landmark'] ?? null,
                'full_address' => $snapshot['full_address'] ?? null,
            ];
        }

        if (! $order->address) {
            return [];
        }

        return [
            'id' => $order->address->id,
            'label' => $order->address->label,
            'contact_name' => $order->address->contact_name,
            'phone' => $order->address->phone,
            'country_name' => $order->address->country?->name,
            'governorate_name' => $order->address->governorate?->name,
            'region_name' => $order->address->region?->name,
            'city' => $order->address->city,
            'area' => $order->address->area,
            'street' => $order->address->street,
            'building_number' => $order->address->building_number,
            'floor' => $order->address->floor,
            'apartment_number' => $order->address->apartment_number,
            'landmark' => $order->address->landmark,
            'full_address' => $order->address->fullAddress(),
        ];
    }

    private function buildOrderViewData(Order $order): array
    {
        $order = $this->orderService->getOrderDetails($order);

        return [
            'order' => $order,
            'address' => $this->resolveAddress($order),
            'couponSnapshot' => (array) data_get($order->meta, 'coupon_snapshot', []),
        ];
    }


    public function assignDelivery($orderId)
    {

        $data = $this->orderService->getDeliveries($orderId);

        return view('dashboard.orders.assign-delivery', [
            'order' => $data['order'],
            'availableDeliveries' => $data['availableDeliveries'],
            'busyDeliveries' => $data['busyDeliveries'],
        ]);
    }

    public function assign(Request $request, $orderId)
    {

        $this->orderService->assign($request,$orderId);

        $msg = Order::MESSAGE_ASSIGNED_TO_DELIVERY['title']['ar'];
        $title = Order::MESSAGE_ASSIGNED_TO_DELIVERY['message']['ar'];

        $delivery  = Delivery::find($request->delivery_id);

       $this->firebaseService->sendNotification($delivery->fcm_token??'',$title,$msg);


        $this->firebaseService->saveNotification($delivery,$orderId,Order::MESSAGE_ASSIGNED_TO_DELIVERY['title'],Order::MESSAGE_ASSIGNED_TO_DELIVERY['message']);


        return redirect()
            ->route('dashboard.orders.show', $orderId)
            ->with('success', 'Delivery assigned successfully.');
    }
}

<?php

namespace App\Repositories\Dashboard;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\ProductSku;

class OrderRepository
{
    public function loadDetails(Order $order): Order
    {
        return $order->load([
            'user:id,name,email,phone',
            'address:id,country_id,governorate_id,region_id,label,contact_name,phone,city,area,street,building_number,floor,apartment_number,landmark,status',
            'address.country:id,name',
            'address.governorate:id,country_id,name',
            'address.region:id,governorate_id,name,shipping_price',
            'coupon:id,code',
            'delivery:id,full_name,phone,vehicle_type,vehicle_model,vehicle_brand,status',
            'walletTransaction:id,wallet_id,user_id,order_id,type,transaction_type,amount,balance_before,balance_after,reference,notes,status,created_at',
            'items:id,order_id,product_id,product_sku_id,sku_label,product_name,product_image,unit_price,quantity,line_total',
            'items.product:id,sku,slug',
            'items.sku',
            'statusLogs.changedByAdmin:id,name',
        ]);
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update([
            'status' => $status,
        ]);

        return $order->fresh();
    }

    public function createStatusLog(Order $order, array $data): OrderStatusLog
    {
        return $order->statusLogs()->create($data);
    }


    protected function activeDeliveryStatuses(): array
    {
        return ['picked_up', 'delivered', 'canceled', 'failed'];
    }

    public function availableDeliveries()
    {
        // Available (approved + not busy)
        $availableDeliveries = Delivery::where('status', 'approved')
            ->whereDoesntHave('orders', function ($q) {
                $q->whereNotIn('status', $this->activeDeliveryStatuses());
            })
            ->get();

        return $availableDeliveries;
    }


    public function busyDeliveries()
    {
        // Busy Deliveries
        $busyDeliveries = Delivery::where('status', 'approved')
            ->whereHas('orders', function ($q) {
                $q->whereNotIn('status', $this->activeDeliveryStatuses());
            })
            ->with(['orders' => function ($q) {
                $q->whereNotIn('status', $this->activeDeliveryStatuses());
            }])
            ->get();

        return $busyDeliveries;
    }


    public function assign($request ,$orderId){

        $request->validate([
            'delivery_id' => 'required|exists:deliveries,id',
        ]);

        $order = Order::findOrFail($orderId);

        $delivery = Delivery::where('id', $request->delivery_id)
            ->where('status', 'approved')
            ->firstOrFail();

        $order->update([
            'delivery_id' => $delivery->id,
        ]);

    }

    public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
    {
        $item->update([
            'quantity' => $quantity,
            'line_total' => round($item->unit_price * $quantity, 2),
        ]);

        return $item->fresh();
    }

    public function deleteItem(OrderItem $item): void
    {
        $item->delete();
    }

    public function addItem(Order $order, Product $product, ?ProductSku $sku, int $quantity): OrderItem
    {
        $unitPrice = $sku ? $sku->priceAfterDiscount() : $product->priceAfterDiscount();

        return $order->items()->create([
            'product_id' => $product->id,
            'product_sku_id' => $sku?->id,
            'sku_label' => $sku?->label(),
            'product_name' => $product->name,
            'product_image' => $product->image,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
        ]);
    }

    public function recalculateTotals(Order $order): Order
    {
        $order->refresh();

        $subtotal = round((float) $order->items()->sum('line_total'), 2);
        $discountAmount = $order->coupon
            ? $order->coupon->calculateDiscount($subtotal)
            : min((float) $order->discount_amount, $subtotal);

        $total = round(max($subtotal - $discountAmount + (float) $order->delivery_fee, 0), 2);

        $order->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ]);

        return $order->fresh();
    }
}

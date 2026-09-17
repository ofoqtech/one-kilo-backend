<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'subtotal' => round((double) $this->subtotal, 2),
            'discount_amount' => round((double) $this->discount_amount, 2),
            'delivery_fee' => round((double) $this->delivery_fee, 2),
            'total' => round((double) $this->total, 2),
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'items_count' => isset($this->items_count)
                ? (int) $this->items_count
                : $this->itemsCount(),
            'address' => $this->resolveAddress($request),
            'payment_url' => $this->payment_url,
        ];
    }

    protected function resolveAddress(Request $request): ?array
    {
        $snapshot = $this->addressSnapshot();

        if ($snapshot !== []) {
            return [
                'id' => $snapshot['id'] ?? $this->address_id,
                'label' => $snapshot['label'] ?? null,
                'contact_name' => $snapshot['contact_name'] ?? null,
                'phone' => $snapshot['phone'] ?? null,
                'country_id' => (string) $snapshot['country_id'] ?? null,
                'country_name' => $snapshot['country_name'] ?? null,
                'governorate_id' => (string) $snapshot['governorate_id'] ?? null,
                'governorate_name' => $snapshot['governorate_name'] ?? null,
                'region_id' => $snapshot['region_id'] ?? null,
                'region_name' => $snapshot['region_name'] ?? null,
                'region_shipping_price' => $snapshot['region_shipping_price'] ?? null,
                'city' => $snapshot['city'] ?? null,
                'area' => $snapshot['area'] ?? null,
                'street' => $snapshot['street'] ?? null,
                'building_number' => $snapshot['building_number'] ?? null,
                'floor' => $snapshot['floor'] ?? null,
                'apartment_number' => $snapshot['apartment_number'] ?? null,
                'landmark' => $snapshot['landmark'] ?? null,
                'latitude' => $snapshot['latitude'] ?? null,
                'longitude' => $snapshot['longitude'] ?? null,
                'full_address' => $snapshot['full_address'] ?? null,
            ];
        }

        if (! $this->relationLoaded('address') || ! $this->address) {
            return null;
        }

        return (new AddressResource($this->address))->resolve($request);
    }
}

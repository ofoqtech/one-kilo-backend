<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'contact_name' => $this->contact_name,
            'phone' => $this->phone,
            'country_id' => (string) $this->country_id,
            'governorate_id' => (string) $this->governorate_id,
            'region_id' => $this->region_id,
            'country' => $this->country ? [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ] : null,
            'governorate' => $this->governorate ? [
                'id' => $this->governorate->id,
                'name' => $this->governorate->name,
            ] : null,
            'region' => $this->region ? [
                'id' => $this->region->id,
                'name' => $this->region->name,
                'shipping_price' => round((float) $this->region->shipping_price, 2),
            ] : null,
            'city' => $this->city,
            'area' => $this->area,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'floor' => $this->floor,
            'apartment_number' => $this->apartment_number,
            'landmark' => $this->landmark,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'coordinates' => $this->latitude !== null && $this->longitude !== null ? [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ] : null,
            'full_address' => $this->fullAddress(),
            'is_default' => (bool) $this->is_default,
            'status' => (bool) $this->status,
        ];
    }
}

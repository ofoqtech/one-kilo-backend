<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => (string) $this->product_id,
            'sku_id' => $this->product_sku_id,
            'sku_label' => $this->sku_label,
            'product_name' => $this->product_name,
            'product_image' => $this->product_image ? asset($this->product_image) : '',
            'unit_price' => round((double) $this->unit_price, 2),
            'quantity' => (int) $this->quantity,
            'line_total' => round((double) $this->line_total, 2),
        ];
    }
}

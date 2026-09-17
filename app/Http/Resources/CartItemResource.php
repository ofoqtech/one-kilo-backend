<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => (int) $this->quantity,
            'product' => $this->whenLoaded('product', fn() => new ProductSummaryResource($this->product)),
            'sku_id' => (string) $this->product_sku_id,
            'sku' => $this->whenLoaded('sku', fn () => $this->sku ? new ProductSkuResource($this->sku) : null),
            'unit_price' => (double) $this->unitPrice(),
            'line_total' => (double) $this->lineTotal(),
        ];
    }
}

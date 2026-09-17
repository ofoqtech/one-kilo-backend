<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppPopupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image ? asset($this->image) : '',
            'link_type' => $this->link_type,
            'link_id' => $this->link_id,
            'link_url' => $this->link_url,
        ];
    }
}

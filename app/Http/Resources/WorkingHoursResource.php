<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingHoursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'day_of_week'     => (string) $this->day_of_week,
            'day_name'           => $this->day_name,
            'open_time'           => $this->open_time,
            'close_time'           => $this->close_time,
            'status'           =>  $this->status,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'day_type' => $this->day_type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'session_count' => $this->session_count,
            'price' => $this->price,
            'duration' => $this->duration,
            'is_active' => $this->is_active,

            // Related class info
            'class' => [
                'id' => $this->gymClass?->id,
                'name' => $this->gymClass?->name,
            ]
        ];
    }
}

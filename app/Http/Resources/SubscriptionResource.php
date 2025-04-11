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
            'session_count' => $this->session_count,
            'price' => $this->price,
            'is_active' => $this->is_active,

            // Related class info
            'class' => [
                'id' => $this->gymClass?->id,
                'name' => $this->gymClass?->name,
                'day_type' => $this->gymClass?->day_type,
                'start_time' => $this->gymClass?->start_time,
                'end_time' => $this->gymClass?->end_time,
                'instructor_name' => $this->gymClass?->instructor?->name,
            ]
        ];
    }
}

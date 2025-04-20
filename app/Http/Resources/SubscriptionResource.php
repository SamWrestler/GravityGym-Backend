<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            'start_time' => Jalalian::fromDateTime($this->start_time)->format('H:i'), // Format without seconds
            'end_time' => Jalalian::fromDateTime($this->end_time)->format('H:i'), // Format without seconds
            'session_count' => $this->session_count,
            'price' => $this->price,
            'duration_value' => $this->duration_value,
            'duration_unit' => $this->duration_unit,
            'is_active' => $this->is_active,
            'instructor' => $this->instructor?->name,
            // Related class info
            'class' => [
                'id' => $this->gymClass?->id,
                'name' => $this->gymClass?->name,
            ]
        ];
    }
}

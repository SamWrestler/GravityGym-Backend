<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
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
            'name' => $this->name,
            'day_type' => $this->day_type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_active' => $this->is_active,
            'instructor_name' => $this->instructor?->name,
            'subscriptions' => $this->whenLoaded('subscriptions', function () {
                return $this->subscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'session_count' => $sub->session_count,
                        'price' => $sub->price,
                        'is_active' => $sub->is_active,
                    ];
                });
            }),
            ];
    }
}

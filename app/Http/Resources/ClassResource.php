<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            'is_active' => $this->is_active,
            'subscriptions' => $this->whenLoaded('subscriptions', function () {
                return $this->subscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'instructor_name' => $sub->instructor?->name,
                        'day_type' => $sub->day_type,
                        'start_time' => Jalalian::fromDateTime($sub->start_time)->format('H:i'), // Format without seconds
                        'end_time' => Jalalian::fromDateTime($sub->end_time)->format('H:i'), // Format without seconds
                        'session_count' => $sub->session_count,
                        'price' => $sub->price,
                        'duration' => $sub->duration, // اضافه کردن فیلد duration به ریسورس
                        'is_active' => $sub->is_active,
                    ];
                });
            }),
        ];
    }
}

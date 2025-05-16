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
                        'sub_name' => $sub->name,
                        'instructor' => $sub->instructor?->name,
                        'class_days' => $sub->class_days,
                        'start_time' => Jalalian::fromDateTime($sub->start_time)->format('H:i'), // Format without seconds
                        'end_time' => Jalalian::fromDateTime($sub->end_time)->format('H:i'), // Format without seconds
                        'session_count' => $sub->session_count,
                        'price' => $sub->price,
                        'class_type' => $sub->class_type,
                        'duration_value' => $sub->duration_value,
                        'duration_unit' => $sub->duration_unit,
                        'is_active' => $sub->is_active,
                        'enrollments' => $sub->relationLoaded('enrollments')
                            ? EnrollmentResource::collection($sub->enrollments)
                            : [],
                    ];
                });
            }),
        ];
    }
}

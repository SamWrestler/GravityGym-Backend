<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'birthdate' => $this->birthdate,
            'role' => $this->role,
            'gender' => $this->gender,
            'enrollments' => EnrollmentResource::collection($this->whenLoaded('enrollments')),
            'subscriptions' => SubscriptionResource::collection(
                $this->enrollments->pluck('subscription')->filter()
            ),
            ];
    }
}

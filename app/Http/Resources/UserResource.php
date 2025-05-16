<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            'birthdate' => $this->birthdate
                ? Jalalian::fromDateTime($this->birthdate)->format('Y/m/d')
                : null,
            'role' => $this->role,
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'height' => $this->height,
            'weight' => $this->weight,
            'insurance' => $this->insurance,
            'disabilities' => $this->relationLoaded('disabilities')
                ? $this->disabilities?->pluck('name') ?? null
                : null,
            'enrollments' => EnrollmentResource::collection($this->whenLoaded('enrollments')),
            'subscriptions' => $this->role === 'instructor'
                ? SubscriptionResource::collection(
                    $this->whenLoaded('subscriptions')
                )
                : null,
            'created_since' => $this->created_at
                ? $this->diffInYearsAndMonths()
                : null,
        ];
    }

    private function diffInYearsAndMonths(): array
    {
        $created = Carbon::parse($this->created_at);
        $now = Carbon::now();

        $years = (int) $created->diffInYears($now);
        $created->addYears($years);

        $months = (int) $created->diffInMonths($now);

        return [
            'year' => $years,
            'month' => $months,
        ];
    }


}

<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {


        $endDate = Carbon::parse($this->end_date);
        $startDate = Carbon::parse($this->start_date);

        if ($this->status === 'reserved') {
            $daysRemaining = max(0, ceil(now()->diffInDays($startDate, false)));
        } else {
            $daysRemaining = ceil($startDate->diffInDays($endDate));
        }

        return [
            'id' => $this->id,
            'start_date' => Jalalian::fromDateTime($this->start_date)->format('Y/m/d'),
            'end_date' => Jalalian::fromDateTime($this->end_date)->format('Y/m/d'),
            'status' => $this->status,
            'remaining_days' => $daysRemaining,
            'user' => $this->user_id,
            'attendances' => AttendanceResource::collection($this->whenLoaded('attendances')),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'userInfo' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

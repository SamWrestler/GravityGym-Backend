<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * تبدیل اطلاعات مدل Payment به فرمت خروجی API
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subscription_id' => $this->subscription_id,
            'amount' => $this->amount,
            'transaction_id' => $this->transaction_id,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'description' => $this->description,
            'raw_response' => $this->raw_response,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'transaction_id',
        'reference_id',
        'status',
        'gateway',
        'description',
        'raw_response',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function enrollment()
    {
        return $this->hasOne(Enrollment::class);
    }

}

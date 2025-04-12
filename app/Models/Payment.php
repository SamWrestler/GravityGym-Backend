<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

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
}

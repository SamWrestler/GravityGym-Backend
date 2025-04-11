<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['class_id', 'session_count', 'price', 'is_active'];
    public $timestamps = false;

    public function gymClass()
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'subscription_id');
    }

}

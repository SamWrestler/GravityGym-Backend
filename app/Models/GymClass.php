<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymClass extends Model
{
    use HasFactory;

    protected $table = "classes";
    protected $fillable = ['name', 'instructor_id', 'day_type', 'start_time', 'end_time', 'is_active'];
    public $timestamps = false;

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'class_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

}

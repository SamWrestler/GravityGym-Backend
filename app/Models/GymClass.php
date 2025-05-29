<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GymClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "classes";
    protected $fillable = ['name', 'is_active'];
    public $timestamps = false;
    protected $casts = [
        'class_days' => 'array',
        'price' => 'float',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'class_id');
    }


}

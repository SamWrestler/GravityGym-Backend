<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disability extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

}

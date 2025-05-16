<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'enrollment_id', 'session_date','status'];
    public $timestamps = false;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function enrollment(){
        return $this->belongsTo(Enrollment::class);
    }
}

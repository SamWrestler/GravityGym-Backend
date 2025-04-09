<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = ['code', 'expires_at', 'user_id'];
    public $timestamps = false;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function scopeGenerateCode($query, $user){
        try{
            do{
                $code = rand(10000, 99999);
            } while ($this->checkIfCodeIsValid($code, $user));

            $newCode = $this->create([
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(5)
            ]);

            return $code;
        }catch(\Exception $e){
            // Handle the exception appropriately
            return response()->json("Error message: $e");
        }
    }

    public function scopeCheckIfCodeIsValid($query, $code, $user){
        return $user->otp()->where('code', $code)->where('expires_at' , '>' , now())->exists();
    }
}

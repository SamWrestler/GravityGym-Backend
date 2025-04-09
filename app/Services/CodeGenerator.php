<?php
namespace App\Services;

use App\Models\Otp;


class CodeGenerator
{
    public static function generateCode($user)
    {
        $code = rand(10000, 99999);
        $newCode = Otp::create([
            'user_id' => $user->id,
            'code' => $code,
        ]);

        return $code;

    }
}
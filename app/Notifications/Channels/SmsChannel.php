<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Ipe\Sdk\Facades\SmsIr;class SmsChannel{
    public function send($notifiable, Notification $notification){
        $code = $notification->toSmsChannel();
        $mobile = $notifiable->phone_number; // شماره موبایل گیرنده
        $templateId = 123456; // شناسه الگو
        $parameters = [
            [
                "name" => "Code",
                "value" => $code['code']
            ]
        ];

        try {
            $response = SmsIr::verifySend($mobile, $templateId, $parameters);
        }catch(\Exception $e){
            throw $e;
        }
        }
}

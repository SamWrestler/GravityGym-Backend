<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;



class PaymentController extends Controller
{

    public function pay(CreatePaymentRequest $request)
    {
        $user = $request->user();

        // ذخیره در دیتابیس
        $record = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $request->subscription_id,
            'amount' => $request->amount,
            'description' => $request->description ?? 'پرداخت با زرین‌پال',
            'status' => 'pending',
        ]);

        // ساخت فاکتور
        $invoice = (new Invoice)->amount($record->amount);
        $invoice->detail(['description' => $record->description]);

        // لود تنظیمات
        $paymentConfig = config('payment');
        $payment = new \Shetabit\Multipay\Payment($paymentConfig);

        try {

            $paymentUrl = $payment->purchase($invoice, function($driver, $transactionId) use ($record) {
                $record->update(['transaction_id' => $transactionId]);
            })->pay()->toJson();

            return response()->json([
                'payment_url' => $paymentUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در پرداخت',
                'error' => $e->getMessage(),
            ], 500);
        }



    }

    public function verify(Request $request)
    {
        $authority = $request->input('authority');

        // 1️⃣ پیدا کردن پرداخت از روی transaction_id
        $paymentRecord = Payment::where('transaction_id', $authority)->firstOrFail();

        $config = config('payment');
        $payment = new \Shetabit\Multipay\Payment($config);

        try {
            // 2️⃣ استفاده از مبلغ واقعی ذخیره شده در دیتابیس
            $receipt = $payment
                ->amount($paymentRecord->amount)
                ->transactionId($authority)
                ->verify();

            // 3️⃣ بروزرسانی وضعیت پرداخت در دیتابیس
            $paymentRecord->update([
                'status' => 'success',
                'reference_id' => $receipt->getReferenceId(),
                'raw_response' => json_encode($receipt->getDetails())
            ]);

            $newEnrollment = Enrollment::create([
                'user_id' => $paymentRecord->user_id,
                'subscription_id' => $paymentRecord->subscription_id,
                'start_date' => now()->format('Y-m-d'),
                'end_date'   => now()->addDays(30)->format('Y-m-d'),
                'status' => 'active'
            ]);

            return response()->json([
                'status' => 'success',
                'ref_id' => $receipt->getReferenceId(),
                'message' => 'پرداخت با موفقیت تایید شد'
            ]);

        } catch (InvalidPaymentException $exception) {

            $paymentRecord->update([
                'status' => 'failed',
                'raw_response' => json_encode(['error' => $exception->getMessage()])
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'پرداخت تایید نشد: ' . $exception->getMessage(),
            ], 422);
        }
    }
}

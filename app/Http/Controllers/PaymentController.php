<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Database\Factories\EnrollmentFactory;
use Illuminate\Http\Request;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Illuminate\Support\Facades\Log;



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

        // 1️⃣ پیدا کردن پرداخت
        $paymentRecord = Payment::where('transaction_id', $authority)->first();

        if (!$paymentRecord) {
            Log::warning('Verification failed: transaction not found', [
                'authority' => $authority,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'پرداخت یافت نشد یا قبلاً تایید شده.',
            ], 404);
        }

        // 2️⃣ اگر پرداخت قبلاً موفق شده، عملیات تکراری انجام نده
        if ($paymentRecord->status === 'success') {
            return response()->json([
                'status' => 'success',
                'ref_id' => $paymentRecord->reference_id,
                'message' => 'پرداخت قبلاً تایید شده است.',
            ]);
        }

        $config = config('payment');
        $payment = new \Shetabit\Multipay\Payment($config);

        try {
            // 3️⃣ تایید با مبلغ واقعی
            $receipt = $payment
                ->amount($paymentRecord->amount)
                ->transactionId($authority)
                ->verify();

            // 4️⃣ بروزرسانی وضعیت پرداخت
            $paymentRecord->update([
                'status' => 'success',
                'reference_id' => $receipt->getReferenceId(),
                'raw_response' => json_encode($receipt->getDetails())
            ]);

            // 5️⃣ جلوگیری از ثبت‌نام تکراری
            $existingEnrollment = Enrollment::where('user_id', $paymentRecord->user_id)
                ->where('payment_id', $paymentRecord->id)
                ->first();

            if (!$existingEnrollment) {
                $userActiveEnrollment = Enrollment::where('subscription_id', $paymentRecord->subscription_id)->where('user_id', $paymentRecord->user_id)->latest('end_date')->first();
                $subscription = Subscription::find($paymentRecord->subscription_id);

                if ($subscription) {
                    if ($userActiveEnrollment) {
                        $startDate = Carbon::parse($userActiveEnrollment->end_date)->addDay();
                        $status = 'reserved';
                    } else {
                        $startDate = Carbon::now();
                        $status = 'active';
                    }
                    $endDate = (clone $startDate)->add($subscription->duration_unit, $subscription->duration_value);
                    Enrollment::create([
                        'user_id' => $paymentRecord->user_id,
                        'subscription_id' => $paymentRecord->subscription_id,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'status' => $status,
                        'payment_id' => $paymentRecord->id,
                    ]);
                }
            }
            return response()->json([
                'status' => 'success',
                'ref_id' => $receipt->getReferenceId(),
                'message' => 'پرداخت با موفقیت تایید شد',
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
        } catch (\Throwable $e) {
            Log::error('Unhandled payment verification error', [
                'error' => $e->getMessage(),
                'authority' => $authority,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'خطای غیرمنتظره در بررسی پرداخت: ' . $e->getMessage(),
            ], 500);
        }
    }
}

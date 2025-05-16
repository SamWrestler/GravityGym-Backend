<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\{Attendance, Enrollment, Payment, Subscription};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\{Exceptions\InvalidPaymentException, Invoice};

class PaymentController extends Controller
{
    /**
     * متد آغاز فرایند پرداخت
     */
    public function pay(CreatePaymentRequest $request)
    {
        $user = $request->user();

        // ذخیره اولیه رکورد پرداخت در دیتابیس
        $record = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $request->subscription_id,
            'amount' => $request->amount,
            'description' => $request->description ?? 'پرداخت با زرین‌پال',
            'status' => 'pending',
        ]);

        // ساخت فاکتور پرداخت
        $invoice = (new Invoice)->amount($record->amount);
        $invoice->detail(['description' => $record->description]);

        // بارگذاری تنظیمات و ایجاد پرداخت
        $paymentConfig = config('payment');
        $payment = new \Shetabit\Multipay\Payment($paymentConfig);

        try {
            // ارسال درخواست پرداخت و دریافت آدرس پرداخت
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

    /**
     * متد تایید پرداخت پس از بازگشت از درگاه
     */
    public function verify(Request $request)
    {
        $authority = $request->input('authority');

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
            $receipt = $payment
                ->amount($paymentRecord->amount)
                ->transactionId($authority)
                ->verify();

            $paymentRecord->update([
                'status' => 'success',
                'reference_id' => $receipt->getReferenceId(),
                'raw_response' => json_encode($receipt->getDetails())
            ]);

            $existingEnrollment = Enrollment::where('user_id', $paymentRecord->user_id)
                ->where('payment_id', $paymentRecord->id)
                ->first();

            if (!$existingEnrollment) {
                Enrollment::create([
                    'user_id' => $paymentRecord->user_id,
                    'subscription_id' => $paymentRecord->subscription_id,
                    'payment_id' => $paymentRecord->id,
                ]);
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

    public function all()
    {
        $payments = Payment::all();
        return PaymentResource::collection($payments);
    }
}

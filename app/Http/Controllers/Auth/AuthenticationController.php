<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Resources\UserResource;
    use App\Models\User;
    use App\Http\Controllers\Controller;
    use App\Models\Otp;
    use App\Notifications\OtpNotification;
    use Illuminate\Http\Request;
    use function PHPUnit\Framework\returnArgument;

    class AuthenticationController extends Controller
    {

        public function login(Request $request)
        {
            // return response()->json($request->remember);
            $validatedData = $request->validate([
                'phone_number' => ['required', 'regex:/((0?9)|(\+?989))\d{2}\W?\d{3}\W?\d{4}/']
            ]);

            $user = User::firstOrCreate(['phone_number' => $validatedData['phone_number']]);

            $request->session()->put('auth', [
                'user' => $user,
                'remember' => $request->remember
            ]);

            $code = Otp::generateCode($user);

            $notificationStatus = $user->notify(new OtpNotification($code));

            return response()->json($notificationStatus);



        }

        public function otp(Request $request)
        {
                $user = $request->session()->get('auth')['user'];

                if (!$user) {
                    return response()->json(['error' => 'User session not found.'], 401);
                }

                $validatedData = $request->validate([
                    'code' => ['required'],
                ]);

                $codeValidation = Otp::checkIfCodeIsValid($validatedData['code'], $user);

                if (!$codeValidation) {
                    return response()->json([
                        'errors' => [
                            'code' => ['عدم صحت یا انقضا کد وارد شده'],
                        ]
                    ], 422);                }

                $user->otp()->delete();

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Validation was successful!',
                    'token' => $token,
                ]);

        }

        public function resendCode(Request $request)
        {
            $phone = $request->input('phone_number');
            $user = User::where('phone_number', $phone)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $code = Otp::generateCode($user);
            $notificationStatus = $user->notify(new OtpNotification($code));
            return response()->json($notificationStatus);
        }


        public function loginExpired(Request $request)
        {
            $phone_number = $request->input('phone_number');
            $user = User::where('phone_number', $phone_number)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            if (empty($user->name)) {
                $user->otp()->delete();
                $user->delete();
                return response()->json("Login Expired");
            }

            $user->otp()->delete();
            return response()->json("Login Expired");
        }

        public function logout(Request $request)
        {
            $request->user()->currentAccessToken()->delete();

            return response()->json('Logged out successfully');

        }
    }

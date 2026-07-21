<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Services\CartService;
use Google\Client;
use App\Mail\SendOtpMail;
use App\Http\Controllers\Api\CartController;
use Illuminate\Support\Facades\Mail;


class CustomerAuthController extends Controller
{
    public function loginOrCreateAccountWithOtp(Request $request)
    {
        return $this->sendOtp($request);
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => [
                'required',
                function ($attribute, $value, $fail) {
                    $clean = preg_replace('/\D/', '', $value);
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isPhone = preg_match('/^[6-9]\d{9}$/', $clean);

                    if (!$isEmail && !$isPhone) {
                        $fail('Enter valid email or Indian phone number');
                    }
                }
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $input = trim($request->contact);
        $clean = preg_replace('/\D/', '', $input);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        $contact = $isEmail ? $input : substr($clean, -10);
        $field = $isEmail ? 'email' : 'phone_number';
        $customer = Customer::where($field, $contact)->first();
        $isNewUser = false;

        if (!$customer) {
            $isNewUser = true;
            $plainPassword = $isEmail
                ? explode('@', $contact)[0] . random_int(100, 999)
                : 'user' . substr($contact, -4) . random_int(10, 99);

            $customer = Customer::create([
                $field => $contact,
                'customer_id' => Customer::generateCustomerId(),
                'name' => $isEmail
                    ? explode('@', $contact)[0]
                    : 'User_' . substr($contact, -4),
                'password' => Hash::make($plainPassword),
                'status' => 1,
                'login_attempts' => 0
            ]);
            if (app()->environment('local')) {
                Log::info("Generated password for {$contact}: {$plainPassword}");
            }
        } else {
            $lastOtpTime = cache()->get('otp_sent_' . $customer->id);
            if ($lastOtpTime && now()->diffInSeconds($lastOtpTime) < 60) {
                $waitTime = 60 - now()->diffInSeconds($lastOtpTime);
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$waitTime} seconds before retrying",
                    'data' => ['wait_time' => $waitTime]
                ], 429);
            }
        }

        // Account lock check (time-boxed)
        if (cache()->has('locked_' . $customer->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again in 15 minutes.'
            ], 429);
        }

        $otp = random_int(100000, 999999);
        $customer->otp = $otp;
        $customer->save();
        cache()->put('otp_' . $customer->id, $otp, now()->addMinutes(5));
        cache()->put('otp_sent_' . $customer->id, now(), now()->addMinutes(1));

        if ($isEmail) {
            $this->sendEmailOtp($customer->email, $otp);
        } else {
            $this->sendSmsOtp($customer->phone_number, $otp);
        }

        if (app()->environment('local')) {
            Log::info("OTP for {$contact}: {$otp}");
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'customer_id' => $customer->id,
                'contact' => $contact,
                'contact_type' => $isEmail ? 'email' : 'phone',
                'is_new_user' => $isNewUser,
                'otp' => app()->environment('local') ? $otp : null
            ]
        ]);
    }

    public function verifyOtpAndLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => [
                'required',
                function ($attribute, $value, $fail) {
                    $clean = preg_replace('/\D/', '', $value);
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isPhone = preg_match('/^[6-9]\d{9}$/', $clean);

                    if (!$isEmail && !$isPhone) {
                        $fail('Enter valid email or phone number');
                    }
                }
            ],
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $input = trim($request->contact);
        $clean = preg_replace('/\D/', '', $input);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        $contact = $isEmail ? $input : substr($clean, -10);
        $field = $isEmail ? 'email' : 'phone_number';
        $customer = Customer::where($field, $contact)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid contact'
            ], 404);
        }

        if ($customer->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }
		/*
        if (cache()->has('locked_' . $customer->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again in 15 minutes.'
            ], 429);
        }
		*/

        $cachedOtp = cache()->get('otp_' . $customer->id);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            $customer->increment('login_attempts');
            /*
			if ($customer->login_attempts >= 5) {
                cache()->put('locked_' . $customer->id, true, now()->addMinutes(15));
            }
			*/
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        $customer->update([
            'otp' => null,
            'last_login_at' => now(),
            'login_attempts' => 0
        ]);
        cache()->forget('otp_' . $customer->id);
        cache()->forget('locked_' . $customer->id);

        $isProfileIncomplete =
            empty($customer->name) ||
            (empty($customer->email) && empty($customer->phone_number)) ||
            str_starts_with($customer->name, 'User_');

        $customer->tokens()->delete();
        $tokenResult = $customer->createToken(
            'auth_token',
            ['*'],
            now()->addHours(12)
        );
        $plainTextToken = $tokenResult->plainTextToken;
        $tokenModel = $tokenResult->accessToken;
        $tokenModel->forceFill([
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'ip_address' => request()->ip(),
        ])->save();
        /*
            CART MERGE LOGIC
        */
		Log::info('Before merge cart');
        try {
            app(CartController::class)->mergeSessionCartIntoUser($request, $customer->id);
			 Log::info('After merge cart');
        } catch (\Throwable $e) {
            Log::error('Cart merge failed: '.$e->getMessage());
			Log::error($e->getTraceAsString());
        }
        /*
            CART MERGE LOGIC
        */

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => $customer,
                'access_token' => $plainTextToken,
                'token_type' => 'Bearer',
                'is_profile_complete' => !$isProfileIncomplete,
                'expires_in' => 12 * 60 * 60
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => [
                'required',
                function ($attribute, $value, $fail) {
                    $clean = preg_replace('/\D/', '', $value);
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isPhone = preg_match('/^[6-9]\d{9}$/', $clean);

                    if (!$isEmail && !$isPhone) {
                        $fail('Enter valid email or phone number');
                    }
                }
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $input = trim($request->contact);
        $clean = preg_replace('/\D/', '', $input);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        $contact = $isEmail ? $input : substr($clean, -10);
        $field = $isEmail ? 'email' : 'phone_number';
        $customer = Customer::where($field, $contact)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }

        if ($customer->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        if (cache()->has('locked_' . $customer->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again in 15 minutes.'
            ], 429);
        }

        /* Rate limiting (60 sec) */
        $lastOtpTime = cache()->get('otp_sent_' . $customer->id);
        if ($lastOtpTime && now()->diffInSeconds($lastOtpTime) < 60) {
            $waitTime = 60 - now()->diffInSeconds($lastOtpTime);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$waitTime} seconds before retrying",
                'data' => ['wait_time' => $waitTime]
            ], 429);
        }

        $otp = random_int(100000, 999999);
        $customer->update(['otp' => $otp]);
        cache()->put('otp_' . $customer->id, $otp, now()->addMinutes(5));
        cache()->put('otp_sent_' . $customer->id, now(), now()->addMinutes(1));

        if ($isEmail) {
            $this->sendEmailOtp($customer->email, $otp);
        } else {
            $this->sendSmsOtp($customer->phone_number, $otp);
        }

        if (app()->environment('local')) {
            Log::info("Resent OTP for {$contact}: {$otp}");
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully',
            'data' => [
                'customer_id' => $customer->id,
                'contact' => $contact,
                'otp' => app()->environment('local') ? $otp : null
            ]
        ]);
    }

    private function sendEmailOtp($email, $otp)
    {
        try {
            Mail::to($email)->send(new SendOtpMail($otp));
            Log::info("OTP email sent to {$email}");
        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
        }
    }

    private function sendSmsOtp($phone, $otp)
    {
        if (app()->environment('local')) {
            Log::info("SMS OTP for {$phone}: {$otp}");
        }
    }

    public function checkContactExists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $input = trim($request->contact);
        $clean = preg_replace('/\D/', '', $input);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        $contact = $isEmail ? $input : substr($clean, -10);
        $field = $isEmail ? 'email' : 'phone_number';
        $exists = Customer::where($field, $contact)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'contact_type' => $isEmail ? 'email' : 'phone'
            ]
        ]);
    }

    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_id_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = new Client([
                'client_id' => env('GOOGLE_CLIENT_ID')
            ]);
            $payload = $client->verifyIdToken($request->google_id_token);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token'
                ], 401);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? 'Google User';
            $profileImg = $payload['picture'] ?? null;

            $query = Customer::where('google_id', $googleId);
            if ($email) {
                $query->orWhere('email', $email);
            }
            $customer = $query->first();
            $isNewUser = false;

            if (!$customer) {
                $isNewUser = true;
                $customer = Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'customer_id' => Customer::generateCustomerId(),
                    'profile_img' => $profileImg,
                    'password' => Hash::make(uniqid()),
                    'status' => 1,
                    'login_attempts' => 0
                ]);
            } else {
                if ($customer->status != 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account is deactivated'
                    ], 403);
                }
                if (!$customer->google_id) {
                    $customer->update(['google_id' => $googleId]);
                }
            }

            $isProfileIncomplete =
                empty($customer->name) ||
                (empty($customer->email) && empty($customer->phone_number)) ||
                str_starts_with($customer->name, 'User_');

            $customer->update([
                'last_login_at' => now(),
                'login_attempts' => 0
            ]);
            cache()->forget('locked_' . $customer->id);

            $customer->tokens()->delete();
            $tokenResult = $customer->createToken('auth_token', ['*'], now()->addHours(12));
            $plainTextToken = $tokenResult->plainTextToken;
            $tokenModel = $tokenResult->accessToken;

            $tokenModel->forceFill([
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'ip_address' => request()->ip()
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => [
                    'customer' => $customer,
                    'access_token' => $plainTextToken,
                    'token_type' => 'Bearer',
                    'is_profile_complete' => !$isProfileIncomplete,
                    'is_new_user' => $isNewUser
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Google login failed. Please try again.'
            ], 500);
        }
    }
}

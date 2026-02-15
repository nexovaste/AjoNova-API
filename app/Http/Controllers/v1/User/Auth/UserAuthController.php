<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Staff;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'emailAddress' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $details = Config::requestDetails();
            $staff = Staff::where('email', $request->emailAddress)->first();

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }

            $passwordIsValid = Hash::check($request->password, $staff->password);
            if ($staff->status_id === 17) {

                if ($passwordIsValid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account locked. Kindly reset your password to unlock your account.'
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }

            if ($staff->status_id !== 1) {

                if ($passwordIsValid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is suspended. Please contact support.'
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }
            $titleName = Config::getTitleNameById($staff->title_id);

            if (!$passwordIsValid) {

                $staff->increment('login_attempt');

                if ($staff->login_attempt >= 5) {

                    $staff->update([
                        'status_id' => 17,
                        'login_attempt' => 0,
                    ]);

                    $token = Password::createToken($staff);


                    // $staff->notify(new AccountLockedResetPassword(
                    //     Str::title($staff->first_name . ' ' . $staff->last_name),
                    //     $token,
                    //     $details['device'],
                    //     $details['browser'],
                    //     $details['location'],
                    //     Str::title($titleName)
                    // ));

                    return response()->json([
                        'success' => false,
                        'message' => 'Account locked. A password reset link has been sent to your email.'
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }

            $deviceId = $request->header('X-Device-ID');
            $fullName = $staff->first_name . ' ' . $staff->last_name;

            $device = DB::table('user_devices')
                ->where('user_id', $staff->staff_id)
                ->where('device_id', $deviceId)
                ->whereNotNull('verified_at')
                ->first();

            if ($device) {
                $staff->tokens()->delete();
                $tokenResult = $staff->createToken('auth_token');
                $tokenResult->accessToken->device_id = $deviceId;
                $token = $tokenResult->plainTextToken;
                $tokenResult->accessToken->save();

                $staff->update([
                    'login_attempt' => 0,
                    'last_login_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful.',
                    'accessToken' => $token
                ], 200);
            }

            $otp = rand(100000, 999999);

            DB::table('otps')->updateOrInsert(
                ['user_id' => $staff->staff_id],
                [
                    'otp_code' => Hash::make($otp),
                    'expires_at' => Carbon::now()->addMinutes(10),
                    'created_at' => now(),
                ]
            );

            // $staff->notify(new LoginOtpMail(
            //     $otp,
            //     $details['device'],
            //     $details['location'],
            //     Str::title($fullName),
            //     Str::title($titleName)
            // ));

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your registered email address. Please verify to complete login.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
                'logError' => $e->getMessage(),
            ], 500);
        }
    }
}

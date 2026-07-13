<?php

namespace App\Http\Controllers\v1\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User\User;
use App\Notifications\Member\LoginOtpMail;
use App\Notifications\member\ResetPasswordMail;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'emailAddress' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::where('email', $request->emailAddress)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }
            $passwordIsValid = Hash::check($request->password, $user->password);
            if ($user->status_id !== 1) {
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

            $titleName = Config::getTitleNameById($user->title_id);

            if (!$passwordIsValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 401);
            }

            $deviceId = $request->header('X-Device-ID');
            $fullName = $user->last_name . ' ' . $user->first_name;

            $device = DB::table('user_devices')
                ->where('user_id', $user->user_id)
                ->where('device_id', $deviceId)
                ->whereNotNull('verified_at')
                ->first();

            if ($device) {
                $user->tokens()->delete();
                $tokenResult = $user->createToken('auth_token');
                $tokenResult->accessToken->device_id = $deviceId;
                $token = $tokenResult->plainTextToken;
                $tokenResult->accessToken->save();

                $user->update([
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
                ['user_id' => $user->user_id],
                [
                    'otp_code' => Hash::make($otp),
                    'expires_at' => Carbon::now()->addMinutes(10),
                    'created_at' => now(),
                ]
            );

            $details = Config::requestDetails();

            $user->notify(new LoginOtpMail(
                $otp,
                $details['device'],
                $details['location'],
                Str::title($fullName),
                Str::title($titleName)
            ));

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

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'emailAddress' => 'required|email',
            'otpCode' => 'required|string|digits:6',
        ]);

        try {
            $deviceId = $request->header('X-Device-ID');

            if (!$deviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device ID is required.'
                ], 403);
            }

            $user = User::where('email', $request->emailAddress)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address.'
                ], 401);
            }

            $userId = $user->user_id;

            $otpRecord = DB::table('otps')
                ->where('user_id', $userId)
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.'
                ], 401);
            }

            if (Carbon::now()->gt($otpRecord->expires_at)) {
                DB::table('otps')->where('user_id', $userId)->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired.'
                ], 401);
            }

            if (!Hash::check((string) $request->otpCode, $otpRecord->otp_code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code.'
                ], 401);
            }

            DB::table('user_devices')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'device_id'   => $deviceId,
                    'device_type' => Config::requestDetails()['device'] ?? 'Unknown',
                    'verified_at' => now(),
                    'updated_at'  => now(),
                ]
            );

            DB::table('otps')->where('user_id', $userId)->delete();

            $user->tokens()->delete();
            $tokenResult = $user->createToken('auth_token');
            $tokenResult->accessToken->device_id = $deviceId;
            $tokenResult->accessToken->save();
            $token = $tokenResult->plainTextToken;
            $user->login_attempt = 0;
            $user->last_login_at = now();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP verified, Login successful.',
                'accessToken' => $token,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong Please try again later.',
                'logError' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword(Request $request, bool $resendLink = false)
    {
        $request->validate([
            'emailAddress' => 'required|string|email',
        ]);
        try {
            $email = $request->emailAddress;
            $user = User::where('email', $email)->first();

            if ($user && $user->status_id !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is suspended. Please contact support.'
                ], 403);
            }


            if ($user) {
                $fullName = $user->last_name. ' ' .$user->first_name;
                $titleName = Config::getTitleNameById($user->title_id);

                $token = Password::createToken($user);
                $user->notify(new ResetPasswordMail($token, Str::title($fullName), Str::title($titleName)));
            }

            return response()->json([
                'success' => true,
                'message' => $resendLink
                    ? 'Password reset link resent. If this email exists, you will receive it shortly.'
                    : 'If an account with this email exists, you will receive a password reset link.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong Please try again later.',
                'logError' => $e->getMessage()
            ], 500);
        }
    }

    public function resendPasswordResetLink(Request $request)
    {
        return $this->resetPassword($request, true);
    }

    private function updateUserPassword($user, $newPassword)
    {
        $user->password = $newPassword;
        $user->save();
        $user->tokens()->delete();
        DB::table('user_devices')->where('user_id', $user->user_id)->delete();
    }

    public function finishResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'emailAddress' => 'required|string|email|exists:users,email',
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);
        try {

            $user = User::where('email', $request->emailAddress)->firstOrFail();

            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be the same as the old password.'
                ], 400);
            }
            $status = Password::broker('users')->reset(
                [
                    'email' => $request->emailAddress,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token' => $request->token,
                ],

                function ($user, $password) {
                    $this->updateUserPassword($user, $password);
                    $user->status_id = 1;
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully.'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reset password. Please try again.'
                ], 500);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong Please try again later.',
                'logError' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchProfile()
    {
        $user = Auth::guard('user')->user();
        $userData = Cache::remember("user_profile_{$user->user_id}", now()->addmonth(), function () use ($user) {
            return new UserResource(
                User::with([
                    'title:title_id,title_name',
                    'gender:gender_id,gender_name',
                    'status:status_id,status_name',
                    'lga:lga_id,lga_name,state_id',
                    'lga.state:state_id,state_name,country_id',
                    'lga.state.country:country_id,country_name',
                ])->findOrFail($user->user_id)
            );
        });
        return response()->json([
            'success' => true,
            'message' => 'user profile fetched successfully.',
            'data' => $userData,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user('user');
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required|string|min:8',
            'newPassword' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);
        try {
            $user = $request->user('user');

            if (!Hash::check($request->oldPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect.'
                ], 400);
            }

            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be the same as the old password.'
                ], 400);
            }

            $user->update([
                'password' => Hash::make($request->newPassword),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong Please try again later.',
                'logError' => $e->getMessage(),
            ], 500);
        }
    }
}

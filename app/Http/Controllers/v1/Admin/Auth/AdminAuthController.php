<?php

namespace App\Http\Controllers\v1\Admin\Auth;

use Carbon\Carbon;
use App\Services\Config;
use App\Models\Admin\Staff;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use App\Notifications\Admin\LoginOtpMail;
use App\Http\Resources\Admin\AdminResource;
use App\Notifications\Admin\ResetPasswordMail;
use App\Notifications\Admin\AccountLockedResetPassword;
use Illuminate\Validation\Rules\Password as PasswordRule;


class AdminAuthController extends Controller
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
            if ($staff->status_id === 19) {

                if ($passwordIsValid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account locked. Kindly reset your password to unlock your account.'
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 299);
            }

            if ($staff->status_id !== 1) {

                if ($passwordIsValid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is suspended. Please contact support.'
                    ], 299);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 299);
            }
            $titleName = Config::getTitleNameById($staff->title_id);

            if (!$passwordIsValid) {

                $staff->increment('login_attempt');

                if ($staff->login_attempt >= 5) {

                    $staff->update([
                        'status_id' => 19,
                        'login_attempt' => 0,
                    ]);

                    $token = Password::createToken($staff);


                    $staff->notify(new AccountLockedResetPassword(
                        Str::title($staff->first_name . ' ' . $staff->last_name),
                        $token,
                        $details['device'],
                        $details['browser'],
                        $details['location'],
                        Str::title($titleName)
                    ));

                    return response()->json([
                        'success' => false,
                        'message' => 'Account locked. A password reset link has been sent to your email.'
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address or password.'
                ], 299);
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

            $staff->notify(new LoginOtpMail(
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

            $staff = Staff::where('email', $request->emailAddress)->first();

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address.'
                ], 401);
            }

            $staffId = $staff->staff_id;

            $otpRecord = DB::table('otps')
                ->where('user_id', $staffId)
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.'
                ], 299);
            }

            if (Carbon::now()->gt($otpRecord->expires_at)) {
                DB::table('otps')->where('user_id', $staffId)->delete();

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
                ['user_id' => $staffId],
                [
                    'device_id'   => $deviceId,
                    'device_type' => Config::requestDetails()['device'] ?? 'Unknown',
                    'verified_at' => now(),
                    'updated_at'  => now(),
                ]
            );

            DB::table('otps')->where('user_id', $staffId)->delete();

            $staff->tokens()->delete();
            $tokenResult = $staff->createToken('auth_token');
            $tokenResult->accessToken->device_id = $deviceId;
            $tokenResult->accessToken->save();
            $token = $tokenResult->plainTextToken;
            $staff->login_attempt = 0;
            $staff->last_login_at = now();
            $staff->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP verified. Login successful.',
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
            $staff = Staff::where('email', $email)->first();

            if ($staff && $staff->status_id !== 1 && $staff->status_id !== 17) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is suspended. Please contact support.'
                ], 403);
            }

            if ($staff) {
                $fullName = $staff ? $staff->first_name . ' ' . $staff->last_name : null;
                $titleName = Config::getTitleNameById($staff->title_id);

                $token = Password::createToken($staff);
                $staff->notify(new ResetPasswordMail($token, Str::title($fullName), Str::title($titleName)));
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

    public function finishResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'emailAddress' => 'required|string|email|exists:staff,email',
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);
        try {

            $staff = Staff::where('email', $request->emailAddress)->firstOrFail();

            if (Hash::check($request->password, $staff->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be the same as the old password.'
                ], 209);
            }
            $status = Password::broker('admins')->reset(
                [
                    'email' => $request->emailAddress,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token' => $request->token,
                ],

                function ($user, $password) {
                    $staff = Staff::where('email', $user->getEmailForPasswordReset())->first();
                    $this->updateStaffPassword($staff, $password);
                    $staff->status_id = 1;
                    $staff->save();
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
                ], 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong Please try again later.',
                'logError' => $e->getMessage()
            ], 500);
        }
    }

    private function updateStaffPassword($staff, $newPassword)
    {
        $staff->password = $newPassword;
        $staff->save();
        $staff->tokens()->delete();
        DB::table('user_devices')->where('user_id', $staff->staff_id)->delete();
    }

    public function fetchProfile()
    {
        $staff = Auth::guard('admin')->user();
        $staffData = Cache::remember("staff_profile_{$staff->staff_id}", now()->addmonth(), function () use ($staff) {
            return new AdminResource(
                Staff::with([
                    'title:title_id,title_name',
                    'gender:gender_id,gender_name',
                    'status:status_id,status_name',
                    'roles:id,name',
                    'roles.permissions:id,name',
                    'lga:lga_id,lga_name,state_id',
                    'lga.state:state_id,state_name,country_id',
                    'lga.state.country:country_id,country_name',
                ])->findOrFail($staff->staff_id)
            );
        });
        return response()->json([
            'success' => true,
            'message' => 'Staff profile fetched successfully.',
            'data' => $staffData,
        ]);
    }

    public function logout(Request $request)
    {
        $staff = $request->user('admin');
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $staff->tokens()->delete();
        $staff->getRoleNames()->first() ?? 'No Role Assigned';

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required|string',
            'newPassword' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);

        try {
            $staff = Auth::guard('admin')->user();

            if (!Hash::check($request->oldPassword, $staff->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.'
                ], 400);
            }

            if (Hash::check($request->newPassword, $staff->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be the same as the current password.'
                ], 400);
            }

          $staff->update([
                'password' => Hash::make($request->newPassword)
            ]);
            $staff->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please log in again.'
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

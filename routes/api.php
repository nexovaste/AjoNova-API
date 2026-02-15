<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Setup\LgaController;
use App\Http\Controllers\v1\Admin\RoleController;
use App\Http\Controllers\v1\Admin\AdminController;
use App\Http\Controllers\v1\Setup\StateController;
use App\Http\Controllers\v1\Setup\TitleController;
use App\Http\Controllers\v1\Setup\GenderController;
use App\Http\Controllers\v1\Setup\StatusController;
use App\Http\Controllers\v1\Setup\CountryController;
use App\Http\Controllers\v1\Admin\ActivitiesController;
use App\Http\Controllers\v1\Setup\LedgerTypeController;
use App\Http\Controllers\v1\User\UserPassportController;
use App\Http\Controllers\v1\User\Auth\UserAuthController;
use App\Http\Controllers\v1\Admin\StaffPassportController;
use App\Http\Controllers\v1\Admin\Auth\AdminAuthController;
use App\Http\Controllers\v1\Admin\UserManagementController;
use App\Http\Controllers\v1\Setup\EmployementTypeController;
use App\Http\Controllers\v1\Setup\LoanInterestTypeController;
use App\Http\Controllers\v1\Setup\PaymentChannelTypeController;
use App\Http\Controllers\v1\Setup\MemberContributionTypeController;


Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::prefix('auth')->controller(AdminAuthController::class)->group(function () {
            Route::post('login', 'login')->middleware('throttle:5,1');
            Route::post('verify-login-otp', 'verifyOtp')->middleware('throttle:5,1');
            Route::post('reset-password', 'resetPassword')->middleware('throttle:5,1');
            Route::post('resend-mail', 'resendPasswordResetLink')->middleware('throttle:5,1');
            Route::post('finish-reset-password', 'finishResetPassword')->middleware('throttle:5,1');
        });

        Route::middleware(['auth:admin', 'trust.device'])->group(function () {
            Route::apiResource('role', RoleController::class)->middleware('permission:manage role');
            Route::apiResource('activities', ActivitiesController::class)->only(['index', 'show'])->middleware('permission:view activities');
            Route::post('change-password', [AdminAuthController::class, 'changePassword'])->middleware('throttle:5,1');
            Route::apiResource('staff', AdminController::class)->middleware('permission:manage staff');
            Route::apiResource('users', UserManagementController::class)->middleware('permission:manage users');
            Route::post('staff-passport/{id}', [StaffPassportController::class, 'update']);
            Route::get('fetch-profile', [AdminAuthController::class, 'fetchProfile']);
            Route::post('logout', [AdminAuthController::class, 'logout']);
        });
        Route::post('finish-change-password', [AdminAuthController::class, 'finishChangePassword'])->middleware('throttle:5,1');
        // Route::apiResource('role', RoleController::class);
        // Route::apiResource('staff', AdminController::class);
    });

    Route::prefix('user')->group(function () {
        Route::middleware('auth:user')->group(function () {
            Route::post('auth/logout', [UserAuthController::class, 'logout']);
            Route::get('auth/profile', [UserAuthController::class, 'fetchUserProfile']);
            Route::post('auth/change-password', [UserAuthController::class, 'changePassword']);
            Route::apiResource('update', UserManagementController::class)->only(['update', 'store']);
            Route::post('user-passport/{id}', [UserPassportController::class, 'update']);
        });
        Route::apiResource('signup', UserManagementController::class)->only('store');
    });

    Route::prefix('setup')->group(function () {
        Route::get('country', [CountryController::class, 'index']);
        Route::get('state', [StateController::class, 'index']);
        Route::get('lga', [LgaController::class, 'index']);
        Route::get('gender', [GenderController::class, 'index']);
        Route::get('title', [TitleController::class, 'index']);
        Route::get('status', [StatusController::class, 'index']);
        Route::get('loan-interest-types', [LoanInterestTypeController::class, 'index']);
        Route::get('payment-channel-types', [PaymentChannelTypeController::class, 'index']);
        Route::get('ledger-types', [LedgerTypeController::class, 'index']);
        Route::get('employment-types', [EmployementTypeController::class, 'index']);
        Route::get('member-contribution-types', [MemberContributionTypeController::class, 'index']);
    });
});

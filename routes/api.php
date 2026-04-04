<?php

use App\Http\Controllers\v1\Admin\ActivitiesController;
use App\Http\Controllers\v1\Admin\ActivityLogController;
use App\Http\Controllers\v1\Admin\AdminController;
use App\Http\Controllers\v1\Admin\Auth\AdminAuthController;
use App\Http\Controllers\v1\Admin\LoanController;
use App\Http\Controllers\v1\Admin\LoanPolicyController;
use App\Http\Controllers\v1\Admin\MemberContributionController;
use App\Http\Controllers\v1\Admin\MemberSavingController;
use App\Http\Controllers\v1\Admin\MemberTargetSavingController;
use App\Http\Controllers\v1\Admin\RoleController;
use App\Http\Controllers\v1\Admin\StaffPassportController;
use App\Http\Controllers\v1\Admin\UserManagementController;
use App\Http\Controllers\v1\Setup\CountryController;
use App\Http\Controllers\v1\Setup\GenderController;
use App\Http\Controllers\v1\Setup\LgaController;
use App\Http\Controllers\v1\Setup\MembershipTypeController;
use App\Http\Controllers\v1\Setup\PaymentChannelTypeController;
use App\Http\Controllers\v1\Setup\StateController;
use App\Http\Controllers\v1\Setup\StatusController;
use App\Http\Controllers\v1\Setup\TitleController;
use App\Http\Controllers\v1\User\Auth\UserAuthController;
use App\Http\Controllers\v1\User\UserPassportController;
use App\Models\Setup\StaffCategory;
use Illuminate\Support\Facades\Route;


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
            // Route::apiResource('role', RoleController::class)->middleware('permission:manage roles');
            // Route::apiResource('staff', AdminController::class)->middleware('permission:manage staff');
            Route::post('change-password', [AdminAuthController::class, 'changePassword'])->middleware('throttle:5,1');
            Route::apiResource('users', UserManagementController::class)->middleware('permission:manage users');
            Route::post('staff-passport/{id}', [StaffPassportController::class, 'update']);
            Route::get('fetch-profile', [AdminAuthController::class, 'fetchProfile']);
            Route::post('logout', [AdminAuthController::class, 'logout']);
            Route::apiResource('loan-policies', LoanPolicyController::class)->except(['destroy']);
            Route::post('deposit-savings', [MemberSavingController::class, 'depositSavings']);
            Route::post('deposit-contribution', [MemberContributionController::class, 'depositContribution']);
            Route::post('deposit-target-savings', [MemberTargetSavingController::class, 'depositTargetSavings']);
            Route::get('fetch-all-contributions', [MemberContributionController::class, 'fetchAllContributions']);
            Route::get('fetch-single-contribution/{id}', [MemberContributionController::class, 'fetchSingleContribution']);
            Route::post('approve-withdrawal/{id}', [MemberContributionController::class, 'approveWithdrawal']);
            Route::post('approve-withdrawal/{id}', [MemberSavingController::class, 'approveWithdrawal']);
            Route::post('approve-withdrawal/{id}', [MemberTargetSavingController::class, 'approveWithdrawal']);

            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/search', [ActivityLogController::class, 'search']);
            Route::get('activity-logs/unread-count', [ActivityLogController::class, 'unreadCount']);
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
            Route::post('activity-logs/{id}/read', [ActivityLogController::class, 'markAsRead']);
            Route::post('activity-logs/mark-all-read', [ActivityLogController::class, 'markAllAsRead']);
            Route::get('activity-logs/{id}/read-by', [ActivityLogController::class, 'readBy']);
        });
        Route::post('finish-change-password', [AdminAuthController::class, 'finishChangePassword'])->middleware('throttle:5,1');
        Route::apiResource('role', RoleController::class);
        Route::apiResource('staff', AdminController::class);
    });

    Route::prefix('user')->group(function () {
        Route::prefix('auth')->controller(UserAuthController::class)->group(function () {
            Route::post('login', 'login')->middleware('throttle:5,1');
            Route::post('verify-login-otp', 'verifyOtp')->middleware('throttle:5,1');
            Route::post('reset-password', 'resetPassword')->middleware('throttle:5,1');
            Route::post('resend-mail', 'resendPasswordResetLink')->middleware('throttle:5,1');
            Route::post('finish-reset-password', 'finishResetPassword')->middleware('throttle:5,1');
        });

        Route::middleware(['auth:user', 'trust.device'])->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout']);
            Route::get('user-profile', [UserAuthController::class, 'fetchProfile']);
            Route::post('change-password', [UserAuthController::class, 'changePassword']);
            Route::apiResource('update', UserManagementController::class)->only(['update', 'store']);
            Route::post('user-passport/{id}', [UserPassportController::class, 'update']);
            Route::post('withdraw-contribution', [MemberContributionController::class, 'withdrawContribution']);
            Route::post('withdraw-savings', [MemberSavingController::class, 'withdrawSavings']);
            Route::post('withdraw-target-savings', [MemberTargetSavingController::class, 'withdrawSavings']);
            Route::post('apply-loan', [LoanController::class, 'applyLoan']);
            //  Route::post('loan-disbursement', [LoanController::class, 'loanDisbursment']);
            //  Route::post('approve-loan/{id}', [LoanController::class, 'approveLoan']);
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
        Route::get('payment-channel-types', [PaymentChannelTypeController::class, 'index']);
        Route::get('membership-types', [MembershipTypeController::class, 'index']);
        Route::get('staff-category', [StaffCategory::class, 'index']);
    });
});

<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Jobs\ActivityLogJob;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\MemberContributionSaving;
use App\Models\Admin\MemberTargetSavingSetting;
use App\Models\Admin\Wallet;
use App\Models\Setup\SetupCounter;
use App\Models\User\User;
use App\Notifications\member\signupMail;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserManagementController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $baseQuery = User::with([
                'title:title_id,title_name',
                'staffCategory:staff_category_id,staff_category_name',
                'membershipType:membership_type_id,membership_type_name',
                'gender:gender_id,gender_name',
                'status:status_id,status_name',
                'lga:lga_id,lga_name,state_id',
                'lga.state:state_id,state_name,country_id',
                'lga.state.country:country_id,country_name',
                'wallet',
            ]);

            $activeCount = (clone $baseQuery)->where('status_id', 1)->count();
            $suspendedCount = (clone $baseQuery)->where('status_id', 3)->count();

            if ($request->filled('status_id')) {
                $baseQuery->where('status_id', $request->status_id);
            }

            if ($request->filled('membership_type_id')) {
                $baseQuery->where('membership_type_id', $request->membership_type_id);
            }

            if ($request->filled('staff_category_id')) {
                $baseQuery->where('staff_category_id', $request->staff_category_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('user_id', 'like', "%{$search}%")
                        ->orWhere('membership_number', 'like', "%{$search}%");
                });
            }

            $userData = $baseQuery
                ->orderBy('last_name', 'asc')
                ->cursorPaginate(30);

            if ($userData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No member records found.',
                    'summary' => [
                        'active_count' => $activeCount,
                        'suspended_count' => $suspendedCount,
                        'total_count' => $activeCount + $suspendedCount,
                    ],
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member records fetched successfully.',
                'summary' => [
                    'active_count' => $activeCount,
                    'suspended_count' => $suspendedCount,
                    'total_count' => $activeCount + $suspendedCount,
                ],
                'data' => UserResource::collection($userData),
                'pagination' => [
                    'next_cursor' => $userData->nextCursor()?->encode(),
                    'previous_cursor' => $userData->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve member records: ' . $e->getMessage()
            ], 500);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                // ================= MEMBER =================
                'membershipNumber' => ['nullable', 'string', 'max:50'],
                'titleId' => 'required|integer|exists:setup_titles,title_id',
                'staffCategoryId' => 'required|integer|exists:staff_categories,staff_category_id',
                'membershipTypeId' => 'required|integer|exists:membership_types,membership_type_id',
                'monthlySalary' => 'required|numeric|min:0',
                'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'genderId' => 'required|integer|exists:setup_genders,gender_id',
                'emailAddress' => 'required|string|email|max:255|unique:users,email',
                'mobileNumber' => ['required', 'string', 'unique:users,mobile_number'],
                'homeAddress' => 'nullable|string|max:255',
                'dateJoined' => ['nullable', 'date'],

                // ================= MEMBER CONTRIBUTION SAVINGS =================
                'contributionAmount' => 'nullable|numeric|min:0|required_if:membershipTypeId,1',
                'savingAmount' => 'nullable|numeric|min:0|required_if:membershipTypeId,2',

                // ================= TARGET SAVINGS =================
                'targetName' => 'nullable|string|max:100',
                'targetAmount' => 'nullable|numeric|min:0|required_with:targetName',
                'startDate' => 'nullable|date|required_with:targetName',
                'durationMonths' => 'nullable|integer|min:1|required_with:targetName',
            ]);

            $admin = Auth::guard('admin')->user();

            $user = null;
            $userId = null;
            $registeredData = [];

            DB::transaction(function () use ($request, $admin, &$user, &$userId, &$registeredData) {
                $userId = SetupCounter::generateCustomId('MEM');
                $user = User::create([
                    'user_id' => $userId,
                    'title_id' => $request->titleId,
                    'staff_category_id' => $request->staffCategoryId,
                    'membership_type_id' => $request->membershipTypeId,
                    'first_name' => strtoupper($request->firstName),
                    'middle_name' => $request->middleName ? strtoupper($request->middleName) : null,
                    'last_name' => strtoupper($request->lastName),
                    'gender_id' => $request->genderId,
                    'email' => strtolower($request->emailAddress),
                    'mobile_number' => $request->mobileNumber,
                    'home_address' => $request->homeAddress ? strtoupper($request->homeAddress) : null,
                    'monthly_salary' => $request->monthlySalary,
                    'created_by' => $admin->staff_id ?? $userId,
                    'updated_by' => $admin->staff_id ?? $userId,
                    'password' => $request->lastName . '123',
                ]);

                // ================= SAVINGS =================
                if ($request->membershipTypeId == 1) {
                    MemberContributionSaving::create([
                        'user_id' => $userId,
                        'contribution_amount' => $request->contributionAmount,
                        'saving_amount' => $request->savingAmount ?: null,
                        'created_by' => $admin->staff_id ?? $userId,
                    ]);
                }

                if ($request->membershipTypeId == 2) {
                    MemberContributionSaving::create([
                        'user_id' => $userId,
                        'saving_amount' => $request->savingAmount,
                        'created_by' => $admin->staff_id ?? $userId,
                    ]);
                }

                // ================= TARGET SAVINGS =================
                if ($request->filled('targetName') && $request->filled('targetAmount') && $request->filled('startDate') && $request->filled('durationMonths')) {
                    $startDate = Carbon::parse($request->startDate);
                    $duration = (int) $request->durationMonths;
                    $endDate = $startDate->copy()->addMonths($duration)->subDay();

                    $monthlyAmount = $duration > 0 ? $request->targetAmount / $duration : 0;
                    MemberTargetSavingSetting::create([
                        'user_id' => $userId,
                        'target_name' => $request->targetName,
                        'target_amount' => $request->targetAmount,
                        'duration_months' => $duration,
                        'monthly_amount' => $monthlyAmount,
                        'start_date' => $request->startDate,
                        'end_date' => $endDate,
                        'created_by' => $admin->staff_id ?? $userId,
                    ]);
                }

                // ================= WALLET =================
                Wallet::create([
                    'user_id' => $userId,
                ]);

                // ================= EMAIL =================
                try {
                    $titleName = Config::getTitleNameById($user->title_id);
                    $fullName = $request->lastName . ' ' . $request->firstName;

                    $user->notify(new signupMail(
                        Str::title($fullName),
                        Str::title($titleName),
                        $request->emailAddress,
                        $request->lastName
                    ));
                } catch (\Throwable $e) {
                    \Log::error('Signup mail notification error: ' . $e->getMessage());
                }

                ClearCacheService::clearListCache('user_list');

                // ================= PREPARE LOG DATA =================
                $registeredData = $user->only([
                    'user_id',
                    'title_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'gender_id',
                    'email',
                    'mobile_number',
                    'status',
                    'created_by',
                    'created_at',
                ]);

                $registeredData['membership_type_id'] = $request->membershipTypeId;
                $registeredData['staff_category_id'] = $request->staffCategoryId;
                $registeredData['monthly_salary'] = $request->monthlySalary;

                if (in_array($request->membershipTypeId, [1, 2])) {
                    $registeredData['saving_details'] = [
                        'contribution_amount' => $request->contributionAmount ?? null,
                        'saving_amount' => $request->savingAmount ?? null,
                    ];
                }

                if ($request->targetName) {
                    $registeredData['target_savings'] = [
                        'target_name' => $request->targetName,
                        'target_amount' => $request->targetAmount,
                        'duration_months' => $request->durationMonths,
                        'start_date' => $request->startDate,
                    ];
                }
            });

            try {
                ActivityLogJob::dispatch(
                    modelClass: ActivityLog::class,
                    action: 'New member registration',
                    description: "A new member with ID: {$userId} has been registered.",
                    userType: $admin ? 'Staff' : 'Member',
                    performedBy: $admin->staff_id ?? $userId,
                    roleId: $admin?->roles?->pluck('id')->first() ?? 0,
                    metadata: [
                        'Registered Data' => $registeredData,
                    ],
                    deviceInfo: Config::requestDetails(),
                )->afterCommit();
            } catch (\Throwable $e) {
                \Log::error('Activity log error during signup: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Member created successfully. Default password is ' . $request->lastName . '123',
                'data' => [
                    'user_id' => $userId,
                    'email' => $request->emailAddress,
                    'default_password' => $request->lastName . '123'
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flat()->first() ?: 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Display the specified resource.
    public function show(string $id)
    {
        try {
            Cache::forget("user_profile_{$id}");
            $userData = new UserResource(User::with([
                'title:title_id,title_name',
                'gender:gender_id,gender_name',
                'status:status_id,status_name',
                'lga:lga_id,lga_name,state_id',
                'lga.state:state_id,state_name,country_id',
                'lga.state.country:country_id,country_name',
                'wallet'
            ])->findOrFail($id));

            return response()->json([
                'success' => true,
                'message' => 'Member profile fetched successfully.',
                'data' => $userData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve member profile: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $updateUser = User::where('user_id', $id)->firstOrFail();

        $request->validate([
            'membershipNumber' => 'nullable|string|max:50',
            'titleId' => 'required|integer|exists:setup_titles,title_id',
            'staffCategoryId' => 'required|integer|exists:staff_categories,staff_category_id',
            'membershipTypeId' => 'required|integer|exists:membership_types,membership_type_id',
            'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'dateOfBirth' => 'nullable|date',
            'genderId' => 'nullable|integer|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|max:255|unique:users,email,' . $id . ',user_id',
            'mobileNumber' => ['required', 'string', 'unique:users,mobile_number,' . $id . ',user_id', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'homeAddress' => 'nullable|string|max:255',
            'lgaId' => 'nullable|integer|exists:setup_lgas,lga_id',
            'nin' => 'nullable|string|max:20',
            'statusId' => 'required|integer|exists:setup_statuses,status_id',
            'monthlySalary' => 'required|numeric|min:0',
            'dateJoined' => 'nullable|date',
            'dateExited' => 'nullable|date|after_or_equal:dateJoined',
        ]);

        $admin = Auth::guard('admin')->user();
        $member = Auth::guard('user')->user();

        $beforeData = [];
        $afterData = [];

        DB::transaction(function () use ($request, $updateUser, $admin, $member, &$beforeData, &$afterData, $id) {
            $beforeData = Arr::only($updateUser->getOriginal(), [
                'user_id',
                'membership_number',
                'title_id',
                'staff_category_id',
                'membership_type_id',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'gender_id',
                'email',
                'mobile_number',
                'home_address',
                'lga_id',
                'nin',
                'status_id',
                'monthly_salary',
                'date_joined',
                'date_exited',
                'updated_by',
                'updated_at',
            ]);

            $updateUser->update([
                'title_id' => $request->titleId,
                'staff_category_id' => $request->staffCategoryId,
                'membership_type_id' => $request->membershipTypeId,
                'first_name' => strtoupper($request->firstName),
                'middle_name' => $request->middleName ? strtoupper($request->middleName) : null,
                'last_name' => strtoupper($request->lastName),
                'date_of_birth' => $request->dateOfBirth,
                'gender_id' => $request->genderId,
                'email' => strtolower($request->emailAddress),
                'mobile_number' => $request->mobileNumber,
                'home_address' => $request->homeAddress ? strtoupper($request->homeAddress) : null,
                'membership_number' => $request->filled('membershipNumber')
                    ? $request->membershipNumber
                    : null,

                'lga_id' => $request->filled('lgaId')
                    ? (int) $request->lgaId
                    : null,
                'nin' => $request->nin,
                'status_id' => $request->statusId,
                'monthly_salary' => $request->monthlySalary,
                'date_joined' => $request->dateJoined,
                'date_exited' => $request->dateExited,
                'updated_by' => $admin?->staff_id ?? $id,
            ]);

            $changes = $updateUser->getChanges();
            $afterData = Arr::only($changes, [
                'user_id',
                'membership_number',
                'title_id',
                'staff_category_id',
                'membership_type_id',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'gender_id',
                'email',
                'mobile_number',
                'home_address',
                'lga_id',
                'nin',
                'status_id',
                'monthly_salary',
                'date_joined',
                'date_exited',
                'updated_by',
                'updated_at',
            ]);

            ClearCacheService::clearListCache('user_list');
            Cache::forget("user_profile_{$id}");
        });

        if ($admin) {
            $performedBy = $admin->staff_id;
            $userType = 'Staff';
            $roleId = $admin->roles?->pluck('id')->first();
        } elseif ($member) {
            $performedBy = $member->user_id;
            $userType = 'Member';
            $roleId = null;
        } else {
            $performedBy = null;
            $userType = 'System';
            $roleId = null;
        }
        ActivityLogJob::dispatch(
            modelClass: ActivityLog::class,
            action: 'Update member',
            description: "Member with ID: {$id} was updated.",
            userType: $userType,
            performedBy: $performedBy,
            roleId: $roleId,
            metadata: [
                'before' => $beforeData,
                'after' => $afterData,
            ],
            deviceInfo: Config::requestDetails(),
        )->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully',
        ], 200);
    }
}

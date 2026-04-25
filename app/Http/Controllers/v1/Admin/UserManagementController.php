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
            $user = Auth::guard('admin')->user();
            $cursor = $request->get('cursor', 'first_page');
            $cacheKey = "user_list_{$cursor}";
            $userData = Cache::tags('user_list')->flexible($cacheKey, [now()->addMonth(), null], function () use ($user) {
                return User::with([
                    'title:title_id,title_name',
                    'gender:gender_id,gender_name',
                    'status:status_id,status_name',
                    'lga:lga_id,lga_name,state_id',
                    'lga.state:state_id,state_name,country_id',
                    'lga.state.country:country_id,country_name',
                ])
                    ->where('user_id', '!=', $user->user_id)
                    ->orderBy('last_name', 'asc')
                    ->cursorPaginate(30);
            });

            if ($userData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No member records found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member records fetched successfully.',
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
        $request->validate([
            // ================= MEMBER =================
            'membershipNumber' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'titleId' => 'required|integer|exists:setup_titles,title_id',
            'staffCategoryId' => 'required|integer|exists:staff_categories,staff_category_id',
            'membershipTypeId' => 'required|integer|exists:membership_types,membership_type_id',
            'monthlySalary' => 'required|numeric|min:0',
            'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'genderId' => 'required|integer|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|max:255|unique:users,email',
            'mobileNumber' => ['required', 'string', 'unique:users,mobile_number', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'homeAddress' => 'nullable|string|max:255',
            'dateJoined' => ['nullable', 'date', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            
            // ================= MEMBER CONTRIBUTION SAVINGS =================
            'contributionAmount' => 'required_if:membershipTypeId,1|numeric|min:0',
            'savingAmount' => 'required_if:membershipTypeId,2|numeric|min:0',

            // ================= TARGET SAVINGS =================
            'targetName' => 'nullable|string|max:100',
            'targetAmount' => 'required_with:targetName|numeric|min:0',
            'startDate' => 'required_with:targetName|date',
            'durationMonths' => 'required_with:targetName|integer|min:1',
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
                'middle_name' => strtoupper($request->middleName),
                'last_name' => strtoupper($request->lastName),
                'gender_id' => $request->genderId,
                'email' => strtolower($request->emailAddress),
                'mobile_number' => $request->mobileNumber,
                'home_address' => strtoupper($request->homeAddress),
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
            if ($request->targetName) {
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
            $titleName = Config::getTitleNameById($user->title_id);
            $fullName = $request->lastName . ' ' . $request->firstName;

            $user->notify(new signupMail(
                Str::title($fullName),
                Str::title($titleName),
                $request->emailAddress,
                $request->lastName
            ));
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
        ActivityLogJob::dispatch(
            modelClass: ActivityLog::class,
            action: 'New member registration',
            description: "A new member with ID: {$userId} has been registered.",
            userType: 'Member',
            performedBy: auth('admin')->id() ?? $userId,
            roleId: auth('admin')->user()?->roles?->pluck('id')->first() ?? 0,
            metadata: [
                'Registered Data' => $registeredData,
            ],
            deviceInfo: Config::requestDetails(),
        )->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully',
        ], 200);
    }

    // Display the specified resource.
    public function show(string $id)
    {
        try {
            $userData = Cache::remember("user_profile_{$id}", now()->addMonth(), function () use ($id) {
                return new UserResource(User::with([
                    'title:title_id,title_name',
                    'gender:gender_id,gender_name',
                    'status:status_id,status_name',
                    'lga:lga_id,lga_name,state_id',
                    'lga.state:state_id,state_name,country_id',
                    'lga.state.country:country_id,country_name'
                ])->findOrFail($id));
            });

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
                'membership_number' => $request->membershipNumber,
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
                'lga_id' => $request->lgaId,
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

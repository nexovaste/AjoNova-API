<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminResource;
use App\Jobs\ActivityLogJob;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\Staff;
use App\Models\Setup\SetupCounter;
use App\Notifications\Admin\StaffRegistration;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $admin = Auth::guard('admin')->user();
            $adminRoleId = $admin->roles->first();

            $staffData = Staff::with([
                'roles',
                'permissions',
                'title:title_id,title_name',
                'gender:gender_id,gender_name',
                'status:status_id,status_name',
                'lga:lga_id,lga_name,state_id',
                'lga.state:state_id,state_name,country_id',
                'lga.state.country:country_id,country_name',
            ])
                ->where('staff_id', '!=', $admin->staff_id)
                ->whereHas('roles', function ($query) use ($adminRoleId) {
                    $query->where('id', '>=', $adminRoleId);
                })
                ->orderBy('last_name', 'asc')
                ->orderBy('staff_id', 'asc')
                ->cursorPaginate(10);

            if ($staffData->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No staff records found.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Staff records fetched successfully.',
                'data' => AdminResource::collection($staffData),
                'pagination' => [
                    'per_page' => $staffData->perPage(),
                    'next_cursor' => optional($staffData->nextCursor())->encode(),
                    'prev_cursor' => optional($staffData->previousCursor())->encode(),
                    'has_more' => $staffData->hasMorePages(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve staff records: ' . $e->getMessage()
            ], 500);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titleId' => 'required|int|exists:setup_titles,title_id',
            'firstName'     => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName'    => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName'      => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'genderId' => 'required|int|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|unique:staff,email',
            'mobileNumber' => ['required', 'string', 'unique:staff,mobile_number', 'regex:/^\+?[1-9]\d{1,14}$/',],
            'homeAddress' => 'nullable|string',
            'roleId' => 'required|int|exists:roles,id'
        ]);

        return DB::transaction(function () use ($request) {

            $admin = Auth::guard('admin')->user();
            $staffId = SetupCounter::generateCustomId('STFF');
            $staff = Staff::create([
                'staff_id'      => $staffId,
                'title_id'      => $request->titleId,
                'first_name'    => strtoupper($request->firstName),
                'middle_name'   => strtoupper($request->middleName),
                'last_name'     => strtoupper($request->lastName),
                'gender_id'     => $request->genderId,
                'email'         => strtolower($request->emailAddress),
                'mobile_number' => $request->mobileNumber,
                'home_address'  => strtoupper($request->homeAddress),
                'created_by'    => $admin->staff_id ?? null,
                'password'      => $staffId,
            ]);
            $role = Role::findById($request->roleId, 'admin');
            $staff->assignRole($role);
            ClearCacheService::clearListCache('staff_list');
            $fullName = $staff->first_name . ' ' . ($staff->middle_name ? $staff->middle_name . ' ' : '') . $staff->last_name;
            $titleName = Config::getTitleNameById($request->titleId);
            $staff->notify(new StaffRegistration(Str::title($fullName), $staffId, Str::title($titleName)));

            $registeredData = $staff->only([
                'staff_id',
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
            $registeredData['role'] = $role->name;
            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'New staff registration',
                description: 'A new staff with ID: ' . $staffId . ' and ' . $role->name .  ' role has been registered to the system.',
                userType: 'Staff',
                performedBy: auth('admin')->id() ?? $staffId,
                roleId: auth('admin')->user()?->roles?->pluck('id')->first() ?? 0,
                metadata: [
                    'Registered Data' => $registeredData,
                ],
                deviceInfo: Config::requestDetails(),
            );

            return response()->json([
                'success'  => true,
                'message' => 'Staff created successfully',
            ], 201);
        });
    }

    // Display the specified resource.
    public function show(string $id)
    {
        try {
            $staffData = Cache::remember("staff_profile_{$id}", now()->addMonth(), function () use ($id) {
                return new AdminResource(Staff::with([
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
                'message' => 'Staff profile fetched successfully.',
                'data' => $staffData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve staff profile: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $updateAdmin = Staff::with(['roles'])->findOrFail($id);
        $request->validate([
            'titleId' => 'required|integer|exists:setup_titles,title_id',
            'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'genderId' => 'required|integer|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|unique:staff,email,' . $id . ',staff_id',
            'mobileNumber' => ['required', 'string', 'unique:staff,mobile_number,' . $id . ',staff_id', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'homeAddress' => 'required|string',
            'lgaId' => 'nullable|integer|exists:setup_lgas,lga_id',
            'dateOfBirth' => 'nullable|date|before:today',
            'nin' => ['nullable', 'string', 'unique:staff,nin,' . $id . ',staff_id', 'regex:/^[0-9]{11}$/'],
            'statusId' => 'required|integer|exists:setup_statuses,status_id',
        ]);


        try {
            $dataBeforeUpdate['role'] = $updateAdmin->roles->pluck('name')->toArray();
            $dataBeforeUpdate = Arr::only($updateAdmin->getOriginal(), [
                'title_id',
                'first_name',
                'middle_name',
                'last_name',
                'gender_id',
                'email',
                'mobile_number',
                'status_id',
                'lga_id',
                'date_of_birth',
                'nin',
                'home_address'
            ]);

            $admin = Auth::guard('admin')->user();
            $updateAdmin->update([
                'title_id'       => $request->titleId,
                'first_name'     => strtoupper($request->firstName),
                'middle_name'    => $request->middleName ? strtoupper($request->middleName) : '',
                'last_name'      => strtoupper($request->lastName),
                'gender_id'      => $request->genderId,
                'email'          => strtolower($request->emailAddress),
                'mobile_number'  => $request->mobileNumber,
                'home_address'   => strtoupper($request->homeAddress),
                'lga_id'         => $request->lgaId ?: null,
                'date_of_birth'  => $request->dateOfBirth,
                'nin'            => $request->nin,
                'status_id'      => $request->statusId,
                'updated_by'     => $admin ? $admin->staff_id : null,
            ]);

            ClearCacheService::clearListCache('staff_list');
            Cache::forget("staff_profile_{$id}");

            $dataAfterUpdate = Arr::only($updateAdmin->getChanges(), [
                'title_id',
                'first_name',
                'middle_name',
                'last_name',
                'gender_id',
                'email',
                'mobile_number',
                'home_address',
                'lga_id',
                'date_of_birth',
                'nin',
                'status_id',
                'updated_by',
            ]);
            $dataAfterUpdate['role'] = $updateAdmin->roles->pluck('name')->toArray();
            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'Staff update',
                description: 'Staff with ID: ' . $id . ' has been updated.',
                userType: 'Admin',
                performedBy: auth('admin')->id() ?? "System",
                roleId: auth('admin')->user()?->roles?->pluck('id')->first() ?? 0,
                metadata: [
                    'Before Update' => $dataBeforeUpdate,
                    'After Update' => $dataAfterUpdate,
                ],
                deviceInfo: Config::requestDetails(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Staff updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff: ' . $e->getMessage()
            ], 500);
        }
    }
}

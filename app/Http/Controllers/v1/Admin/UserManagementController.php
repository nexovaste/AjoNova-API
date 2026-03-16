<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\Setup\SetupCounter;
use App\Models\User\User;
use App\Notifications\member\signupMail;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
                    'message' => 'No user records found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User records fetched successfully.',
                'data' => UserResource::collection($userData),
                'pagination' => [
                    'next_cursor' => $userData->nextCursor()?->encode(),
                    'previous_cursor' => $userData->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user records: ' . $e->getMessage()
            ], 500);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titleId' => 'required|int|exists:setup_titles,title_id',
            'staffCategoryId' => 'required|int|exists:staff_categories,staff_category_id',
            'membershipTypeId' => 'required|int|exists:membership_types,membership_type_id',
            'monthlySalary' => 'required|numeric|min:0',
            'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'genderId' => 'required|int|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|unique:users,email',
            'mobileNumber' => ['required', 'string', 'unique:users,mobile_number', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'homeAddress' => 'nullable|string'
        ]);

        $admin = Auth::guard('admin')->user();
        $userId = SetupCounter::generateCustomId('USER');
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
            'password'      => $userId,
        ]);
        
        $titleName = Config::getTitleNameById($user->title_id);
        $fullName = $request->lastName.' '.$request->firstName;
        $user->notify(new signupMail(
            Str::title($fullName),
            Str::title($titleName),
            $request->emailAddress,
            $request->lastName
        ));
         ClearCacheService::clearListCache('user_list');
        return response()->json([
            'success'  => true,
            'message' => 'User created successfully',
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
                'message' => 'Staff profile fetched successfully.',
                'data' => $userData
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
        $updateUser = User::where('user_id', $id)->firstOrFail();
        $request->validate([
            'titleId' => 'required|int|exists:setup_titles,title_id',
            'firstName'     => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName'    => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName'      => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'genderId' => 'required|int|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|unique:users,email,' . $id . ',user_id',
            'mobileNumber' => ['required', 'string', 'unique:users,mobile_number,' . $id . ',user_id', 'regex:/^\+?[1-9]\d{1,14}$/',],
            'homeAddress' => 'required|string',
        ]);

        $staff = Auth::guard('admin')->user();
        $updateUser->update([
            'title_id'     => $request->titleId,
            'first_name'   => strtoupper($request->firstName),
            'middle_name'  => strtoupper($request->middleName),
            'last_name'    => strtoupper($request->lastName),
            'gender_id'    => $request->genderId,
            'email'        => strtolower($request->emailAddress),
            'mobile_number' => $request->mobileNumber,
            'home_address' => strtoupper($request->homeAddress),
            'updated_by'   => $staff->staff_id ?? $id,
        ]);

        ClearCacheService::clearListCache('user_list');
        Cache::forget("user_profile_{$id}");
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
        ], 200);
    }
}

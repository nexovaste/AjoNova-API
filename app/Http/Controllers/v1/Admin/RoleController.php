<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleResource;
use App\Jobs\ActivityLogJob;
use App\Models\Admin\ActivityLog;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        try {
            $user = auth('admin')->user();
            $userRole = $user->roles->first();
            $cacheKey = "admin_roles_with_permissions_{$userRole->id}";
            $roles = Cache::remember($cacheKey, now()->addMonth(), function () use ($userRole) {
                return Role::where('guard_name', 'admin')
                    ->where('id', '>=', $userRole->id)
                    ->with('permissions:id,name')
                    ->orderBy('name', 'asc')
                    ->get();
            });
            return response()->json([
                'success' => true,
                'message' => 'Roles fetched successfully.',
                'data' => RoleResource::collection($roles)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'roleName'      => 'required|string|min:3|max:50|unique:roles,name,NULL,id,guard_name,admin',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        try {
            $role = Role::create([
                'name' => ucwords($request->roleName),
                'guard_name' => 'admin',
            ])->syncPermissions($request->permissions);

            $permissionNames = $role->permissions->pluck('name')->toArray();
            $permissionIds = $role->permissions->pluck('id')->toArray();
            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'New role created',
                description: 'A new role: ' . $request->roleName . ' with the following permissions: ' . implode(', ', $permissionNames) . ' has been created.',
                userType: ' Staff',
                performedBy: auth('admin')->id() ?? "System",
                roleId: auth('admin')->user()?->roles?->pluck('id')->first() ?? 0,
                deviceInfo: Config::requestDetails(),
                metadata: [
                    'Role Name' => $role->name,
                    'Role Id' => $role->id,
                    'Permission Names' => $permissionNames,
                    'Permissions Ids' => $permissionIds,
                ]
            );
            ClearCacheService::clearListCache('admin_roles_with_permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }


    // Update the specified resource in storage.
    public function update(Request $request, int $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $dataBeforeUpdate = $role->getOriginal();
        $dataBeforeUpdate['permission'] = $role->permissions->pluck('name')->toArray();

        $validated = $request->validate([
            'roleName' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);
        try {
            $role->update([
                'name' => ucwords($validated['roleName']),
                'guard_name' => 'centralstaffs',
            ]);
            $role->syncPermissions($validated['permissions']);
            $role->load('permissions');

            $dataAfterUpdate = $role->getChanges();
            $dataAfterUpdate['permission'] = $role->permissions->pluck('name')->toArray();
            $message = $dataBeforeUpdate['name'] == $role->name ? '] has been updated with the following permissions [' : '] has been updated to [' . $dataAfterUpdate['name'] . '] with the following permissions [';
            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'Role updated',
                description: 'The role [' . $dataBeforeUpdate['name'] . $message . implode(', ', $dataAfterUpdate['permission']) . '].',
                userType: 'Staff',
                performedBy: auth('admin')->id() ?? "System",
                roleId: auth('admin')->user()?->roles?->pluck('id')->first() ?? 0,
                deviceInfo: Config::requestDetails(),
                metadata: [
                    'Before Update' => $dataBeforeUpdate,
                    'After Update' => $dataAfterUpdate,
                ]
            );

            ClearCacheService::clearListCache('admin_roles_with_permissions');
            Cache::forget("admin_role_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }
}

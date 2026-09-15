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
            $userRole = $user?->roles?->first();

            $roles = Role::where('guard_name', 'admin')
                ->when($userRole, function ($query) use ($userRole) {
                    $query->where('id', '>=', $userRole->id);
                })
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('id', 'asc')
                ->get();

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

    // Display the specified resource.
    public function show($id)
    {
        try {
            $role = Role::where('guard_name', 'admin')
                ->with('permissions:id,name')
                ->withCount('users')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Role fetched successfully.',
                'data' => new RoleResource($role)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found.'
            ], 404);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $request->validate([
            'roleName'      => 'required|string|min:3|max:50|unique:roles,name,NULL,id,guard_name,admin',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        try {
            $role = Role::create([
                'name' => ucwords($request->roleName),
                'guard_name' => 'admin',
            ])->syncPermissions($request->permissions);

            $role->load('permissions');
            $role->loadCount('users');
            $permissionNames = $role->permissions->pluck('name')->toArray();
            $permissionIds = $role->permissions->pluck('id')->toArray();

            ActivityLogJob::dispatch(
                modelClass: ActivityLog::class,
                action: 'New role created',
                description: 'A new role: ' . $request->roleName . ' with the following permissions: ' . implode(', ', $permissionNames) . ' has been created.',
                userType: 'Staff',
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
                'message' => 'Role created successfully',
                'data' => new RoleResource($role)
            ], 201);
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
        $role = Role::where('guard_name', 'admin')->with('permissions')->findOrFail($id);
        $dataBeforeUpdate = $role->getOriginal();
        $dataBeforeUpdate['permission'] = $role->permissions->pluck('name')->toArray();

        $validated = $request->validate([
            'roleName' => 'required|string|min:3|max:50|unique:roles,name,' . $id . ',id,guard_name,admin',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        try {
            $role->update([
                'name' => ucwords($validated['roleName']),
                'guard_name' => 'admin',
            ]);
            $role->syncPermissions($validated['permissions']);
            $role->load('permissions');
            $role->loadCount('users');

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
                'data' => new RoleResource($role)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove the specified resource from storage.
    public function destroy(int $id)
    {
        try {
            if ((int)$id === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Super Admin role cannot be deleted.'
                ], 403);
            }

            $role = Role::where('guard_name', 'admin')->findOrFail($id);
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role with active staff assigned. Reassign staff first.'
                ], 422);
            }

            $role->delete();
            ClearCacheService::clearListCache('admin_roles_with_permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    // Fetch all available permissions.
    public function permissions()
    {
        try {
            $permissions = Permission::where('guard_name', 'admin')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Permissions fetched successfully.',
                'data' => $permissions->map(fn($p) => [
                    'permissionId' => $p->id,
                    'permissionName' => $p->name,
                ])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions.'
            ], 500);
        }
    }
}

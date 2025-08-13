<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    // Save new role
    public function saveNewRole(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permission_ids' => 'sometimes|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permission_ids')) {
            $role->permissions()->attach($request->permission_ids);
        }

        return response()->json([
            'role' => $role,
            'code' => config('httpStatus.OK'),
            'status' => true,
            'message' => 'Role created successfully'
        ], 201);
    }

    // Get list of all permissions
    public function getListOfPermissions(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $permissions = Permission::all();

        return response()->json([
            'permissions' => $permissions,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update role permissions
    public function updateRolePermissions(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->permissions()->sync($request->permission_ids);

        $role->load('permissions');

        return response()->json([
            'role' => $role,
            'code' => 200,
            'status' => true,
            'message' => 'Role permissions updated successfully'
        ]);
    }

    // Delete role by changing active state to false
    public function deleteRole(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Prevent deletion of admin role
        if ($role->name === 'admin') {
            return response()->json(['message' => 'Cannot delete admin role'], 400);
        }

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete role that is assigned to users'], 400);
        }

        // Change active state to false instead of hard delete
        $role->update(['active' => ! $role->active]);

        $status = $role->active ? 'activated' : 'deactivated';

        return response()->json([
            'role' => $role,
            'code' => 200,
            'status' => true,
            'message' => "Role ${status} successfully"
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Save new user details
    public function saveUserDetails(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'employee_id' => 'required|integer',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userData = $validator->validated();
        $roleIds = $userData['role_ids'];
        unset($userData['role_ids']);

        // Hash the password
        $userData['password'] = Hash::make($userData['password']);

        $newUser = User::create($userData);

        // Assign roles
        $newUser->roles()->attach($roleIds);

        // Load roles for response
        $newUser->load('roles');

        return response()->json([
            'user' => $newUser,
            'code' => 200,
            'status' => true,
            'message' => 'User record saved successfully'
        ], 201);
    }

    // Get paginated list of users
    public function getPaginatedListOfUsers(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $users_list = User::with('roles')->paginate($perPage);

        $users = [
            'usersResponse' => $users_list,
            'status' => true,
            'code' => 200,
        ];
        return response()->json($users);
    }

    // Get non-paginated list of users
    public function getNonPaginatedListOfUsers(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $users = User::with('roles')->get();
        return response()->json(['users' => $users]);
    }

    // Get user details by ID
    public function getUserDetailsById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $targetUser = User::with('roles')->find($id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $targetUser]);
    }

    // Update user details
    public function updateUserDetails(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|required|string|unique:users,username,' . $id,
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'role_ids' => 'sometimes|required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userData = $validator->validated();

        // Handle role assignment if provided
        if (isset($userData['role_ids'])) {
            $roleIds = $userData['role_ids'];
            unset($userData['role_ids']);

            // Update roles
            $targetUser->roles()->sync($roleIds);
        }

        // Hash password if provided
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        $targetUser->update($userData);

        // Load roles for response
        $targetUser->load('roles');

        return response()->json([
            'user' => $targetUser,
            'code' => 200,
            'status' => true,
            'message' => 'User record updated successfully'
        ]);
    }

    // Delete user
    public function deleteUser(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_USERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent self-deletion
        if ($targetUser->id === $user->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        // Delete user's tokens first
        $targetUser->tokens()->delete();

        // Delete user
//        $targetUser->delete();
        $targetUser->update(['active' => ! $targetUser->active]);

        $status = $targetUser->active ? 'activated' : 'deactivated';

        return response()->json([
            'code' => config('httpStatus.OK'),
            'status' => true,
            'message' => "User record ${status} successfully"
        ]);
    }

    // Get all available roles (for role assignment)
    public function getAvailableRoles(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $roles = Role::with('permissions')->get();

        return response()->json([
            'roles' => $roles,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get paginated list of roles
    public function getPaginatedListOfRoles(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        $roles_list = Role::with('permissions')->paginate($perPage);
        $roles = [
            'rolesResponse' => $roles_list,
            'status' => true,
            'code' => 200,
        ];
        return response()->json($roles);
    }

    // Get non-paginated list of roles
    public function getNonPaginatedListOfRoles(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ASSIGN_ROLES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $roles = Role::with('permissions')->get();
        return response()->json([
            'roles' => $roles,
            'status' => true,
            'code' => 200,
        ]);
    }

    // Get logged-in user profile details
    public function getUserProfile(Request $request)
    {
        $user = $request->user();
        
        // Load user with roles and employee details
        $user->load(['roles', 'employee']);
        
        return response()->json([
            'user' => $user,
            'code' => 200,
            'status' => true,
            'message' => 'User profile retrieved successfully'
        ]);
    }
}

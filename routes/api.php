<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Models\User;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', fn (Request $req) => $req->user());

    // Employee endpoints
    Route::post('/employees', [EmployeeController::class, 'saveEmployeeDetails']);
    Route::get('/employees', [EmployeeController::class, 'getPaginatedListOfEmployees']);
    Route::get('/employees/all', [EmployeeController::class, 'getNonPaginatedListOfEmployees']);
    Route::get('/employees/{id}', [EmployeeController::class, 'getEmployeeDetailsById']);
    Route::put('/employees/{id}', [EmployeeController::class, 'updateEmployeeDetails']);
    Route::patch('/employees/{id}/status', [EmployeeController::class, 'changeEmployeeStatus']);

    // User endpoints
    Route::post('/users', [UserController::class, 'saveUserDetails']);
    Route::get('/users', [UserController::class, 'getPaginatedListOfUsers']);
    Route::get('/users/all', [UserController::class, 'getNonPaginatedListOfUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserDetailsById']);
    Route::put('/users/{id}', [UserController::class, 'updateUserDetails']);
    Route::patch('/users/{id}', [UserController::class, 'deleteUser']);
    Route::get('/roles', [UserController::class, 'getAvailableRoles']);
    Route::get('/roles/paginated', [UserController::class, 'getPaginatedListOfRoles']);
    Route::get('/roles/all', [UserController::class, 'getNonPaginatedListOfRoles']);

    // Role and Permission Management
    Route::post('/roles', [RoleController::class, 'saveNewRole']);
    Route::get('/permissions', [RoleController::class, 'getListOfPermissions']);
    Route::put('/roles/{id}/permissions', [RoleController::class, 'updateRolePermissions']);
    Route::patch('/roles/{id}/delete', [RoleController::class, 'deleteRole']);
});

Route::post('/reauth', function (Request $request) {
    $user = Auth::user() ?? User::where('username', $request->username)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials',

        ], 401);
    }

    // Generate and return new token
    $token = $user->createToken('api');
    $token->accessToken->expires_at = now()->addMinutes(59);
    $token->accessToken->save();

    $plainTextToken = $token->plainTextToken;
    $delimiter = "|";
    $tokenValue = Str::after($plainTextToken, $delimiter);

    return response()->json([
        'username' => $user->username,
        'access_token' => $tokenValue,
        'expires_at' => $token->accessToken->expires_at,
        'permissions' => $token->accessToken->abilities,
    ]);
});


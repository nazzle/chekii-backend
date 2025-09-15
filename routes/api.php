<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\InventoryController;
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
    Route::get('/profile', [UserController::class, 'getUserProfile']);

    // Role and Permission Management
    Route::post('/roles', [RoleController::class, 'saveNewRole']);
    Route::get('/permissions', [RoleController::class, 'getListOfPermissions']);
    Route::put('/roles/{id}/permissions', [RoleController::class, 'updateRolePermissions']);
    Route::patch('/roles/{id}/delete', [RoleController::class, 'deleteRole']);

    // Item and Inventory Management
    Route::post('/items', [ItemController::class, 'createItem']);
    Route::get('/items', [ItemController::class, 'getPaginatedItems']);
    Route::get('/items/all', [ItemController::class, 'getAllItems']);
    Route::get('/items/{id}', [ItemController::class, 'getItemById']);
    Route::put('/items/{id}', [ItemController::class, 'updateItem']);
    Route::patch('/items/{id}/delete', [ItemController::class, 'deleteItem']);

    // Inventory Management
    Route::post('/inventories', [InventoryController::class, 'createInventory']);
    Route::get('/inventories', [InventoryController::class, 'getPaginatedInventories']);
    Route::get('/inventories/all', [InventoryController::class, 'getAllInventories']);
    Route::get('/inventories/{id}', [InventoryController::class, 'getInventoryById']);
    Route::put('/inventories/{id}', [InventoryController::class, 'updateInventory']);
    Route::patch('/inventories/{id}/delete', [InventoryController::class, 'deleteInventory']);

    // Category Management
    Route::post('/categories', [CategoryController::class, 'createCategory']);
    Route::get('/categories', [CategoryController::class, 'getPaginatedCategories']);
    Route::get('/categories/all', [CategoryController::class, 'getAllCategories']);
    Route::get('/categories/{id}', [CategoryController::class, 'getCategoryById']);
    Route::put('/categories/{id}', [CategoryController::class, 'updateCategory']);
    Route::patch('/categories/{id}/delete', [CategoryController::class, 'deleteCategory']);

    // Location Management
    Route::post('/locations', [LocationController::class, 'createLocation']);
    Route::get('/locations', [LocationController::class, 'getPaginatedLocations']);
    Route::get('/locations/all', [LocationController::class, 'getAllLocations']);
    Route::get('/locations/{id}', [LocationController::class, 'getLocationById']);
    Route::put('/locations/{id}', [LocationController::class, 'updateLocation']);
    Route::patch('/locations/{id}/delete', [LocationController::class, 'deleteLocation']);

    // Movement Management
    Route::post('/movements', [MovementController::class, 'createMovement']);
    Route::get('/movements', [MovementController::class, 'getPaginatedMovements']);
    Route::get('/movements/all', [MovementController::class, 'getAllMovements']);
    Route::get('/movements/{id}', [MovementController::class, 'getMovementById']);
    Route::put('/movements/{id}', [MovementController::class, 'updateMovement']);
    Route::patch('/movements/{id}/delete', [MovementController::class, 'deleteMovement']);

    // Supplier Management
    Route::post('/suppliers', [SupplierController::class, 'createSupplier']);
    Route::get('/suppliers', [SupplierController::class, 'getPaginatedSuppliers']);
    Route::get('/suppliers/all', [SupplierController::class, 'getAllSuppliers']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'getSupplierById']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'updateSupplier']);
    Route::patch('/suppliers/{id}/delete', [SupplierController::class, 'deleteSupplier']);
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


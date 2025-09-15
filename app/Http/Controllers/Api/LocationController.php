<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    // Create new location
    public function createLocation(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:locations,code|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = Location::create([
            'active' => true,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return response()->json([
            'location' => $location,
            'code' => 200,
            'status' => true,
            'message' => 'Location created successfully'
        ], 201);
    }

    // Get paginated list of locations
    public function getPaginatedLocations(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $locations = Location::with(['inventories', 'movements'])->paginate($perPage);

        return response()->json([
            'locations' => $locations,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of locations
    public function getAllLocations(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $locations = Location::with(['inventories', 'movements'])->get();

        return response()->json([
            'locations' => $locations,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get location by ID
    public function getLocationById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $location = Location::with(['inventories', 'movements'])->find($id);
        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        return response()->json([
            'location' => $location,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update location
    public function updateLocation(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $location = Location::find($id);
        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:locations,code,' . $id . '|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location->update($validator->validated());

        return response()->json([
            'location' => $location,
            'code' => 200,
            'status' => true,
            'message' => 'Location updated successfully'
        ]);
    }

    // Delete location (soft delete by changing active status)
    public function deleteLocation(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $location = Location::find($id);
        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        // Check if location has associated inventories or movements
        if ($location->inventories()->count() > 0 || $location->movements()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location that has associated inventories or movements'
            ], 400);
        }

        // Change active state to false instead of hard delete
        $location->update(['active' => !$location->active]);
        $status = $location->active ? 'activated' : 'deactivated';

        return response()->json([
            'location' => $location,
            'code' => 200,
            'status' => true,
            'message' => "Location record {$status} successfully"
        ]);
    }
}

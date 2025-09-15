<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovementController extends Controller
{
    // Create new movement
    public function createMovement(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'location_id' => 'required|exists:locations,id',
            'movement_type' => 'required|in:in,out,transfer',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $movement = Movement::create([
            'active' => true,
            'item_id' => $request->item_id,
            'location_id' => $request->location_id,
            'movement_type' => $request->movement_type,
            'quantity' => $request->quantity,
            'reference' => $request->reference,
            'notes' => $request->notes,
        ]);

        // Load relationships for response
        $movement->load(['item', 'location']);

        return response()->json([
            'movement' => $movement,
            'code' => 200,
            'status' => true,
            'message' => 'Movement created successfully'
        ], 201);
    }

    // Get paginated list of movements
    public function getPaginatedMovements(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $movements = Movement::with(['item', 'location'])->paginate($perPage);

        return response()->json([
            'movements' => $movements,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of movements
    public function getAllMovements(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $movements = Movement::with(['item', 'location'])->get();

        return response()->json([
            'movements' => $movements,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get movement by ID
    public function getMovementById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $movement = Movement::with(['item', 'location'])->find($id);
        if (!$movement) {
            return response()->json(['message' => 'Movement not found'], 404);
        }

        return response()->json([
            'movement' => $movement,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update movement
    public function updateMovement(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $movement = Movement::find($id);
        if (!$movement) {
            return response()->json(['message' => 'Movement not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|exists:items,id',
            'location_id' => 'sometimes|required|exists:locations,id',
            'movement_type' => 'sometimes|required|in:in,out,transfer',
            'quantity' => 'sometimes|required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $movement->update($validator->validated());
        $movement->load(['item', 'location']);

        return response()->json([
            'movement' => $movement,
            'code' => 200,
            'status' => true,
            'message' => 'Movement updated successfully'
        ]);
    }

    // Delete movement (soft delete by changing active status)
    public function deleteMovement(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $movement = Movement::find($id);
        if (!$movement) {
            return response()->json(['message' => 'Movement not found'], 404);
        }

        // Change active state to false instead of hard delete
        $movement->update(['active' => !$movement->active]);
        $status = $movement->active ? 'activated' : 'deactivated';

        return response()->json([
            'movement' => $movement,
            'code' => 200,
            'status' => true,
            'message' => "Movement record {$status} successfully"
        ]);
    }
}

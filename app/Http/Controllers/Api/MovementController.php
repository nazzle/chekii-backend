<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Item;
use App\Models\Location;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            'from_location' => 'nullable|exists:locations,id',
            'to_location' => 'nullable|exists:locations,id',
            'movement_type' => 'required|in:in,out,transfer',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $itemId = (int) $request->item_id;
        $fromLocationId = $request->from_location ? (int) $request->from_location : null;
        $toLocationId = $request->to_location ? (int) $request->to_location : null;
        $movementType = $request->movement_type;
        $quantity = (int) $request->quantity;

        // Additional business validations
        if ($movementType === 'in' && !$toLocationId) {
            return response()->json(['message' => 'to_location is required for IN movement'], 422);
        }
        if ($movementType === 'out' && !$fromLocationId) {
            return response()->json(['message' => 'from_location is required for OUT movement'], 422);
        }
        if ($movementType === 'transfer') {
            if (!$fromLocationId || !$toLocationId) {
                return response()->json(['message' => 'from_location and to_location are required for TRANSFER'], 422);
            }
            if ($fromLocationId === $toLocationId) {
                return response()->json(['message' => 'Cannot transfer to and from the same location'], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Helper to get inventory row with lock
            $getInventoryLocked = function (int $itemId, int $locationId): ?Inventory {
                return Inventory::where('item_id', $itemId)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();
            };

            if ($movementType === 'in') {
                // Add quantity to to_location
                $invTo = $getInventoryLocked($itemId, $toLocationId);
                if (!$invTo) {
                    $invTo = Inventory::create([
                        'active' => true,
                        'item_id' => $itemId,
                        'location_id' => $toLocationId,
                        'quantity' => 0,
                        'reorder_level' => 0,
                    ]);
                    // Lock the newly created row
                    $invTo = Inventory::where('id', $invTo->id)->lockForUpdate()->first();
                }
                $invTo->quantity = $invTo->quantity + $quantity;
                $invTo->save();
            } elseif ($movementType === 'out') {
                // Deduct quantity from from_location
                $invFrom = $getInventoryLocked($itemId, $fromLocationId);
                if (!$invFrom || $invFrom->quantity < $quantity) {
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient quantity at source location'], 400);
                }
                $invFrom->quantity = $invFrom->quantity - $quantity;
                $invFrom->save();
            } else { // transfer
                $invFrom = $getInventoryLocked($itemId, $fromLocationId);
                if (!$invFrom || $invFrom->quantity < $quantity) {
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient quantity at source location'], 400);
                }
                $invTo = $getInventoryLocked($itemId, $toLocationId);
                if (!$invTo) {
                    $invTo = Inventory::create([
                        'active' => true,
                        'item_id' => $itemId,
                        'location_id' => $toLocationId,
                        'quantity' => 0,
                        'reorder_level' => 0,
                    ]);
                    $invTo = Inventory::where('id', $invTo->id)->lockForUpdate()->first();
                }
                $invFrom->quantity = $invFrom->quantity - $quantity;
                $invFrom->save();
                $invTo->quantity = $invTo->quantity + $quantity;
                $invTo->save();
            }

            // Record movement (store *_id columns)
            $movement = Movement::create([
                'active' => true,
                'item_id' => $itemId,
                'from_location' => $fromLocationId,
                'to_location' => $toLocationId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'reference' => $request->reference,
                'notes' => $request->notes,
            ]);

            DB::commit();

            // Load relationships for response
            $movement->load(['item', 'fromLocation', 'toLocation']);

            return response()->json([
                'movement' => $movement,
                'code' => 200,
                'status' => true,
                'message' => 'Movement created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create movement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get paginated list of movements
    public function getPaginatedMovements(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $movements = Movement::with(['item', 'fromLocation', 'toLocation'])->paginate($perPage);

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

        $movements = Movement::with(['item', 'fromLocation', 'toLocation'])->get();

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

        $movement = Movement::with(['item', 'fromLocation', 'toLocation'])->find($id);
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
            'from_location' => 'nullable|exists:locations,id',
            'to_location' => 'nullable|exists:locations,id',
            'movement_type' => 'sometimes|required|in:in,out,transfer',
            'quantity' => 'sometimes|required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        // Map request keys to *_id columns if present
        if (array_key_exists('from_location', $data)) {
            $data['from_location_id'] = $data['from_location'];
            unset($data['from_location']);
        }
        if (array_key_exists('to_location', $data)) {
            $data['to_location_id'] = $data['to_location'];
            unset($data['to_location']);
        }
        if (isset($data['from_location_id']) && isset($data['to_location_id']) && $data['from_location_id'] === $data['to_location_id']) {
            return response()->json(['message' => 'Cannot transfer to and from the same location'], 422);
        }

        $movement->update($data);
        $movement->load(['item', 'fromLocation', 'toLocation']);

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

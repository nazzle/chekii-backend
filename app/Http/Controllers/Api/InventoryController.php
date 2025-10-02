<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    // Create new inventory
    public function createInventory(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if inventory already exists for this item
        $existingInventory = Inventory::where('item_id', $request->item_id)->first();
        if ($existingInventory) {
            return response()->json([
                'message' => 'Inventory already exists for this item'
            ], 400);
        }

        $inventory = Inventory::create([
            'active' => true,
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
            'reorder_level' => $request->reorder_level,
            'location_id' => $request->location_id,
        ]);

        // Load relationships for response
        $inventory->load(['item', 'location', 'supplier']);

        return response()->json([
            'inventory' => $inventory,
            'code' => 200,
            'status' => true,
            'message' => 'Inventory created successfully'
        ], 201);
    }

    // Get paginated list of inventories
    public function getPaginatedInventories(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $inventories = Inventory::with(['item', 'location'])->paginate($perPage);

        return response()->json([
            'inventories' => $inventories,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of inventories
    public function getAllInventories(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventories = Inventory::with(['item', 'location'])->get();

        return response()->json([
            'inventories' => $inventories,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get inventory by ID
    public function getInventoryById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventory = Inventory::with(['item', 'location', 'supplier'])->find($id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        return response()->json([
            'inventory' => $inventory,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update inventory
    public function updateInventory(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|exists:items,id',
            'quantity' => 'sometimes|required|integer|min:0',
            'reorder_level' => 'sometimes|required|integer|min:0',
            'location_id' => 'sometimes|required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventory->update($validator->validated());
        $inventory->load(['item', 'location', 'supplier']);

        return response()->json([
            'inventory' => $inventory,
            'code' => 200,
            'status' => true,
            'message' => 'Inventory updated successfully'
        ]);
    }

    // Delete inventory (soft delete by changing active status)
    public function deleteInventory(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        // Change active state to false instead of hard delete
        $inventory->update(['active' => !$inventory->active]);
        $status = $inventory->active ? 'activated' : 'deactivated';

        return response()->json([
            'inventory' => $inventory,
            'code' => 200,
            'status' => true,
            'message' => "Inventory record {$status} successfully"
        ]);
    }
}

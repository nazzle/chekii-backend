<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryRecord;
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
            'supplier_id' => 'required|exists:suppliers,id',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if inventory already exists for this item at this location
        $existingInventory = Inventory::where('item_id', $request->item_id)
            ->where('location_id', $request->location_id)
            ->first();

        if ($existingInventory) {
            // Add quantity to existing inventory record
            $existingInventory->increment('quantity', $request->quantity);
            $existingInventory->load(['item', 'location', 'supplier', 'country']);

            // Create inventory record for tracking
            InventoryRecord::create([
                'active' => true,
                'item_id' => $request->item_id,
                'supplier_id' => $request->supplier_id,
                'quantity' => $request->quantity,
                'location_id' => $request->location_id,
            ]);

            return response()->json([
                'inventory' => $existingInventory,
                'code' => 200,
                'status' => true,
                'message' => 'Quantity added to existing inventory successfully'
            ], 200);
        }

        // Create new inventory record
        $inventory = Inventory::create([
            'active' => true,
            'item_id' => $request->item_id,
            'supplier_id' => $request->supplier_id,
            'country_id' => $request->country_id,
            'quantity' => $request->quantity,
            'reorder_level' => $request->reorder_level,
            'location_id' => $request->location_id,
        ]);

        // Create inventory record for tracking
        InventoryRecord::create([
            'active' => true,
            'item_id' => $request->item_id,
            'supplier_id' => $request->supplier_id,
            'quantity' => $request->quantity,
            'location_id' => $request->location_id,
        ]);

        // Load relationships for response
        $inventory->load(['item', 'location', 'supplier', 'country']);

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

        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
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

    // Implement inventory search
    public function searchInventoryByAttributes(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        // Require at least keyword or location_id
        if (!$request->filled('keyword') || !$request->filled('location_id')) {
            return response()->json([
                'message' => 'Please provide keyword or location_id to search',
                'inventories' => [],
                'code' => 400,
                'status' => false
            ], 400);
        }

        $query = Inventory::with(['item', 'location']);

        // Filter by location if provided
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Search by keyword in item fields (REQUIRED filter - not optional)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->whereHas('item', function ($itemQuery) use ($keyword) {
                $itemQuery->where('item_code', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            });
        }

        $inventories = $query->get();

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
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'country_id' => 'sometimes|required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventory->update($validator->validated());
        $inventory->load(['item', 'location']);

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

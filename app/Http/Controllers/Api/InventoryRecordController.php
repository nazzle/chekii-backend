<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryRecordController extends Controller
{
    /**
     * Create a new inventory record.
     */
    public function createInventoryRecord(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:0',
            'location_id' => 'required|exists:locations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventoryRecord = InventoryRecord::create([
            'active' => $request->boolean('active', true),
            'item_id' => $request->item_id,
            'supplier_id' => $request->supplier_id,
            'quantity' => $request->quantity,
            'location_id' => $request->location_id,
        ]);

        $inventoryRecord->load(['item', 'location', 'supplier']);

        return response()->json([
            'inventory_record' => $inventoryRecord,
            'code' => 200,
            'status' => true,
            'message' => 'Inventory record created successfully',
        ], 201);
    }

    /**
     * Get paginated list of inventory records.
     */
    public function getPaginatedInventoryRecords(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        $inventoryRecords = InventoryRecord::with(['item', 'location', 'supplier'])->paginate($perPage);

        return response()->json([
            'inventory_records' => $inventoryRecords,
            'code' => 200,
            'status' => true,
        ]);
    }

    /**
     * Get all inventory records (non-paginated).
     */
    public function getAllInventoryRecords(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventoryRecords = InventoryRecord::with(['item', 'location', 'supplier'])->get();

        return response()->json([
            'inventory_records' => $inventoryRecords,
            'code' => 200,
            'status' => true,
        ]);
    }

    /**
     * Get a single inventory record by ID.
     */
    public function getInventoryRecordById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventoryRecord = InventoryRecord::with(['item', 'location', 'supplier'])->find($id);
        if (!$inventoryRecord) {
            return response()->json(['message' => 'Inventory record not found'], 404);
        }

        return response()->json([
            'inventory_record' => $inventoryRecord,
            'code' => 200,
            'status' => true,
        ]);
    }

    /**
     * Update an inventory record.
     */
    public function updateInventoryRecord(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventoryRecord = InventoryRecord::find($id);
        if (!$inventoryRecord) {
            return response()->json(['message' => 'Inventory record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|exists:items,id',
            'quantity' => 'sometimes|required|integer|min:0',
            'location_id' => 'sometimes|required|exists:locations,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inventoryRecord->update($validator->validated());
        $inventoryRecord->load(['item', 'location', 'supplier']);

        return response()->json([
            'inventory_record' => $inventoryRecord,
            'code' => 200,
            'status' => true,
            'message' => 'Inventory record updated successfully',
        ]);
    }

    /**
     * Soft delete: toggle active status of an inventory record.
     */
    public function deleteInventoryRecord(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('ADJUST_INVENTORY')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $inventoryRecord = InventoryRecord::find($id);
        if (!$inventoryRecord) {
            return response()->json(['message' => 'Inventory record not found'], 404);
        }

        $inventoryRecord->update(['active' => !$inventoryRecord->active]);
        $status = $inventoryRecord->active ? 'activated' : 'deactivated';

        return response()->json([
            'inventory_record' => $inventoryRecord,
            'code' => 200,
            'status' => true,
            'message' => "Inventory record {$status} successfully",
        ]);
    }
}

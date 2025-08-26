<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    // Add new item
    public function addItem(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'barcode' => 'nullable|string|unique:items,barcode',
            'item_code' => 'required|string|unique:items,item_code',
            'description' => 'nullable|string',
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'gender' => 'nullable|in:male,female,unisex',
            'age' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Create the item
            $item = Item::create([
                'active' => true,
                'barcode' => $request->barcode,
                'item_code' => $request->item_code,
                'description' => $request->description,
                'buying_price' => $request->buying_price,
                'selling_price' => $request->selling_price,
                'gender' => $request->gender,
                'age' => $request->age,
            ]);

            // Create the inventory record
            $inventory = Inventory::create([
                'active' => true,
                'item_id' => $item->id,
                'quantity' => $request->quantity,
                'reorder_level' => $request->reorder_level,
            ]);

            DB::commit();

            // Load relationships for response
            $item->load('inventory');

            return response()->json([
                'item' => $item,
                'inventory' => $inventory,
                'code' => 200,
                'status' => true,
                'message' => 'Item and inventory created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add inventory for existing item
    public function addInventory(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
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

        // Create the inventory record
        $inventory = Inventory::create([
            'active' => true,
            'item_id' => $request->item_id,
            'supplier_id' => $request->supplier_id,
            'quantity' => $request->quantity,
            'reorder_level' => $request->reorder_level,
        ]);

        // Load the item relationship
        $inventory->load('item');

        return response()->json([
            'inventory' => $inventory,
            'code' => 200,
            'status' => true,
            'message' => 'Inventory created successfully'
        ], 201);
    }
}

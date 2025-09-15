<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    // Create new item
    public function createItem(Request $request)
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
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = Item::create([
            'active' => true,
            'barcode' => $request->barcode,
            'item_code' => $request->item_code,
            'description' => $request->description,
            'buying_price' => $request->buying_price,
            'selling_price' => $request->selling_price,
            'gender' => $request->gender,
            'age' => $request->age,
            'category_id' => $request->category_id,
        ]);

        // Load category relationship for response
        $item->load('category');

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => 'Item created successfully'
        ], 201);
    }

    // Get paginated list of items
    public function getPaginatedItems(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $items = Item::with(['category', 'inventory'])->paginate($perPage);

        return response()->json([
            'items' => $items,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of items
    public function getAllItems(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $items = Item::with(['category', 'inventory'])->get();

        return response()->json([
            'items' => $items,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get item by ID
    public function getItemById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::with(['category', 'inventory'])->find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update item
    public function updateItem(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'barcode' => 'nullable|string|unique:items,barcode,' . $id,
            'item_code' => 'sometimes|required|string|unique:items,item_code,' . $id,
            'description' => 'nullable|string',
            'buying_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'gender' => 'nullable|in:male,female,unisex',
            'age' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update($validator->validated());
        $item->load(['category', 'inventory']);

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => 'Item updated successfully'
        ]);
    }

    // Delete item (soft delete by changing active status)
    public function deleteItem(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Check if item has associated inventory or movements
        if ($item->inventory || $item->movements()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete item that has associated inventory or movements'
            ], 400);
        }

        // Change active state to false instead of hard delete
        $item->update(['active' => !$item->active]);
        $status = $item->active ? 'activated' : 'deactivated';

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => "Item record {$status} successfully"
        ]);
    }
}
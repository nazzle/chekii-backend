<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    public function createDiscount(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'definition_id' => 'required|exists:discount_definitions,id',
            'item_id' => 'nullable|exists:items,id',
            'category_id' => 'nullable|exists:categories,id',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate that either item_id or category_id is provided, but not both
        if ($request->item_id && $request->category_id) {
            return response()->json([
                'message' => 'Discount can only be applied to either an item or a category, not both'
            ], 422);
        }

        if (!$request->item_id && !$request->category_id) {
            return response()->json([
                'message' => 'Discount must be applied to either an item or a category'
            ], 422);
        }

        $discount = Discount::create($validator->validated() + ['active' => true]);
        $discount->load(['discountDefinition', 'item', 'category']);

        return response()->json([
            'discount' => $discount,
            'status' => true,
            'code' => 200,
            'message' => 'Discount created successfully'
        ], 201);
    }

    public function getPaginatedDiscounts(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $discounts = Discount::with(['discountDefinition', 'item', 'category'])
//            ->whereNull('sale_id')
            ->paginate($perPage);

        return response()->json([
            'discounts' => $discounts,
            'status' => true,
            'code' => 200
        ]);
    }

    public function getAllDiscounts(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $discounts = Discount::with(['discountDefinition', 'item', 'category'])
            ->whereNull('sale_id')
            ->get();

        return response()->json([
            'discounts' => $discounts,
            'status' => true,
            'code' => 200
        ]);
    }

    public function getDiscountById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $discount = Discount::with(['discountDefinition', 'item', 'category'])->find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }

        return response()->json([
            'discount' => $discount,
            'status' => true,
            'code' => 200
        ]);
    }

    public function updateDiscount(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $discount = Discount::find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'definition_id' => 'sometimes|required|exists:discount_definitions,id',
            'item_id' => 'nullable|exists:items,id',
            'category_id' => 'nullable|exists:categories,id',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate that either item_id or category_id is provided, but not both
        $itemId = $request->has('item_id') ? $request->item_id : $discount->item_id;
        $categoryId = $request->has('category_id') ? $request->category_id : $discount->category_id;

        if ($itemId && $categoryId) {
            return response()->json([
                'message' => 'Discount can only be applied to either an item or a category, not both'
            ], 422);
        }

        if (!$itemId && !$categoryId) {
            return response()->json([
                'message' => 'Discount must be applied to either an item or a category'
            ], 422);
        }

        $discount->update($validator->validated());
        $discount->load(['discountDefinition', 'item', 'category']);

        return response()->json([
            'discount' => $discount,
            'status' => true,
            'code' => 200,
            'message' => 'Discount updated successfully'
        ]);
    }

    public function deleteDiscount(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $discount = Discount::find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }

        $discount->update(['active' => ! $discount->active]);
        $status = $discount->active ? 'activated' : 'deactivated';

        return response()->json([
            'discount' => $discount,
            'status' => true,
            'code' => 200,
            'message' => "Discount record {$status} successfully"
        ]);
    }

    // Get active discounts for a specific item
    public function getDiscountsByItem(Request $request, $itemId)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $now = now();
        $discounts = Discount::with(['discountDefinition'])
            ->where('item_id', $itemId)
            ->where('active', true)
            ->whereNull('sale_id')
            ->where(function($query) use ($now) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->get();

        return response()->json([
            'discounts' => $discounts,
            'status' => true,
            'code' => 200
        ]);
    }

    // Get active discounts for a specific category
    public function getDiscountsByCategory(Request $request, $categoryId)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $now = now();
        $discounts = Discount::with(['discountDefinition'])
            ->where('category_id', $categoryId)
            ->where('active', true)
            ->whereNull('sale_id')
            ->where(function($query) use ($now) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->get();

        return response()->json([
            'discounts' => $discounts,
            'status' => true,
            'code' => 200
        ]);
    }
}


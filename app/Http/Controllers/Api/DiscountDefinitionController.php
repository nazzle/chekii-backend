<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountDefinitionController extends Controller
{
    public function createDiscountDefinition(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $discount = DiscountDefinition::create($validator->validated() + ['active' => true]);
        return response()->json(['discount_definition' => $discount, 'status' => true, 'code' => 200, 'message' => 'Discount definition created successfully'], 201);
    }

    public function getPaginatedDiscountDefinitions(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['discount_definitions' => DiscountDefinition::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllDiscountDefinitions(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['discount_definitions' => DiscountDefinition::all(), 'status' => true, 'code' => 200]);
    }

    public function getDiscountDefinitionById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $discount = DiscountDefinition::find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount definition not found'], 404);
        }
        return response()->json(['discount_definition' => $discount, 'status' => true, 'code' => 200]);
    }

    public function updateDiscountDefinition(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $discount = DiscountDefinition::find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount definition not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:percentage,fixed',
            'value' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $discount->update($validator->validated());
        return response()->json(['discount_definition' => $discount, 'status' => true, 'code' => 200, 'message' => 'Discount definition updated successfully']);
    }

    public function deleteDiscountDefinition(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $discount = DiscountDefinition::find($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount definition not found'], 404);
        }
        $discount->update(['active' => ! $discount->active]);
        $status = $discount->active ? 'activated' : 'deactivated';
        return response()->json(['discount_definition' => $discount, 'status' => true, 'code' => 200, 'message' => "Discount definition record {$status} successfully"]);
    }
}


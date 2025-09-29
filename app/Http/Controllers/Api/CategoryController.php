<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // Create new category
    public function createCategory(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:categories,code|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = Category::create([
            'active' => true,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return response()->json([
            'category' => $category,
            'code' => 200,
            'status' => true,
            'message' => 'Category created successfully'
        ], 201);
    }

    // Get paginated list of categories
    public function getPaginatedCategories(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 20);
        $categories = Category::with('items')->paginate($perPage);

        return response()->json([
            'categories' => $categories,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of categories
    public function getAllCategories(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $categories = Category::with('items')->get();

        return response()->json([
            'categories' => $categories,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get category by ID
    public function getCategoryById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $category = Category::with('items')->find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json([
            'category' => $category,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update category
    public function updateCategory(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:categories,code,' . $id . '|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'category' => $category,
            'code' => 200,
            'status' => true,
            'message' => 'Category updated successfully'
        ]);
    }

    // Delete category (soft delete by changing active status)
    public function deleteCategory(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_CATEGORIES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Check if category has associated items
        if ($category->items()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category that has associated items'
            ], 400);
        }

        // Change active state to false instead of hard delete
        $category->update(['active' => !$category->active]);
        $status = $category->active ? 'activated' : 'deactivated';

        return response()->json([
            'category' => $category,
            'code' => 200,
            'status' => true,
            'message' => "Category record {$status} successfully"
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    // Create new supplier
    public function createSupplier(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:suppliers,code|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create([
            'active' => true,
            'name' => $request->name,
            'code' => $request->code,
            'country' => $request->country,
        ]);

        return response()->json([
            'supplier' => $supplier,
            'code' => 200,
            'status' => true,
            'message' => 'Supplier created successfully'
        ], 201);
    }

    // Get paginated list of suppliers
    public function getPaginatedSuppliers(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $suppliers = Supplier::with('items')->paginate($perPage);

        return response()->json([
            'suppliers' => $suppliers,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of suppliers
    public function getAllSuppliers(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $suppliers = Supplier::with('items')->get();

        return response()->json([
            'suppliers' => $suppliers,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get supplier by ID
    public function getSupplierById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $supplier = Supplier::with('inventories')->find($id);
        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        return response()->json([
            'supplier' => $supplier,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update supplier
    public function updateSupplier(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:suppliers,code,' . $id . '|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier->update($validator->validated());

        return response()->json([
            'supplier' => $supplier,
            'code' => 200,
            'status' => true,
            'message' => 'Supplier updated successfully'
        ]);
    }

    // Delete supplier (soft delete by changing active status)
    public function deleteSupplier(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        // Check if supplier has associated inventories
        if ($supplier->inventories()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete supplier that has associated inventories'
            ], 400);
        }

        // Change active state to false instead of hard delete
        $supplier->update(['active' => ! $supplier->active]);
        $status = $supplier->active ? 'activated' : 'deactivated';

        return response()->json([
            'supplier' => $supplier,
            'code' => 200,
            'status' => true,
            'message' => "Supplier record {$status} successfully"
        ]);
    }
}

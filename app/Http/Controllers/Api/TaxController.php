<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    public function createTax(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $tax = Tax::create($validator->validated() + ['active' => true]);
        return response()->json(['tax' => $tax, 'status' => true, 'code' => 200, 'message' => 'Tax created successfully'], 201);
    }

    public function getPaginatedTaxes(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        return response()->json(['taxes' => Tax::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllTaxes(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['taxes' => Tax::all(), 'status' => true, 'code' => 200]);
    }

    public function getTaxById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $tax = Tax::find($id);
        if (! $tax) {
            return response()->json(['message' => 'Tax not found'], 404);
        }
        return response()->json(['tax' => $tax, 'status' => true, 'code' => 200]);
    }

    public function updateTax(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $tax = Tax::find($id);
        if (! $tax) {
            return response()->json(['message' => 'Tax not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'rate' => 'sometimes|required|numeric|min:0|max:100',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $tax->update($validator->validated());
        return response()->json(['tax' => $tax, 'status' => true, 'code' => 200, 'message' => 'Tax updated successfully']);
    }

    public function deleteTax(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $tax = Tax::find($id);
        if (! $tax) {
            return response()->json(['message' => 'Tax not found'], 404);
        }
        $tax->update(['active' => ! $tax->active]);
        $status = $tax->active ? 'activated' : 'deactivated';
        return response()->json(['tax' => $tax, 'status' => true, 'code' => 200, 'message' => "Tax record {$status} successfully"]);
    }
}


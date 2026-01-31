<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function createCustomer(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'loyalty_card_number' => 'nullable|string',
            'loyalty_points' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $customer = Customer::create($validator->validated() + ['active' => true]);
        return response()->json(['customer' => $customer, 'status' => true, 'code' => 200, 'message' => 'Customer created successfully'], 201);
    }

    public function getPaginatedCustomers(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        return response()->json(['customers' => Customer::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllCustomers(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['customers' => Customer::all(), 'status' => true, 'code' => 200]);
    }

    public function getCustomerById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $customer = Customer::with('sales')->find($id);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        return response()->json(['customer' => $customer, 'status' => true, 'code' => 200]);
    }

    public function updateCustomer(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $customer = Customer::find($id);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'loyalty_card_number' => 'nullable|string',
            'loyalty_points' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $customer->update($validator->validated());
        return response()->json(['customer' => $customer, 'status' => true, 'code' => 200, 'message' => 'Customer updated successfully']);
    }

    public function deleteCustomer(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_CUSTOMERS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $customer = Customer::find($id);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $customer->update(['active' => ! $customer->active]);
        $status = $customer->active ? 'activated' : 'deactivated';
        return response()->json(['customer' => $customer, 'status' => true, 'code' => 200, 'message' => "Customer record {$status} successfully"]);
    }
}


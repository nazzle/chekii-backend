<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentOptionController extends Controller
{
    public function createPaymentOption(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:payment_options,code|max:50',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $payment = PaymentOption::create($validator->validated() + ['active' => true]);
        return response()->json(['payment_option' => $payment, 'status' => true, 'code' => 200], 201);
    }

    public function getPaginatedPaymentOptions(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['payment_options' => PaymentOption::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllPaymentOptions(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['payment_options' => PaymentOption::all(), 'status' => true, 'code' => 200]);
    }

    public function getPaymentOptionById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = PaymentOption::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment option not found'], 404);
        }
        return response()->json(['payment_option' => $payment, 'status' => true, 'code' => 200]);
    }

    public function updatePaymentOption(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = PaymentOption::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment option not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:payment_options,code,' . $id . '|max:50',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $payment->update($validator->validated());
        return response()->json(['payment_option' => $payment, 'status' => true, 'code' => 200, 'message' => 'Payment option updated successfully']);
    }

    public function deletePaymentOption(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PAYMENT_OPTIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = PaymentOption::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment option not found'], 404);
        }
        $payment->update(['active' => ! $payment->active]);
        $status = $payment->active ? 'activated' : 'deactivated';
        return response()->json(['payment_option' => $payment, 'status' => true, 'code' => 200, 'message' => "Payment option record {$status} successfully"]);
    }
}

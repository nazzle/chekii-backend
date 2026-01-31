<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_SALE')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|exists:sales,id',
            'payment_option_id' => 'required|exists:payment_options,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $payment = Payment::create($validator->validated() + ['active' => true]);
        $payment->load(['sale', 'paymentOption']);
        return response()->json(['payment' => $payment, 'status' => true, 'code' => 200], 201);
    }

    public function getPaginatedPayments(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        $payments = Payment::with(['sale', 'paymentOption'])->paginate($perPage);
        return response()->json(['payments' => $payments, 'status' => true, 'code' => 200]);
    }

    public function getAllPayments(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payments = Payment::with(['sale', 'paymentOption'])->get();
        return response()->json(['payments' => $payments, 'status' => true, 'code' => 200]);
    }

    public function getPaymentById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = Payment::with(['sale', 'paymentOption'])->find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        return response()->json(['payment' => $payment, 'status' => true, 'code' => 200]);
    }

    public function updatePayment(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'sale_id' => 'sometimes|required|exists:sales,id',
            'payment_option_id' => 'sometimes|required|exists:payment_options,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'reference' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $payment->update($validator->validated());
        return response()->json(['payment' => $payment, 'status' => true, 'code' => 200, 'message' => 'Payment updated successfully']);
    }

    public function deletePayment(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $payment->update(['active' => ! $payment->active]);
        $status = $payment->active ? 'activated' : 'deactivated';
        return response()->json(['payment' => $payment, 'status' => true, 'code' => 200, 'message' => "Payment record {$status} successfully"]);
    }
}


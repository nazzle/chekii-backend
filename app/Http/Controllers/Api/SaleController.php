<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Discount;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function createSale(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_SALE')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'location_id' => 'required|integer|exists:locations,id',
            'payment_method_id' => 'required|integer|exists:payment_options,id',
//            'sale_date' => 'required|date',
            'total' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
//            'net_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
            'reference' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'active' => true,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_id' => $request->customer_id,
                'location_id' => $request->location_id,
                'user_id' => $request->user()->id,
                'sale_date' => date('Y-m-d h:m:s'),
                'total_amount' => $request->total,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'payment_option_id' => $request->payment_method_id,
                'subtotal' => $request->subtotal,
                'amount_paid' => $request->amount_received,
                'change_amount' => $request->change,
                'status' => $request->status,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'original_sale_id' => $request->original_sale_id,
                'is_refund' => $request->is_refund ?? 0,
            ]);

            foreach ($request->items as $itemData) {
                SaleItem::create([
                    'active' => true,
                    'sale_id' => $sale->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
//                    'tax_amount' => $itemData['tax_amount'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                ]);
            }

            DB::commit();
            $sale->load(['saleItems.item', 'customer', 'user']);

            return response()->json(['sale' => $sale, 'status' => true, 'code' => 200], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sale', 'error' => $e->getMessage()], 500);
        }
    }

    public function getPaginatedSales(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        $sales = Sale::with(['customer', 'user', 'saleItems'])->paginate($perPage);
        return response()->json(['sales' => $sales, 'status' => true, 'code' => 200]);
    }

    public function getAllSales(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $sales = Sale::with(['customer', 'user', 'saleItems'])->get();
        return response()->json(['sales' => $sales, 'status' => true, 'code' => 200]);
    }

    public function getSaleById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $sale = Sale::with(['customer', 'user', 'saleItems.item', 'discounts', 'payments'])->find($id);
        if (! $sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        return response()->json(['sale' => $sale, 'status' => true, 'code' => 200]);
    }

    public function updateSale(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $sale = Sale::find($id);
        if (! $sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'status' => 'sometimes|required|in:pending,completed,cancelled',
            'reference' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $sale->update($validator->validated());
        return response()->json(['sale' => $sale, 'status' => true, 'code' => 200, 'message' => 'Sale updated successfully']);
    }

    public function deleteSale(Request $request, $id)
    {
        if (! $request->user()->hasPermission('CANCEL_SALE')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $sale = Sale::find($id);
        if (! $sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        $sale->update(['active' => ! $sale->active, 'status' => $sale->active ? 'completed' : 'cancelled']);
        $status = $sale->active ? 'activated' : 'cancelled';
        return response()->json(['sale' => $sale, 'status' => true, 'code' => 200, 'message' => "Sale {$status} successfully"]);
    }
}


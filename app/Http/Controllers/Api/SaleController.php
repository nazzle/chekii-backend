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
            'total' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'amount_received' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'original_sale_id' => 'nullable|integer',
            'is_refund' => 'nullable|boolean',
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
                'subtotal' => $request->input('subtotal'),
                'amount_paid' => $request->input('amount_received'),
                'change_amount' => $request->input('change'),
                'status' => $request->status,
                'reference' => $request->input('reference'),
                'notes' => $request->input('notes'),
                'original_sale_id' => $request->input('original_sale_id'),
                'is_refund' => (bool) $request->input('is_refund', false),
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

            $payments = Payment::create([
                'sale_id' => $sale->id,
                'payment_option_id' => $request->payment_method_id,
                'amount' => $request->input('amount_received'),
                'status' => $request->status,
            ]);

            DB::commit();
            $sale->load(['saleItems.item', 'customer', 'user']);

            return response()->json(['sale' => $sale, 'status' => true, 'code' => 200], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = config('app.debug') ? $e->getMessage() : 'Failed to create sale';
            return response()->json(['message' => 'Failed to create sale', 'error' => $message], 500);
        }
    }

    public function getPaginatedSales(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_SALES')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $query = Sale::with(['customer', 'user', 'saleItems', 'paymentOptions']);

        // Filter by location_id
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by customer_id
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by user_id (who created the sale)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by sale_number
        if ($request->filled('sale_number')) {
            $query->where('sale_number', 'like', '%' . $request->sale_number . '%');
        }

        // Filter by payment_option_id
        if ($request->filled('payment_option_id')) {
            $query->where('payment_option_id', $request->payment_option_id);
        }

        // Filter by is_refund
        if ($request->filled('is_refund')) {
            $query->where('is_refund', $request->is_refund);
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }

        // Filter by exact sale_date
        if ($request->filled('sale_date')) {
            $query->whereDate('sale_date', $request->sale_date);
        }

        // Filter by date range (from)
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        // Filter by date range (to)
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Filter by total_amount range (minimum)
        if ($request->filled('min_amount')) {
            $query->where('total_amount', '>=', $request->min_amount);
        }

        // Filter by total_amount range (maximum)
        if ($request->filled('max_amount')) {
            $query->where('total_amount', '<=', $request->max_amount);
        }

        // Filter by reference
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        // Sorting — whitelist to prevent query abuse
        $allowedOrderBy = ['sale_date', 'total_amount', 'id', 'created_at', 'reference'];
        $orderBy = in_array($request->input('order_by'), $allowedOrderBy)
            ? $request->input('order_by')
            : 'sale_date';
        $orderDirection = strtolower($request->input('order_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // Pagination — cap to prevent DoS
        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        $sales = $query->paginate($perPage);

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


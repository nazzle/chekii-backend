<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleBackupController extends Controller
{
    /**
     * Display a listing of sales
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'paymentMethod', 'items.product'])
            ->latest('sale_date');

        // Filter by date
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('sale_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $sales = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sales,
            'summary' => [
                'total_sales' => $query->sum('total_amount'),
                'total_transactions' => $query->count(),
            ]
        ]);
    }

    /**
     * Create a new sale
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate totals from items
            $subtotal = 0;
            $items = [];

            foreach ($request->items as $itemData) {
                $product = Item::find($itemData['product_id']);

                // Check stock availability
                if ($product->track_stock && $product->stock_quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
                }

                $itemTotal = $itemData['unit_price'] * $itemData['quantity'];
                $subtotal += $itemTotal;

                $items[] = [
                    'product_id' => $itemData['product_id'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'total_price' => $itemTotal,
                    'unit_cost' => $product->cost_price, // For profit calculation
                ];

                // Update stock
                if ($product->track_stock) {
                    $product->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            // Calculate tax (example: 16%)
            $taxRate = 0.16; // This could come from a settings table
            $taxAmount = $subtotal * $taxRate;

            // Apply discount
            $discountAmount = $request->discount_amount ?? 0;

            // Calculate final total
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Validate payment
            $amountPaid = $request->amount_paid;
            $changeAmount = max(0, $amountPaid - $totalAmount);

            if ($amountPaid < $totalAmount) {
                throw new \Exception("Amount paid is less than total amount due");
            }

            // Generate sale number
            $saleNumber = 'SL-' . date('Ymd') . '-' . str_pad(Sale::whereDate('sale_date', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'store_id' => auth()->user()->store_id ?? 1, // Adjust as needed
                'payment_method_id' => $request->payment_method_id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'sale_type' => 'walk-in',
                'notes' => $request->notes,
                'sale_date' => now(),
            ]);

            // Create sale items
            foreach ($items as $item) {
                $sale->items()->create($item);
            }

            // Create payment record
            $sale->payments()->create([
                'payment_method_id' => $request->payment_method_id,
                'amount' => $amountPaid,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update customer loyalty points if applicable
            if ($request->customer_id) {
                $pointsEarned = $totalAmount; // 1 point per dollar spent
                Customer::find($request->customer_id)->increment('loyalty_points', $pointsEarned);
            }

            DB::commit();

            // Load relationships for response
            $sale->load(['customer', 'user', 'paymentMethod', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'data' => $sale,
                'receipt_data' => $this->generateReceiptData($sale)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Sale failed: ' . $e->getMessage() : 'Sale failed',
            ], 500);
        }
    }

    /**
     * Display a specific sale
     */
    public function show($id)
    {
        $sale = Sale::with(['customer', 'user', 'paymentMethod', 'items.product', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    /**
     * Get sale for receipt/printing
     */
    public function receipt($id)
    {
        $sale = Sale::with(['customer', 'user', 'paymentMethod', 'items.product'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->generateReceiptData($sale)
        ]);
    }

    /**
     * Process a return/refund
     */
    public function processReturn(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'refund_amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $originalSale = Sale::findOrFail($id);

            // Create refund sale
            $refundSale = Sale::create([
                'sale_number' => 'RF-' . $originalSale->sale_number,
                'customer_id' => $originalSale->customer_id,
                'user_id' => auth()->id(),
                'store_id' => $originalSale->store_id,
                'payment_method_id' => $originalSale->payment_method_id,
                'subtotal' => -$request->refund_amount, // Negative amount
                'total_amount' => -$request->refund_amount,
                'amount_paid' => -$request->refund_amount,
                'status' => 'completed',
                'sale_type' => 'refund',
                'notes' => $request->reason,
                'original_sale_id' => $originalSale->id,
                'is_refund' => true,
                'sale_date' => now(),
            ]);

            // Process returned items
            foreach ($request->items as $returnItem) {
                $saleItem = SaleItem::find($returnItem['sale_item_id']);

                // Restore stock
                $product = $saleItem->product;
                if ($product->track_stock) {
                    $product->increment('stock_quantity', $returnItem['quantity']);
                }

                // Create refund item
                $refundSale->items()->create([
                    'product_id' => $saleItem->product_id,
                    'unit_price' => -$saleItem->unit_price, // Negative price
                    'quantity' => $returnItem['quantity'],
                    'total_price' => -($saleItem->unit_price * $returnItem['quantity']),
                    'unit_cost' => $saleItem->unit_cost,
                ]);
            }

            // Create refund payment
            $refundSale->payments()->create([
                'payment_method_id' => $originalSale->payment_method_id,
                'amount' => -$request->refund_amount, // Negative amount
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update original sale status if all items are returned
            $this->updateOriginalSaleStatus($originalSale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully',
                'data' => $refundSale->load(['items.product', 'payments'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Return failed: ' . $e->getMessage() : 'Return failed',
            ], 500);
        }
    }

    /**
     * Cancel a sale
     */
    public function cancel($id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::with('items.product')->findOrFail($id);

            if ($sale->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale is already cancelled'
                ], 400);
            }

            // Restore stock for all items
            foreach ($sale->items as $item) {
                $product = $item->product;
                if ($product->track_stock) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            // Update sale status
            $sale->update([
                'status' => 'cancelled',
                'notes' => $sale->notes . "\nCancelled on: " . now()->toDateTimeString()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Cancellation failed: ' . $e->getMessage() : 'Cancellation failed',
            ], 500);
        }
    }

    /**
     * Get today's sales summary
     */
    public function todaysSummary()
    {
        $today = now()->format('Y-m-d');

        $summary = Sale::whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->selectRaw('
                          COUNT(*) as total_transactions,
                          SUM(total_amount) as total_sales,
                          SUM(tax_amount) as total_tax,
                          SUM(discount_amount) as total_discount,
                          AVG(total_amount) as average_sale
                      ')
            ->first();

        $paymentMethods = Payment::whereHas('sale', function ($query) use ($today) {
            $query->whereDate('sale_date', $today)->where('status', 'completed');
        })
            ->selectRaw('payment_method_id, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->with('paymentMethod')
            ->groupBy('payment_method_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'payment_methods' => $paymentMethods,
                'date' => $today
            ]
        ]);
    }

    /**
     * Generate receipt data
     */
    private function generateReceiptData(Sale $sale)
    {
        return [
            'sale_number' => $sale->sale_number,
            'date' => $sale->sale_date->format('Y-m-d H:i:s'),
            'cashier' => $sale->user->name,
            'customer' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
            'items' => $sale->items->map(function ($item) {
                return [
                    'product' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price
                ];
            }),
            'subtotal' => $sale->subtotal,
            'tax_amount' => $sale->tax_amount,
            'discount_amount' => $sale->discount_amount,
            'total_amount' => $sale->total_amount,
            'amount_paid' => $sale->amount_paid,
            'change_amount' => $sale->change_amount,
            'payment_method' => $sale->paymentMethod->name,
        ];
    }

    /**
     * Update original sale status after return
     */
    private function updateOriginalSaleStatus(Sale $sale)
    {
        // Logic to determine if original sale should be marked as partially refunded
        $totalRefunded = Sale::where('original_sale_id', $sale->id)
            ->where('is_refund', true)
            ->sum('total_amount');

        if (abs($totalRefunded) >= $sale->total_amount) {
            $sale->update(['status' => 'refunded']);
        }
    }
}

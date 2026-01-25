<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'sale_number',
        'customer_id',
        'location_id',
        'payment_option_id',
        'subtotal',
        'amount_paid',
        'change_amount',
        'sale_type',
        'notes',
        'original_sale_id',
        'is_refund',
        'user_id',
        'sale_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'net_amount',
        'status',
        'reference',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sale_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Get the customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who made the sale.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sale items.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the discounts.
     */
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get the payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Creates sale number - unique number.
     */
    public static function generateSaleNumber(): string
    {
        $date = Carbon::now()->format('Ymd');

        // Count today's sales with lock to prevent race conditions
        $countToday = DB::table('sales')
            ->whereDate('created_at', Carbon::today())
            ->lockForUpdate()
            ->count() + 1;

        $saleNumber = 'SALE-' . $date . '-' . str_pad($countToday, 6, '0', STR_PAD_LEFT);

        // Ensure uniqueness (in case of race condition)
        while (self::where('sale_number', $saleNumber)->exists()) {
            $countToday++;
            $saleNumber = 'SALE-' . $date . '-' . str_pad($countToday, 6, '0', STR_PAD_LEFT);
        }

        return $saleNumber;
    }
}

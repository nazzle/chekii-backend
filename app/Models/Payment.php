<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'sale_id',
        'payment_option_id',
        'amount',
        'payment_date',
        'reference',
    ];

    protected $casts = [
        'active' => 'boolean',
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the payment option.
     */
    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }
}

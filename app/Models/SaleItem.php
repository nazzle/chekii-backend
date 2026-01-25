<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'sale_id',
        'item_id',
        'quantity',
        'unit_price',
        'total_price',
        'tax_amount',
        'discount_amount',
    ];

    protected $casts = [
        'active' => 'boolean',
        'id' => 'integer',
        'sale_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Get the sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

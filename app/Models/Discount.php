<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'definition_id',
        'item_id',
        'category_id',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    /**
     * Get the discount definition.
     */
    public function discountDefinition()
    {
        return $this->belongsTo(DiscountDefinition::class);
    }

    /**
     * Get the item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}

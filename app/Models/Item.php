<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active',
        'barcode',
        'item_code',
        'description',
        'buying_price',
        'selling_price',
        'gender',
        'age',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Get the inventory for this item.
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active',
        'item_id',
        'quantity',
        'reorder_level',
        'location_id',
        'supplier_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'quantity' => 'integer',
        'reorder_level' => 'integer',
    ];

    /**
     * Get the item that owns this inventory.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the location that owns this inventory.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the supplier that owns this inventory.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

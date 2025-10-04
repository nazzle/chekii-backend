<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
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
        'from_location',
        'to_location',
        'movement_type',
        'quantity',
        'reference',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'quantity' => 'integer',
    ];

    /**
     * Get the item that owns this movement.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the from location that owns this movement.
     */
    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location');
    }

    /**
     * Get the to location that owns this movement.
     */
    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location');
    }
}

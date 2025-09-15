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
        'location_id',
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
     * Get the location that owns this movement.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
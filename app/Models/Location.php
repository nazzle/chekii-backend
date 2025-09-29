<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active',
        'name',
        'code',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the inventories for this location.
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the movements for this location.
     */
    public function movements()
    {
        return $this->hasMany(Movement::class, 'from_location')
            ->orWhere('to_location');
    }

    // Movements starting from this location
    public function movementsFrom()
    {
        return $this->hasMany(Movement::class, 'from_location_id');
    }

    // Movements ending at this location
    public function movementsTo()
    {
        return $this->hasMany(Movement::class, 'to_location_id');
    }
}

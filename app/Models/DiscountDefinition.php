<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'type',
        'value',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'value' => 'decimal:2',
    ];

    /**
     * Get the discounts for this definition.
     */
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }
}

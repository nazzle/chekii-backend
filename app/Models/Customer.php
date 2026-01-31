<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'email',
        'phone',
        'address',
        'type',
        'loyalty_card_number',
        'loyalty_points'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the sales for this customer.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}

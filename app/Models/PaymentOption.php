<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'code',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

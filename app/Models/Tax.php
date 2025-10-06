<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'rate',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'rate' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'company_name',
        'company_logo',
        'address',
        'email',
        'return_policy',
        'website',
        'currency_symbol',
        'currency_code',
        'phone'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

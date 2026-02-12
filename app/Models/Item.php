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
        'item_image',
        'buying_price',
        'selling_price',
        'gender',
        'category_id',
        'type_id',
        'age_id',
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

    /**
     * Get the category that owns this item.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the item type that owns this item.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    /**
     * Get the age group that owns this item.
     */
    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }

    /**
     * Get the movements for this item.
     */
    public function movements()
    {
        return $this->hasMany(Movement::class);
    }
}

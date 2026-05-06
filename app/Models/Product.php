<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'stocks',
        'sold',
        'is_in_stock',
        'category',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_in_stock' => 'boolean',
    ];

    // Define product categories
    public static function getCategories()
    {
        return [
            'meat' => __('Meat & Poultry'),
            'vegetables' => __('Vegetables'),
            'fruits' => __('Fruits'),
            'dairy' => __('Dairy'),
            'eggs' => __('Eggs'),
            'seafood' => __('Seafood'),
            'beverages' => __('Beverages'),
            'snacks' => __('Snacks'),
            'condiments' => __('Condiments & Spices'),
            'grains' => __('Grains & Cereals'),
            'frozen' => __('Frozen Goods'),
            'bakery' => __('Bakery Goods'),
            'gas' => __('Gas'),
            'other' => __('Other'),
        ];
    }

    public function getCategoryNameAttribute()
    {
        $categories = self::getCategories();
        return $categories[$this->category] ?? $this->category ?? 'Uncategorized';
    }

    public function getStockStatusAttribute()
    {
        if ($this->stocks <= 0) {
            return 'out_of_stock';
        } elseif ($this->stocks < 10) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getStockStatusColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
        };
    }

    // Relationship with order items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

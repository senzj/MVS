<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image_url',
        'color',
        'stocks',
        'sold',
        'is_in_stock',
        'category',
        'price',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'category_id' => 'integer',
        'is_in_stock' => 'boolean',
    ];

    public static function getCategories(): array
    {
        static $categories = null;

        if ($categories === null) {
            $categories = ProductCategories::query()
                ->orderBy('name', 'asc')
                ->pluck('name', 'id')
                ->toArray();
        }

        return $categories;
    }

    public function getCategoryNameAttribute()
    {
        $categories = self::getCategories();

        if (! empty($this->category_id) && isset($categories[$this->category_id])) {
            return $categories[$this->category_id];
        }

        return 'Uncategorized';
    }

    public function getCategoryAttribute()
    {
        return $this->category_id;
    }

    public function setCategoryAttribute($value): void
    {
        $this->attributes['category_id'] = $value !== '' ? $value : null;
    }

    public function getStockStatusAttribute()
    {
        if ($this->stocks == 0) {
            return 'out_of_stock';

        } elseif ($this->stocks <= config('storeconfig.stock_low_threshold')) {
            return 'low_stock';

        } elseif ($this->stocks > config('storeconfig.stock_low_threshold')) {
            return 'in_stock';

        } else {
            return 'unknown';
        }
    }

    public function getStockStatusColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            'unknown' => 'gray',
        };
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(ProductCategories::class, 'category_id');
    }

    // Relationship with order items
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Relationship with restock items
    public function restocks(): HasMany
    {
        return $this->hasMany(ItemRestocks::class);
    }
}

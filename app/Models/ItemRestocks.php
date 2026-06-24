<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRestocks extends Model
{
    protected $fillable = [
        'restock_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'unit_type',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function restock(): BelongsTo
    {
        return $this->belongsTo(ProductRestock::class, 'restock_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountPreset extends Model
{
    use HasFactory;
    protected $table = 'discount_preset';

    protected $fillable = [
        'name',
        'type',
        'value',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'discount_preset_id');
    }
}

<?php

namespace App\Models;

use App\Helpers\PaymentImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paymentqr extends Model
{
    protected $table = 'payment_qr';

    protected $fillable = ['name', 'image', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? PaymentImageHelper::getPaymentImageUrl($this->image) : null;
    }
}

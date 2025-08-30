<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Storage;

class PaymentImageHelper
{
    /**
     * Get the current payment image path
     */
    public static function getPaymentImage(): ?string
    {
        $files = Storage::disk('public')->files('image/payment');
        return collect($files)->first();
    }

    /**
     * Get the full URL for the payment image
     */
    public static function getPaymentImageUrl(): ?string
    {
        $imagePath = self::getPaymentImage();
        return $imagePath ? asset('storage/' . $imagePath) : null;
    }

    /**
     * Check if payment image exists
     */
    public static function hasPaymentImage(): bool
    {
        return !is_null(self::getPaymentImage());
    }
}
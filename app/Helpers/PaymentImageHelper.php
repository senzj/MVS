<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Storage;

class PaymentImageHelper
{
    /**
     * Get the current payment image path
     */
    public static function getPaymentImage(?string $path = null): ?string
    {
        if ($path) {
            return Storage::disk('public')->exists($path) ? $path : null;
        }

        $files = Storage::disk('public')->files('image/payment');
        return collect($files)->first();
    }

    /**
     * Get the full URL for the payment image
     */
    public static function getPaymentImageUrl(?string $path = null): ?string
    {
        $imagePath = self::getPaymentImage($path);

        return $imagePath ? route('payment.qr', ['path' => $imagePath]) : null;
    }

    /**
     * Check if payment image exists
     */
    public static function hasPaymentImage(): bool
    {
        return !is_null(self::getPaymentImage());
    }
}

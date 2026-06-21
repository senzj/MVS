<?php

namespace App\Traits;

use Illuminate\Validation\Rules\Password;

trait HasPasswordRule
{
    protected function passwordRule(): Password
    {
        $cfg = config('storeconfig');

        $min          = (int)  ($cfg['password_min_length']       ?? 8);
        $needUpper    = (bool) ($cfg['password_require_uppercase'] ?? true);
        $needLower    = (bool) ($cfg['password_require_lowercase'] ?? true);
        $needNumber   = (bool) ($cfg['password_require_number']    ?? true);
        $needSpecial  = (bool) ($cfg['password_require_special']   ?? true);

        $rule = Password::min($min);

        // mixedCase() enforces BOTH upper + lower.
        // If only one case is required, letters() (any case) is the best built-in fallback.
        if ($needUpper && $needLower) {
            $rule = $rule->mixedCase();
        } elseif ($needUpper || $needLower) {
            $rule = $rule->letters();
        }

        if ($needNumber)  $rule = $rule->numbers();
        if ($needSpecial) $rule = $rule->symbols();

        return $rule;
    }
}

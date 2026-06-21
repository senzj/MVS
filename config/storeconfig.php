<?php

return [
    'default_order_type' => env('STORE_DEFAULT_ORDER_TYPE'),
    'default_payment_type' => env('STORE_DEFAULT_PAYMENT_TYPE'),
    'order_edit_lock_status' => explode(',', env('ORDER_EDIT_LOCK_STATUS')),
    'store_open_hour' => env('STORE_OPEN_HOUR', 7),
    'store_close_hour' => env('STORE_CLOSE_HOUR', 20),
    'store_open_days' => explode(',', env('STORE_OPEN_DAYS', '1,2,3,4,5,6')),
    'other_payment_types' => explode(',', env('OTHER_PAYMENT_TYPES', 'gcash')),
    'session_expire_days' => env('SESSION_EXPIRE_DAYS', 30),
    'stock_low_threshold' => env('STOCK_LOW_THRESHOLD', 10),
    'currency_symbol' => env('CURRENCY_SYMBOL', '₱'),
    'currency_code' => env('CURRENCY_CODE', 'PHP'),
    'password_min_length' => env('PASSWORD_MIN_LENGTH', 8),
    'password_require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
    'password_require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
    'password_require_number' => env('PASSWORD_REQUIRE_NUMBER', true),
    'password_require_special' => env('PASSWORD_REQUIRE_SPECIAL', true),
];

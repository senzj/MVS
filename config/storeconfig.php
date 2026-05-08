<?php

return [
    'default_order_type' => env('STORE_DEFAULT_ORDER_TYPE'),
    'default_payment_type' => env('STORE_DEFAULT_PAYMENT_TYPE'),
    'order_edit_lock_status' => explode(',', env('ORDER_EDIT_LOCK_STATUS')),
];

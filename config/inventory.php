<?php

return [

    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),

    'order_statuses' => [
        'pending',
        'confirmed',
        'processing',
        'dispatched',
        'cancelled',
        'refunded',
    ],

    'cancellable_statuses' => [
        'pending',
        'confirmed',
    ],

];
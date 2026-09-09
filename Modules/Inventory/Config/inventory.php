<?php

return [
    'name' => 'Inventory',

    /*
    |--------------------------------------------------------------------------
    | Default Low Stock Threshold
    |--------------------------------------------------------------------------
    */
    'default_reorder_level' => 5,

    /*
    |--------------------------------------------------------------------------
    | Allow Backorders (Selling when stock is 0)
    |--------------------------------------------------------------------------
    */
    'allow_backorders' => false,

    /*
    |--------------------------------------------------------------------------
    | Stock Reservation Expiry (Minutes)
    |--------------------------------------------------------------------------
    | How long an uncompleted checkout session can hold reserved stock.
    */
    'reservation_expiry_minutes' => 60,
];

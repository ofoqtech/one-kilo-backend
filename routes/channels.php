<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders-dashboard', function ($user) {
    return true;
}, ['guards' => ['admin']]);

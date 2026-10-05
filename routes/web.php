<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'points-platform',
        'status' => 'ok',
    ]);
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

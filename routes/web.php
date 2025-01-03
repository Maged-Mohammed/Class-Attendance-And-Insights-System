<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::post ('/register','authController@store');

require __DIR__.'/auth.php';

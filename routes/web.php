<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Serve the main dashboard HTML
Route::get('/', function () {
    return view('dashboard');
});

// Fallback for SPA routing
Route::fallback(function () {
    return view('dashboard');
});

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Login page
Route::get('/login', function () {
    return view('pages.login');
})->name('login');

// Protected pages (require token in localStorage)
Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/cases', function () {
    return view('pages.kanban');
});

Route::get('/cases/{id}', function () {
    return view('pages.case-detail');
});

Route::get('/my-tasks', function () {
    return view('pages.my-tasks');
});

Route::get('/admin', function () {
    return view('pages.admin');
});

<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
use Inertia\Inertia;

Route::get('/test-inertia', function () {
    return Inertia::render('Test');
});

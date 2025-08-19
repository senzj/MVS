<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->middleware('guest')
    ->name('home');

// dashboard
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Auth routes
Route::middleware(['auth'])->group(function () {
    // Settings route
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Orders route
    Volt::route('orders', 'orders')->name('orders');

    // Products route
    Volt::route('products', 'products')->name('products');

    // Customers route
    Volt::route('customers', 'customers')->name('customers');

    // Employee route
    Volt::route('employees', 'employees')->name('employees');
});

require __DIR__.'/auth.php';


/**
 * Routes Notes:
 * example route: Volt::route('orders', 'orders')->name('orders');
 * 
 * Volt::route -> equivalent to Route::view, Route::get
 * 
 * ('first parameter', 'second parameter')->name('third parameter');
 * first parameter is the URL path where the route will be accessible (e.g. .../orders)
 * second parameter is the view file name or controller action that will handle the request (e.g. 'classname.controller', 'orders.index', 'orders.delete')
 * third parameter is an optional name for the route (e.g. 'route.name')
 * 
 * Volt::route(
 *   'orders',   // URL path (goes to /orders)
 *   'orders'    // Volt component/view file name (resources/views/livewire/orders.blade.php)
 * )->name(
 *   'orders'    // Route name (used in route('orders'))
 * );
 */
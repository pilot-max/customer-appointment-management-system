<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('services')->name('services.')->group(function () {
        Route::livewire('/', 'pages::services.index')->name('index');
        Route::livewire('/create', 'pages::services.create')->name('create');
        Route::livewire('/{service}/edit', 'pages::services.edit')->name('edit');
        Route::livewire('/{service}', 'pages::services.show')->name('show');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::livewire('/', 'pages::customers.index')->name('index');
        Route::livewire('/create', 'pages::customers.create')->name('create');
        Route::livewire('/{customer}/edit', 'pages::customers.edit')->name('edit');
        Route::livewire('/{customer}', 'pages::customers.show')->name('show');
    });

    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::livewire('/', 'pages::appointments.index')->name('index');
        Route::livewire('/create', 'pages::appointments.create')->name('create');
        Route::livewire('/{appointment}/edit', 'pages::appointments.edit')->name('edit');
        Route::livewire('/{appointment}', 'pages::appointments.show')->name('show');
    });
});

require __DIR__.'/settings.php';

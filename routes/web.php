<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/services', 'pages::services.index')
        ->name('services.index');
    Route::livewire('/services/create', 'pages::services.create')
        ->name('services.create');
    Route::livewire('/services/{service}/edit', 'pages::services.edit')
        ->name('services.edit');
    Route::livewire('/services/{service}', 'pages::services.show')
        ->name('services.show');
});

require __DIR__.'/settings.php';

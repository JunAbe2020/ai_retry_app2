<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('setting/', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // ダッシュボード画面
    Volt::route('/', 'retries.dashboard')->name('retries.dashboard');

    // ミス一覧画面
    Volt::route('retries/index', 'retries.index')->name('retries.index');

    // ミス作成画面
    Volt::route('retries/create', 'retries.create')->name('retries.create');

    // ミス詳細画面
    Volt::route('retries/{retry}', 'retries.show')->name('retries.show');

    // ミス更新画面
    Volt::route('retries/{retry}/edit', 'retries.edit')->name('retries.edit');
});

require __DIR__ . '/auth.php';

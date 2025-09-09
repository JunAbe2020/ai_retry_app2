<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // ミス一覧画面
    Volt::route('retries/index', 'retries.index')->name('retries.index');

    // ミス作成画面
    Volt::route('retries/create', 'retries.create')->name('retries.create');

    // ミス詳細画面
    Volt::route('retries/{mistake}', 'retries.show')->name('retries.show');

    // ミス更新画面
    Volt::route('retries/{mistake}/edit', 'retries.edit')->name('retries.edit');
});

require __DIR__ . '/auth.php';

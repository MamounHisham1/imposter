<?php

use App\Livewire\CreateRoom;
use App\Livewire\GameRoom;
use App\Livewire\JoinRoom;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;

// Game Routes
Route::get('/', CreateRoom::class)->name('home');
Route::get('/create-room', CreateRoom::class)->name('create-room');
Route::get('/join-room', JoinRoom::class)->name('join-room');
Route::get('/join-room/{code}', JoinRoom::class)->name('join-room-with-code');
Route::get('/room/{room:code}', GameRoom::class)->name('game-room');

// PWA Install Route
Route::view('/install', 'install')->name('install');

// Settings Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings/profile', Profile::class)->name('profile.edit');
    Route::get('/settings/password', Password::class)->name('user-password.edit');
    Route::get('/settings/appearance', Appearance::class)->name('appearance.edit');
    Route::get('/settings/two-factor', TwoFactor::class)->name('two-factor.show');
});

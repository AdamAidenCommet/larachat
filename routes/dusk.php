<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/dusk/login/{userId?}', function ($userId = null) {
    $user = $userId ? User::find($userId) : User::factory()->create();
    auth()->login($user);

    return redirect('/dashboard');
})->name('dusk.login');

Route::get('/dusk/logout', function () {
    auth()->logout();

    return redirect('/');
})->name('dusk.logout');

Route::get('/dusk/user', function () {
    return auth()->user();
})->name('dusk.user');

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('company', function () {
    return Inertia::render('Company');
})->middleware(['auth', 'verified'])->name('company');

Route::get('employee', function () {
    return Inertia::render('Employee');
})->middleware(['auth', 'verified'])->name('employee');

require __DIR__.'/settings.php';

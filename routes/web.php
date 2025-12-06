<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('company', function () {
        return Inertia::render('Company');
    })->name('company');

    Route::get('employee', function () {
        return Inertia::render('Employee');
    })->name('employee');

    Route::prefix('api')->namespace('App\Http\Controllers')->group(function(){
        Route::apiResources([
            'companies' => 'CompaniesController',
            'employees' => 'EmployeesController',
        ]);

        Route::get('list', 'EmployeesController@getList');
    });
});


require __DIR__.'/settings.php';

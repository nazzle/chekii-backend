<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/operation-success', function () {
    return view('operation-success');
});

Route::middleware('web.lock')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'));
    Route::get('/admin/settings', fn () => view('settings'));
    Route::get('/**', fn () => view('welcome'));
    // Any backend pages here
});

Route::get('/lock', fn () => view('welcome'));

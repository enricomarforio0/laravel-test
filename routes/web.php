<?php

use App\Http\Controllers\UserController;
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


Route::get('/login', function () {
    return view('user.login');
});

Route::get('/register', function () {
    return view('user.create');
});

Route::get('/', function () {
    return view('user.login');
});

Route::get('/api/register', 'App\Http\Controllers\UserController@store');

Route::get('/api/login', 'App\Http\Controllers\UserController@find');

Route::resource('user', UserController::class);

<?php

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FlightController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/flights/search', [FlightController::class, 'searchFlights']);

Route::prefix('airlines')
    ->name('airlines.') // identifier to prefix in child `->name()` identifiers
    ->middleware('auth:sanctum')
    ->group(function(){
        Route::get('', [AirlineController::class, 'index'])
            ->name('index');
        Route::get('/{id}', [AirlineController::class, 'show'])
            ->name('show')
            ->whereNumber('id');
        Route::post('', [AirlineController::class, 'store'])
            ->name('store');
        Route::put('/{id}', [AirlineController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [AirlineController::class, 'destroy'])
            ->name('destroy')
            ->whereNumber('id');
    });




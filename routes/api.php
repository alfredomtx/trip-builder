<?php

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
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
Route::post('/register', [AuthController::class, 'register']);

/**
 * Airline
 * 
 * all endpoints requires authentication
 */
 Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/airlines', [AirlineController::class, 'index']);
    Route::post('/airlines', [AirlineController::class, 'store']);
    Route::get('/airlines/{id}', [AirlineController::class, 'show']);
    Route::delete('/airlines/{id}', [AirlineController::class, 'destroy']);
    Route::put('/airlines/{id}', [AirlineController::class, 'update']);
});

Route::post('/trips/search', [TripController::class, 'searchFlights']);





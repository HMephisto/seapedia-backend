<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/reviews', [ReviewController::class, 'index']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/stores/{id}',   [StoreController::class, 'show']); 

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/add-role', [AuthController::class, 'addRole']);
    Route::post('/switch-role', [AuthController::class, 'switchRole']);

    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::middleware('role:SELLER')->group(function () {
        Route::post('/seller/store', [StoreController::class, 'create']);
        Route::put('/seller/store', [StoreController::class, 'update']);
        Route::get('/seller/store',  [StoreController::class, 'show']); 
        
        Route::post('/seller/products', [ProductController::class, 'store']);
        Route::put('/seller/products/{id}', [ProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [ProductController::class, 'destroy']);
    });
}); 



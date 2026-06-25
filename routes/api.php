<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WalletController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/reviews', [ReviewController::class, 'index']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/stores/{id}', [StoreController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/add-role', [AuthController::class, 'addRole']);
    Route::post('/switch-role', [AuthController::class, 'switchRole']);

    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::middleware('role:SELLER')->group(function () {
        Route::post('/seller/store', [StoreController::class, 'create']);
        Route::put('/seller/store', [StoreController::class, 'update']);
        Route::get('/seller/store', [StoreController::class, 'myStore']);
        Route::get('/seller/store/check', [StoreController::class, 'hasStore']);

        Route::post('/seller/products', [ProductController::class, 'store']);
        Route::post('/seller/products/{id}/image', [ProductController::class, 'uploadImage']);
        Route::put('/seller/products/{id}', [ProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [ProductController::class, 'destroy']);
    });

    Route::middleware('role:BUYER')->group(function () {
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::post('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);

        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'show']);
            Route::post('/topup', [WalletController::class, 'topup']);
            Route::get('/transactions', [WalletController::class, 'transactions']);
        });

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'summary']);
            Route::post('/items', [CartController::class, 'add']);
            Route::patch('/items/{itemId}', [CartController::class, 'updateQuantity']);
            Route::delete('/items/{itemId}', [CartController::class, 'remove']);
            Route::delete('', [CartController::class, 'clear']);
        });
    });
});



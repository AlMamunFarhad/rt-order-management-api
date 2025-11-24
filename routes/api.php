<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Product\OrderController;
use App\Http\Controllers\Product\StockController;
use App\Http\Controllers\Product\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
// Protected routes
Route::group(['middleware' => 'auth:api'],function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // products
    Route::get('/products', [ProductController::class,'index']);
    Route::post('/products/store', [ProductController::class,'store']);
    Route::get('/products/search', [ProductController::class,'search']);
    Route::get('/products/{product}', [ProductController::class,'show']);
    Route::put('/products/{product}', [ProductController::class,'update']);
    Route::delete('/products/{product}', [ProductController::class,'destroy']);

    // stocks
    Route::get('/stocks', [StockController::class,'index']);
    Route::post('/stocks', [StockController::class,'store']);
    Route::get('/stocks/{stock}', [StockController::class,'show']);
    Route::put('/stocks/{stock}', [StockController::class,'update']);

    // orders
    Route::get('/orders', [OrderController::class,'index']);
    Route::post('/orders', [OrderController::class,'store']);
    Route::get('/orders/{order}', [OrderController::class,'show']);
    Route::delete('/orders/{order}', [OrderController::class,'destroy']);
});
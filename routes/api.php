<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\App;
use App\Http\Middleware\Role;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Category actions
Route::prefix('category')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
});

// Product actions
Route::prefix('product')->group(function () {
    Route::get('/search', [ProductController::class, 'search']); 
    Route::get('/category/{categoryId}', [ProductController::class, 'getByCategory']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/', [ProductController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User actions
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Vendor-only actions
    Route::middleware('role:vendor')->group(function () { 
        // Product
        Route::prefix('product')->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
        });
        
        // Category 
        Route::prefix('category')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        // Order
        Route::get('/order/search', [OrderController::class, 'search']);

        // Admin 
        Route::prefix('admin')->group(function () {
            Route::get('/orders', [AdminController::class, 'orders']);
            Route::get('/users', [AdminController::class, 'users']);
        });
    });
    
    // Customer-only actions
    Route::middleware('role:customer')->group(function () {
        Route::post('/order', [OrderController::class, 'store']);

    });

    // Order actions
    Route::prefix('order')->group(function () {
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });
    
    // Order-detail actions
    Route::prefix('order-details')->group(function () {
        Route::get('/{id}', [OrderDetailController::class, 'show']);
        Route::get('/', [OrderDetailController::class, 'index']);
        Route::post('/', [OrderDetailController::class, 'store']);
        Route::put('/{id}', [OrderDetailController::class, 'update']);
        Route::delete('/{id}', [OrderDetailController::class, 'destroy']);
    });

    // Cart actions
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'addItem']);
        Route::post('/confirmPayment', [PaymentController::class, 'confirm']);
        Route::put('/item/{id}', [CartController::class, 'updateItem']);
        Route::delete('/item/{id}', [CartController::class, 'removeItem']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });

});

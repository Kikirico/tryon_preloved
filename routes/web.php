<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FashnController;
Route::get('/ai-fitting', [FashnController::class, 'index'])->name('fashn');
Route::get('/ai-fitting/health', [FashnController::class, 'health'])->middleware('throttle:30,1');
Route::post('/ai-fitting', [FashnController::class, 'store'])->middleware('throttle:5,1');
Route::get('/ai-fitting/runs/{run}', [FashnController::class, 'status']);
Route::get('/ai-fitting/runs/{run}/images/{kind}', [FashnController::class, 'image'])->name('fashn.image');
Route::delete('/ai-fitting/runs/{run}', [FashnController::class, 'destroy']);
Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/products/{product}', [ShopController::class, 'show'])->name('product.show');
Route::get('/try-on/{product}', [ShopController::class, 'legacyProduct']);
Route::get('/checkout/{product}', [ShopController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{product}', [ShopController::class, 'order'])->middleware('throttle:10,1');
Route::get('/orders/{order:token}', [ShopController::class, 'receipt'])->name('receipt');
Route::post('/orders/{order:token}/proof', [ShopController::class, 'proof'])->middleware('throttle:5,1');
Route::get('/admin/login', [AdminController::class, 'login'])->name('login');
Route::post('/admin/login', [AdminController::class, 'authenticate'])->middleware('throttle:5,1');
Route::middleware(['auth', App\Http\Middleware\AdminOnly::class])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin');
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::post('/products', [AdminController::class, 'store']);
    Route::delete('/products/{product}', [AdminController::class, 'destroy'])->name('admin.products.destroy');
    Route::post('/products/{product}/fashn', [AdminController::class, 'fashn']);
    Route::post('/orders/{order}/status', [AdminController::class, 'status']);
    Route::get('/orders/{order}/proof', [AdminController::class, 'proof']);
});

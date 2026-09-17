<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\DeliveryForgotController;
use App\Http\Controllers\Api\Auth\DeliveryAuthController;
use App\Http\Controllers\Api\Auth\ForgotController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Payments\KashierController as KashierPaymentsController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;


## ================== SETTINGS ================== ##
Route::get('/settings',     [SettingsController::class, 'index']);
Route::get('/about-us',     [SettingsController::class, 'about']);
Route::get('/privacy',      [SettingsController::class, 'privacy']);
Route::get('/terms',        [SettingsController::class, 'terms']);
Route::get('/faq',          [SettingsController::class, 'faq']);
Route::post('/contact',     [SettingsController::class, 'contact']);
Route::get('/banners',      [SettingsController::class, 'banners']);
Route::get('/workingHours',      [SettingsController::class, 'workingHours']);
Route::get('/app-popup',    [SettingsController::class, 'activePopup']);
## ================== SETTINGS ================== ##

## ================== LOOKUPS (Mobile) ================== ##
Route::get('/countries',                            [LocationController::class, 'countries']);
Route::get('/countries/{country_id}/governorates',  [LocationController::class, 'governorates']);
Route::get('/governorates/{governorate_id}/regions', [LocationController::class, 'regions']);
## ================== LOOKUPS (Mobile) ==================

## ------------------ AUTH ROUTES ------------------ ##
Route::controller(AuthController::class)->group(function () {
    Route::post('/register',     'register')->middleware('guest');
    Route::post('/verify-otp',   'verifyOtp')->middleware('guest');
    Route::post('/resend-otp',   'resendOtp')->middleware('guest');
    Route::post('/login',        'login')->middleware('guest');
    Route::post('/logout',       'logout')->middleware('auth:sanctum');
    Route::post('/firebase-login',       [AuthController::class, 'firebaseLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationsController::class, 'notifications']);
    });
});
## ------------------ AUTH ROUTES ------------------ ##


## ------------------ Forgot Password ------------------ ##
Route::post('/forgot/password',         [ForgotController::class, 'forgotPassword'])->middleware('guest');
Route::post('/forgot/verify-otp',       [ForgotController::class, 'verifyOtp'])->middleware('guest');
Route::post('/forgot/resend-otp',       [ForgotController::class, 'resendOtp'])->middleware('guest');
Route::post('/forgot/reset-password',   [ForgotController::class, 'resetPassword'])->middleware('guest');
## ------------------ Forgot Password ------------------ ##


## ================== COMMERCE ================== ##
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/categories/{slug}/products', [ProductController::class, 'categoryProducts']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/best-selling', [ProductController::class, 'bestSelling']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
## ================== COMMERCE ================== ##

## ================== PAYMENTS (Public) ================== ##
Route::get('/payments/kashier/callback', [KashierPaymentsController::class, 'callback'])
    ->name('api.payments.kashier.callback');
Route::post('/payments/kashier/webhook', [KashierPaymentsController::class, 'webhook'])
    ->name('api.payments.kashier.webhook');
## ================== PAYMENTS (Public) ================== ##


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::post('/addresses/{id}/set-default', [AddressController::class, 'setDefault'])->whereNumber('id');
    Route::get('/addresses/{id}', [AddressController::class, 'show'])->whereNumber('id');
    Route::post('/addresses/{id}', [AddressController::class, 'update'])->whereNumber('id');
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])->whereNumber('id');

    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet', [WalletController::class, 'show']);

    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon']);

    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{reference}', [OrderController::class, 'show']);
    Route::get('/getLocation/{reference}', [\App\Http\Controllers\Api\DeliveryOrderController::class, 'getLocation']);
});


Route::prefix('delivery')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::post('/register',     [DeliveryAuthController::class, 'register']);
        Route::post('/verify-otp',   [DeliveryAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp',   [DeliveryAuthController::class, 'resendOtp']);
        Route::post('/login',        [DeliveryAuthController::class, 'login']);

        Route::post('/forgot/password',       [DeliveryForgotController::class, 'forgotPassword']);
        Route::post('/forgot/verify-otp',     [DeliveryForgotController::class, 'verifyOtp']);
        Route::post('/forgot/resend-otp',     [DeliveryForgotController::class, 'resendOtp']);
        Route::post('/forgot/reset-password', [DeliveryForgotController::class, 'resetPassword']);
    });

    Route::post('/logout', [DeliveryAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/getProfile',       [DeliveryAuthController::class, 'getProfile'])->middleware('auth:sanctum');
    Route::post('/updateProfile',       [DeliveryAuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/firebase-login', [DeliveryAuthController::class, 'firebaseLogin']);


    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/current-orders', [\App\Http\Controllers\Api\DeliveryOrderController::class, 'currentOrders']);
        Route::get('/past-orders', [\App\Http\Controllers\Api\DeliveryOrderController::class, 'pastOrders']);
        Route::get('/orders/{reference}', [\App\Http\Controllers\Api\DeliveryOrderController::class, 'show']);

        Route::post('/orders/updateStatus/{reference}', [\App\Http\Controllers\Api\DeliveryOrderController::class, 'updateStatus']);
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationsController::class, 'notifications']);
        Route::post('/test', [\App\Http\Controllers\Api\NotificationsController::class, 'test']);
        Route::post('/update-location', [DeliveryAuthController::class, 'updateLocation']);
    });
});

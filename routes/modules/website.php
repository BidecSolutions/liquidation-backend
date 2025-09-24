<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    CategoryController,
    PaymentMethodController,
    ShippingMethodController,
    ListingController,
    ContactMessageController,
};


// CategoryController works
Route::prefix('category')->controller(CategoryController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('tree', 'tree');
    Route::get('all','all');
    Route::get('/{slug}', 'show');
});

Route::prefix('options')->group(function () {
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('shipping-methods', [ShippingMethodController::class, 'index']);
});

// ListingController works
    Route::prefix('listings')->controller(ListingController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/filters', 'filterListings');
        Route::get('/filters-metadata', 'filtersMetadata');
        Route::get('/{slug}/show', 'show');
        Route::get('/suggestions', 'suggestions');
        Route::get('/coolAuctions', 'coolAuctions');

    });

// ContactMessageController works
Route::prefix('contact')->controller(ContactMessageController::class)->group(function () {
    Route::post('message', 'store');
});
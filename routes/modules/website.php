<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    CategoryController,
    PaymentMethodController,
    ShippingMethodController,
    ListingController,
    ContactMessageController,
    GuestController,
    InstructionController,
    PromotionController,
    UserController,
};

// routes/api.php
Route::get('guest-id', [GuestController::class, 'generate']);

// CategoryController works
Route::prefix('category')->controller(CategoryController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('tree', 'tree');
    Route::get('all', 'all');
    Route::get('/{slug}', 'show');
});

Route::prefix('options')->group(function () {
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('shipping-methods', [ShippingMethodController::class, 'index']);
});
Route::get('user/summary/{userId}', [UserController::class, 'userSummary']);
Route::get('promotions', [PromotionController::class, 'list']);
Route::get('instructions', [InstructionController::class, 'list']);
// ListingController works
Route::prefix('listings')->controller(ListingController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/filters', 'filterListings');
    Route::get('/filters-metadata', 'filtersMetadata');
    Route::get('/{slug}/show', 'show');
    Route::get('/suggestions', 'suggestions');
    Route::get('/coolAuctions', 'coolAuctions');
    Route::get('/hotListings', 'hotListings');
    Route::get('/closingSoon', 'closingSoon');
    Route::get('/isfeatured', 'isfeatured');
    Route::get('/search', 'search');
    Route::get('/homePastSearches', 'homePastSearches');
    Route::get('/recommendations', 'recommendations');
    Route::get('/searchById/{id}', 'searchById');
    Route::get('/mainapi', 'mainapi');
});

// ContactMessageController works
Route::prefix('contact')->controller(ContactMessageController::class)->group(function () {
    Route::post('message', 'store');
});

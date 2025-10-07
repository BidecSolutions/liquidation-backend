<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\GovernorateController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\InstructionController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ShippingMethodController;
use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

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
    Route::delete('/deletePastSearches/{searchId}', 'removePastSearch');
    Route::get('/recommendations', 'recommendations');
    Route::get('/searchById/{id}', 'searchById');
    Route::get('/mainapi', 'mainapi');
});
Route::post('countries/list', [CountryController::class, 'list']);

// ContactMessageController works
Route::prefix('contact')->controller(ContactMessageController::class)->group(function () {
    Route::post('message', 'store');
});
Route::prefix('vehicle')->controller(VehicleController::class)->group(function () {
    Route::post('/', 'list');
});
// Public routes for locations
Route::get('regions', [RegionController::class, 'index']);
Route::get('governorates', [GovernorateController::class, 'index']);
Route::get('regions/{region}', [RegionController::class, 'show']);
Route::get('governorates/{governorate}', [GovernorateController::class, 'show']);

//forgot password routes 
Route::post('/forgot-password', [UserAuthController::class,'sendResetLinkEmail']);
Route::post('/reset-password', [UserAuthController::class,'resetPassword']);

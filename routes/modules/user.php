<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AppointmentController,
    UserAuthController,
    ListingController,
    ListingOfferController,
    BidController,
    ListingReportController,
    WatchlistController,
    UserController,
    NotificationController,
    DeliveryAddressController,
    AuctionResultController,
    CommentController,
    FavoriteController,
    ListingBuyController,
    UserFeedbackController,
    ListingReviewController,
};


// User Auth
// Route::post('/user/login', [AuthController::class, 'login']);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user/profile', [AuthController::class, 'user']);
//     Route::post('/user/logout', [AuthController::class, 'logout']);
// });
Route::prefix('user')->group(function () {
    //User Auth Controller works
    Route::controller(UserAuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/username-check', 'checkUsername');
        Route::post('/email-verification', 'emailVerification');
        Route::post('/resend-otp', 'resendOtp');
        Route::post('/request-restore-token', 'requestRestoreToken');
        Route::post('/verify-and-restore', 'verifyAndRestore');
    });

    Route::middleware('auth:api')->group(function () {

        //User Auth Controller works
        Route::controller(UserAuthController::class)->group(function () {
            Route::get('/profile', 'profile');
            Route::post('/logout', 'logout');
            Route::post('/{id}/edit-contact-details', 'updateProfile');
            Route::post('/upgrade-to-business', 'upgradeToBusiness');
            Route::post('/change-password', 'changePassword');
            Route::post('/upload-profile', 'uploadProfilePhoto');
            Route::post('/upload-background', 'uploadBackgroundPhoto');
            Route::post('/change-email', 'updateEmail');
            Route::post('/update-name', 'updateName');
            Route::post('/profile-update', 'updateProfileDetails');
            Route::delete('/delete', 'deleteAccount');
            // Route::post('/forgot-password', 'sendResetLinkEmail');
            // Route::post('/reset-password', 'resetPassword');
            


        });

        Route::prefix('listings/offers')->controller(ListingOfferController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/{listingID}/listing-wise', 'listingWiseOffer');
            Route::post('/{listingID}/store', 'store');
            Route::put('{id}/update', 'update');
            Route::delete('{id}/destroy', 'destroy');
            Route::post('{id}/approve', 'approveOffer');
            Route::post('{id}/reject', 'rejectOffer');
        });

        Route::prefix('listings')->controller(BidController::class)->group(function () {
            Route::get('{listingID}/bids', 'index');
            Route::post('/bids/{listingID}/store', 'store');
            Route::post('/bids/{listingID}/accept-bid', 'acceptBid');
            Route::post('/bids/{listingID}/reject-bid', 'rejectBid');
        });

        // ListingController works
        Route::prefix('listings')->controller(ListingController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/views', 'views');
            Route::post('/store', 'store');
            Route::get('/{slug}/show', 'show');
            Route::post('/{slug}/update', 'update');
            Route::patch('/{slug}/toggle', 'toggleStatus');
            Route::post('{slug}/withdraw', 'withdraw'); // Withdraw listing
            Route::post('{slug}/relist', 'relist'); // Relist listing
            // Route::delete('/{slug}/destroy', 'destroy');
            Route::post('note/{id}', 'updateNote');
            Route::delete('note/{id}', 'deleteNote');
            Route::delete('/images/{id}', 'deleteImage');
            Route::get('/suggestions', 'suggestions');
            Route::get('/search', 'search');
            Route::get('/recentview', 'recentViewedListings');            
            Route::get('/homePastSearches', 'homePastSearches');     
            Route::get('/searchById/{id}', 'searchById'); 
            Route::get('/recommendations', 'recommendations');    

        });

        Route::prefix('listings')->controller(ListingReportController::class)->group(function () {
            Route::post('{listingSlug}/report', 'store');
        });

        Route::prefix('listings')->controller(ListingBuyController::class)->group(function () {
            Route::post('{slug}/buy-now', 'buyNow'); // Buy Now
        });

        Route::prefix('appointments')->controller(AppointmentController::class)->group(function () {
            Route::post('/store', 'store');                     // 📌 Buyer: Create a new appointment request
            Route::get('/seller-appointments', 'sellerAppointments'); // 📌 Seller: View all appointment requests for their listings
            Route::post('/{id}/confirm', 'confirm'); // 📌 Seller: Confirm an appointment
            Route::post('/{id}/decline', 'decline'); // 📌 Seller: Decline an appointment
        });

        Route::prefix('watchlist')->controller(WatchlistController::class)->group(function () {
            Route::get('/', 'index');        // 📄 List watchlist
            Route::post('{listingSlug}/store', 'store'); // ⭐ Add
            Route::delete('{listingSlug}/destroy', 'destroy'); // ❌ Remove
        });

        Route::prefix('notification')->controller(NotificationController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/unread', 'unReadList');
            Route::get('/count', 'unreadCount');
            Route::post('/read', 'markAllAsRead');
            Route::post('/{id}/read', 'markAsRead');
        });

        //Delibery address routes
        Route::prefix('delivery-addresses')->controller(DeliveryAddressController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/store', 'store');
            Route::post('/{id}/update', 'update');
            Route::delete('/{id}/destroy', 'destroy');
        });

        //Userlisting results
        Route::prefix('auction-results')->controller(AuctionResultController::class)->group(function () {
            Route::get('/won', 'wonListings');
            Route::get('/lost', 'lostListings');
        });

        //FavouriteController
        Route::prefix('favorites')->controller(FavoriteController::class)->group(function () {
            Route::post('/category/{id}', 'toggleCategory');         // Toggle category favorite
            Route::post('/seller/{id}', 'toggleSeller');             // Toggle seller favorite
            Route::get('/categories', 'listFavoriteCategories');     // List favorite categories
            Route::get('/sellers', 'listFavoriteSellers');           // List favorite sellers
        });

        //Feedback Controller
        Route::prefix('feedback')->controller(UserFeedbackController::class)->group(function () {
            Route::post('/store' , 'store');
            Route::patch('{id}/update', 'update');
            Route::get('stats/{user_id}', 'stats');
            });

        //listing review controller
        Route::prefix('listing-reviews')->controller(ListingReviewController::class)->group(function (){
            Route::post('/store', 'store');
            Route::get('/stats/{listing_id}', 'showStats');
            Route::get('/' , 'index');
        });

        //Comment Controller Route
        Route::prefix('comments')->controller(CommentController::class)->group(function () {
            Route::get('/{listing}',  'index');
            Route::post('/{listing}/comment',  'store');
            Route::post('/{comment}/reply', 'reply');
            Route::post('/{comment}/update',  'update');
            Route::delete('/{comment}/delete',  'destroy');
        });

    });
});
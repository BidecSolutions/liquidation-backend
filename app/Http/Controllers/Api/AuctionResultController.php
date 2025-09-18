<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\Bid;

class AuctionResultController extends Controller
{
    public function wonListings()
    {
        $userId = auth('api')->id();

        $listings = Listing::where('status', 3) // sold
            ->where(function ($q) use ($userId) {
                // Case 1: User won via bidding
                $q->whereHas('winningBid', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                })
                // Case 2: User bought via Buy Now
                ->orWhereHas('buyNowPurchases', function ($sub) use ($userId) {
                    $sub->where('buyer_id', $userId);
                })
                // Case 3: User made an offer that was accepted
                ->orWhereHas('winningOffer', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->where('status', 'accepted'); // assuming "accepted" means won
                });
            })
            ->with([
                'winningBid.user',
                'images',
                'buyNowPurchases.buyer',
                'winningOffer.user',
                'feedbacks.reviewedUser'
            ])
            ->get();


        return response()->json([
            'status' => true,
            'message' => 'Won listings fetched successfully',
            'data' => $listings
        ]);
    }


    public function lostListings()
    {
        $user = auth('api')->user();

        $bidListingIds = Bid::where('user_id', $user->id)
            ->pluck('listing_id')
            ->unique();

        $lostListings = Listing::whereIn('id', $bidListingIds)
            ->where('status', 3) // sold
            ->with(['winningBid', 'images'])
            ->get()
            ->filter(fn($listing) => $listing->winningBid && $listing->winningBid->user_id !== $user->id);

        return response()->json([
            'status' => true,
            'message' => 'Lost listings fetched',
            'data' => $lostListings->values()
        ]);
    }
}

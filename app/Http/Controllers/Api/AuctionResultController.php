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
            ->whereHas('bids', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['winningBid','images','buyNowPurchases', 'feedbacks.reviewedUser'])
            ->get()
            ->filter(function ($listing) use ($userId) {
                return $listing->winningBid && $listing->winningBid->user_id == $userId;
            })
            ->values();

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

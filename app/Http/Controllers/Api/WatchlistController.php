<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Watchlist;
use App\Models\Listing;

class WatchlistController extends Controller
{
    // ⭐ Add to watchlist
    public function store($listingSlug)
    {
        try {
            $userId = auth('api')->id();

            $listing = Listing::where('slug', $listingSlug)->first();
            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null
                ], 404);
            }

            $watch = Watchlist::firstOrCreate([
                'user_id' => $userId,
                'listing_id' => $listing->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Added to watchlist',
                'data' => $watch
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error adding to watchlist',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // ❌ Remove from watchlist
    public function destroy($listingSlug)
    {
        try {
            $userId = auth('api')->id();

            $listing = Listing::where('slug', $listingSlug)->first();

            $deleted = Watchlist::where('user_id', $userId)
                ->where('listing_id', $listing->id)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => $deleted ? 'Removed from watchlist' : 'Item not in watchlist',
                'data' => null
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error removing from watchlist',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // 📄 Get user's watchlist
    public function index()
    {
        try {
            $userId = auth('api')->id();

            $watchlist = Watchlist::with('listing.images')
                ->where('user_id', $userId)
                ->latest()
                ->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'Watchlist fetched successfully',
                'data' => $watchlist
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching watchlist',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}


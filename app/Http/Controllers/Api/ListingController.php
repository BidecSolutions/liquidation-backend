<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\ListingImage;
use App\Models\ListingOffer;
use App\Models\ListingView;
use App\Models\SearchHistory;
use App\Models\UserFeedback;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    // 🔹 Reusable query for Cool Auctions
    private function getCoolAuctions($userId = null, $limit = 10, $offset = 0)
    {
        $limit = $limit ?? 10;
        $listings = Listing::with(['category', 'creator', 'images'])
            ->withCount('bids')
            ->whereNotNull('start_price') // has an end time
            ->where('start_price', '>', 0)
            ->where('expire_at', '>=', now())
            ->has('bids')
            ->orderByDesc('bids_count')
            ->limit($limit)
            ->offset($offset)
            ->get();
        // dd($listings);

        // if ($userId) {
        //     $query->where('created_by', $userId);
        // }

        return $listings;
    }

    // 🔹 Reusable query for Hot Listings
    private function getHotListings($userId = null, $limit = 10, $offset = 0)
    {
        $query = Listing::with(['category', 'creator', 'images'])
            ->withCount(['views', 'watchers'])
            ->where('expire_at', '>=', now())
            ->orderByDesc('views_count')
            ->orderByDesc('watchers_count')
            ->limit($limit)
            ->offset($offset);

        // if ($userId) {
        //     $query->where('created_by', $userId);
        // }

        return $query->get();
    }

    // 🔹 Reusable query for Closing Soon
    private function getClosingSoon($userId = null, $limit = 10, $offset = 0)
    {
        $listings = Listing::with(['category', 'creator', 'images'])
            ->whereNotNull('start_price')
            ->where('expire_at', '>', now())
            ->orderBy('expire_at', 'asc')
            ->limit($limit)
            ->offset($offset)
            ->get();
        // if ($userId) {
        //     $query->where('created_by', $userId);
        // }

        return $listings;
    }

    private function getIsFeatured($userId = null, $limit = 10, $offset = 0)
    {
        $listings = Listing::with(['category', 'creator', 'images'])
            ->where('is_featured', 1)
            ->where('expire_at', '>=', now())
            ->limit($limit)
            ->offset($offset)
            ->get();
        // if ($userId) {
        //     $query->where('created_by', $userId);
        // }

        return $listings;
    }

    private function getRecommendedListings($userId = null, $guestId = null, $limit = 20, $offset = 0)
    {
        // 1. Get latest keywords/categories from search history
        $searchHistories = SearchHistory::query()
            ->when(
                $userId,
                function ($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                },
                function ($q) use ($guestId) {
                    if ($guestId) {
                        $q->where('guest_id', $guestId);
                    }
                }
            )
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $keywords = $searchHistories->pluck('keyword')->filter()->toArray();
        $categoryIds = $searchHistories->pluck('category_id')->filter()->toArray();

        // 2. Get latest viewed listing categories
        $viewedCategories = ListingView::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($guestId, fn ($q) => $q->where('guest_id', $guestId))
            ->orderBy('created_at', 'desc')
            ->with('listing:id,category_id')
            ->limit(5)
            ->get()
            ->pluck('listing.category_id')
            ->filter()
            ->toArray();

        // Merge categories from searches + views
        $allCategoryIds = array_unique(array_merge($categoryIds, $viewedCategories));

        // 3. Build recommended listings query
        $recommendations = Listing::with(['images', 'category', 'creator'])
            ->withCount('views', 'watchers as watch_count', 'bids')
            ->where('expire_at', '>=', now())
            ->where('status', 1)
            ->when(count($keywords), function ($q) use ($keywords) {
                $q->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('title', 'LIKE', "%{$word}%")
                            ->orWhere('description', 'LIKE', "%{$word}%");
                    }
                });
            })
            ->when(count($allCategoryIds), fn ($q) => $q->orWhereIn('category_id', $allCategoryIds))
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $recommendations;
    }

    public function index(Request $request)
    {
        try {
            $query = Listing::with(['category', 'creator', 'images', 'bids.user', 'winningBid.user', 'buyNowPurchases.buyer', 'attributes', 'paymentMethod:id,name', 'shippingMethod:id,name'])->withCount('views');

            // 🔒 Filter by creator if authenticated (user guard)
            $authUserId = auth('api')->check() ? auth('api')->id() : null;
            if ($authUserId) {
                $query->where('created_by', $authUserId);
            }

            // 🔎 Filter by search keyword
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%')
                        ->orWhere('subtitle', 'like', '%'.$request->search.'%')
                        ->orWhere('brand', 'like', '%'.$request->search.'%')
                        ->orWhere('color', 'like', '%'.$request->search.'%')
                        ->orWhere('size', 'like', '%'.$request->search.'%')
                        ->orWhere('style', 'like', '%'.$request->search.'%')
                        ->orWhere('memory', 'like', '%'.$request->search.'%')
                        ->orWhere('hard_drive_size', 'like', '%'.$request->search.'%')
                        ->orWhere('cores', 'like', '%'.$request->search.'%')
                        ->orWhere('storage', 'like', '%'.$request->search.'%')
                        ->orWhereHas('category', function ($q2) use ($request) {
                            $q2->where('name', 'like', '%'.$request->search.'%');
                        });
                });
            }

            if ($request->has('slug')) {
                $query->where('slug', $request->slug);
            }

            if ($request->has('city')) {
                $query->whereHas('creator', function ($q) use ($request) {
                    $q->where('city', $request->city);
                });
            }

            if ($request->has('listing_type')) {
                $query->where('listing_type', $request->listing_type);
            }

            // if ($request->has('status')) {
            //     $query->where('status', $request->status);
            // }

            // if ($request->has('pending_reserve_approval')) {
            //     $query->orWhere('status', $request->pending_reserve_approval);
            // }

            if ($request->has('status')) {
                $statuses = is_array($request->status)
                    ? $request->status
                    : explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->has('not_equal_status')) {
                $query->where('status', '!=', $request->not_equal_status);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('reserve_price')) {
                $query->where('reserve_price', $request->reserve_price);
            }

            // Filter by category and its children
            if ($request->filled('category_id')) {
                $categoryIds = $this->getAllCategoryIds($request->category_id);
                $query->whereIn('category_id', $categoryIds);
            }

            if ($request->filled('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->filled('price_from')) {
                $query->where('start_price', '>=', $request->price_from);
            }

            if ($request->filled('price_to')) {
                $query->where('start_price', '<=', $request->price_to);
            }

            $listings = $query->latest()->paginate(20);

            $listings->each(function ($listing) use ($authUserId) {
                $listing->bid_count = $listing->bids()->count();
                $listing->view_count = $listing->views()->count();

                // 💼 Offers made by user
                $listing->buying_offers = $authUserId
                    ? ListingOffer::with(['user'])->where('listing_id', $listing->id)
                        ->where('user_id', $authUserId)
                        ->get()
                    : collect();

                // 🧾 Offers received by the user (as seller)
                $listing->selling_offers = $authUserId && $listing->created_by == $authUserId
                    ? ListingOffer::with(['user'])->where('listing_id', $listing->id)
                        ->get()
                    : collect();
            });

            return response()->json([
                'status' => true,
                'message' => 'Listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function coolAuctions(Request $request)
    {
        try {
            $limit = $request->input('limit');
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getCoolAuctions($userId, $limit);

            return response()->json([
                'status' => true,
                'message' => 'Cool auctions fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching cool auctions',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function hotListings(Request $request)
    {
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getHotListings($userId);

            return response()->json([
                'status' => true,
                'message' => 'Hot listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching hot listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function closingSoon(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getClosingSoon($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Closing soon listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching closing soon listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function isfeatured(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getCoolAuctions($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Closing soon listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching closing soon listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function recommendations(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $userId = auth('api')->id();
        $guestId = $request->header('X-Guest-ID');

        if (! $userId && ! $guestId) {
            return response()->json([
                'status' => false,
                'message' => 'User or Guest ID required',
                'data' => [],
            ], 400);
        }

        $recommendations = $this->getRecommendedListings($userId, $guestId, $limit, $offset);

        return response()->json([
            'status' => true,
            'message' => 'Recommendations fetched successfully',
            'data' => $recommendations,
        ]);
    }

    /* FULL API OF HOME PAGE */
    public function mainapi(Request $request)
    {
        try {
            $cool_auctions = $this->getCoolAuctions(null, 10);
            $hot_listings = $this->getHotListings(null, 10);
            $closing_soon = $this->getClosingSoon(null, 10);
            $is_featured = $this->getIsFeatured(null, 10);

            if (auth('api')->check()) {
                $userId = auth('api')->id();
                $recommendations = $this->getRecommendedListings($userId, null, 10, 0);
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                $recommendations = $this->getRecommendedListings(null, $guestId, 10, 0);
            } else {
                $recommendations = [];
            }

            return response()->json([
                'status' => true,
                'message' => 'Home page data fetched successfully',
                'data' => [
                    'cool_auctions' => $cool_auctions,
                    'hot_listings' => $hot_listings,
                    'closing_soon' => $closing_soon,
                    'is_featured' => $is_featured,
                    'recommendations' => $recommendations,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching home page data',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function filterListings(Request $request)
    {
        $guestId = $request->header('X-Guest-ID');
        // dd($guestId);
        $query = Listing::query()
            ->with([
                'images',
                'category',
                'creator',
                'attributes',
                'watchers',
                'paymentMethod:id,name',
                'shippingMethod:id,name',
            ])->withCount('views', 'watchers', 'bids')
            ->where('listing_type', $request->listing_type) // ✅ Only listings with the requested type
            ->where('status', 1)
            ->where('is_active', 1)
            ->where('expire_at', '>=', now());
        // ✅ Filter by category_id
        $categoryTree = null;
        if ($request->filled('category_id')) {
            // for parent fetching
            $categoryTree = Category::select('id', 'name', 'slug', 'parent_id')
                ->with('parentRecursive:id,name,slug,parent_id')
                ->find($request->category_id);
            // for fetching all childeren listings
            $category = Category::with('children')->find($request->category_id);
            if ($category) {
                $categoryIds = $category->allchildrenIds();
                $query->whereIn('category_id', $categoryIds);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 1);
        }

        // // ✅ Filter by category_type (join with categories table)
        // if ($request->filled('type')) {
        //     $query->where('listing_type', $request->type);
        // }

        // ✅ Predefined keys that should use range logic (besides price)
        $rangeKeys = ['year', 'odometer', 'land_size', 'bedrooms', 'bathrooms'];

        // ✅ Dynamic attribute filters
        if ($request->filled('filters') && is_array($request->filters)) {
            foreach ($request->filters as $key => $value) {
                $query->whereHas('attributes', function ($q) use ($key, $value, $rangeKeys) {
                    // Multiple values -> IN
                    if (is_array($value) && ! isset($value['min']) && ! isset($value['max'])) {
                        $q->where('key', $key)->whereIn('value', $value);
                    }
                    // Range filter (for predefined keys)
                    elseif (is_array($value) && (isset($value['min']) || isset($value['max'])) && in_array($key, $rangeKeys)) {
                        $q->where('key', $key);
                        if (isset($value['min'])) {
                            $q->where('value', '>=', $value['min']);
                        }
                        if (isset($value['max'])) {
                            $q->where('value', '<=', $value['max']);
                        }
                    }
                    // Single value
                    else {
                        $q->where('key', $key)->where('value', $value);
                    }
                });
            }
        }

        // ✅ Price range
        if ($request->filled('min_price')) {
            $query->where('buy_now_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('buy_now_price', '<=', $request->max_price);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('city')) {
            $query->whereHas('creator', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }
        if ($request->filled('search')) {
            // $query->where('title', 'LIKE', '%'. $request->search. '%');

            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%");
                //  ->orWhere('subtitle', 'LIKE', "%{$search}%")
            });
        }
        $sortOrder = strtolower($request->input('sort', 'desc'));
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        $query->orderBy('created_at', $sortOrder);

        // ✅ Pagination
        $perPage = $request->input('pagination.per_page', 20);
        $listings = $query->paginate($perPage);

        $listingData = $listings->getCollection()->map(function ($listing) {
            $listingArray = $listing->toArray();
            unset($listingArray['attributes']);
            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();

            return array_merge($listingArray, $attributes);
        });

        // ✅ Keep pagination meta
        $pagination = [
            'current_page' => $listings->currentPage(),
            'per_page' => $listings->perPage(),
            'total' => $listings->total(),
            'last_page' => $listings->lastPage(),
            'next_page_url' => $listings->nextPageUrl(),
            'prev_page_url' => $listings->previousPageUrl(),
        ];

        // ✅ Build category path if category is selected
        $categoryPath = null;
        $categoryName = null;
        if ($request->filled('category_id')) {
            $category = Category::with('parentRecursive')->find($request->category_id);

            if ($category) {
                // Full breadcrumb path

                $path = [];
                $c = $category;
                while ($c) {
                    array_unshift($path, $c->name);
                    $c = $c->parent;
                }
                $categoryPath = implode(' > ', $path);
                $categoryName = $category->name;
            }
        }

        // ✅ Capture filters (if any)
        $filters = $request->filters ?? [];

        if ($request->filled('search') || $request->filled('category_id') || ! empty($filters)) {
            $keyword = $request->input('search', $categoryName ?? '');

            if (auth('api')->check()) {
                // Logged-in user
                $userId = auth('api')->id();

                SearchHistory::updateOrCreate(
                    ['user_id' => $userId, 'keyword' => strtolower(trim($keyword))],
                    [
                        'count' => DB::raw('count + 1'),
                        'category_id' => $request->input('category_id', null),
                        'category_path' => $categoryPath ?? null,
                        'filters' => ! empty($filters) ? json_encode($filters) : null,
                    ]
                );
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                // Guest user
                $history = SearchHistory::firstOrNew([
                    'guest_id' => $guestId,
                    'keyword' => strtolower(trim($keyword)),
                ]);

                $history->count = ($history->exists ? $history->count + 1 : 1);
                $history->category_id = $request->input('category_id', null);
                $history->category_path = $categoryPath ?? null;
                $history->filters = ! empty($filters) ? json_encode($filters) : null;
                $history->guest_id = $guestId;

                $history->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Filtered listings fetched successfully',
            'data' => $listingData,
            'pagination' => $pagination, 
            'category_tree' => $categoryTree,
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
        ]);

        $keyword = strtolower(trim($request->keyword));

        $maincategory = Category::whereRaw('LOWER(name) = ?', [$keyword])->with('parentRecursive')->first();
        if ($maincategory) {
            $categoryIds = $maincategory->allchildrenIds();

            $query = Listing::with(['images', 'category', 'creator'])->withCount('views')->where('status', 1)->whereIn('category_id', $categoryIds);

            $results = $query->get();

            $path = [];
            $c = $maincategory;
            while ($c) {
                array_unshift($path, $c->name);
                $c = $c->parent;
            }
            $categoryPath = implode('>', $path);
        } else {
            $query = Listing::with(['images', 'category', 'creator'])->withCount('views')
                ->where('status', 1)
                ->where(function ($q) use ($keyword) {
                    $q->where('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            $results = $query->get();
            $categoryIds = $results->pluck('category_id')->unique();

            $categoryPath = null;
            if ($categoryIds->count() === 1) {
                $category = Category::with('parentRecursive')->find($categoryIds->first());
                if ($category) {
                    $path = [];
                    $c = $category;
                    while ($c) {
                        array_unshift($path, $c->name);
                        $c = $c->parent;
                    }
                    $categoryPath = implode(' > ', $path);
                }
            }
        }

        // ✅ Apply limit & offset (manual pagination)
        $limit = $request->input('limit');
        $offset = $request->input('offset');

        if ($limit !== null && $offset !== null) {
            $results = $query->limit((int) $limit)->offset((int) $offset)->get();
        } else {
            $results = $query->get(); // return all if no limit/offset
        }

        // ✅ Transform results (merge attributes like in filterListings)
        $listingData = $results->map(function ($listing) {
            $listingArray = $listing->toArray();
            unset($listingArray['attributes']);
            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();

            return array_merge($listingArray, $attributes);
        });

        // ✅ Save search history
        if (auth('api')->check()) {
            SearchHistory::updateOrCreate(
                [
                    'user_id' => auth('api')->id(),
                    'keyword' => $keyword,
                ],
                [
                    'count' => DB::raw('count + 1'),
                    'category_path' => $categoryPath ?? null,
                ]
            );
        } elseif ($request->header('X-Guest-ID')) {
            $guestId = $request->header('X-Guest-ID');

            $searchHistory = SearchHistory::firstOrNew([
                'guest_id' => $guestId,
                'keyword' => $keyword,
            ]);
            $searchHistory->count = ($searchHistory->exists ? $searchHistory->count + 1 : 1);
            $searchHistory->guest_id = $guestId;
            $searchHistory->category_id = $category->id ?? null;
            $searchHistory->category_path = $categoryPath ?? null;
            $searchHistory->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Search results fetched successfully',
            'data' => $listingData,
            'total' => $query->count(),
            'category_path' => $categoryPath ?? null,
        ]);
    }

    public function suggestions(Request $request)
    {

        $query = $request->query('query');

        $suggestions = collect();
        $webSuggestions = null;
        if ($query) {
            $request->validate([
                'query' => 'required|string|max:255',
            ]);
            // ✅ Fetch suggestions from listings
            $suggestions = Listing::where('status', 1)
                ->where('title', 'LIKE', "%{$query}%")
                ->limit(10)
                ->pluck('title');
            $webSuggestions = Listing::where('status', 1)
                ->where('title', 'LIKE', "%{$query}%")
                ->limit(10)
                ->with(['images:id,listing_id,image_path'])
                ->select('id', 'title', 'slug', 'buy_now_price')->get();
        }

        // ✅ Past searches only if user is logged in
        $pastSearches = [];
        if (auth('api')->check()) {
            $pastSearches = SearchHistory::where('user_id', auth('api')->id())
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->pluck('keyword');
        } elseif ($guestId = $request->header('X-Guest-ID')) {
            $pastSearches = SearchHistory::where('guest_id', $guestId)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->pluck('keyword');
        }

        return response()->json([
            'status' => true,
            'message' => 'Suggestions fetched successfully',
            'suggestions' => $suggestions,
            'past_searches' => $pastSearches,
            'web_suggestions' => $webSuggestions,
        ]);
    }

    public function homePastSearches()
    {
        $searchResults = [];

        if (! auth('api')->check()) {
            // dd("AWfawf");
            $guestId = request()->header('X-Guest-ID');
            if (! $guestId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Guest ID missing',
                    'data' => [],
                ], 400);
            }

            // ✅ Correct column -> guest_id, not user_id
            $pastSearches = SearchHistory::where('guest_id', $guestId)
                ->orderBy('updated_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($pastSearches as $search) {
                $keyword = $search->keyword;

                if ($search->category_id === null) {
                    // ✅ Keyword search
                    $listings = Listing::with(['images', 'category', 'creator'])->withCount('views')
                        ->where('status', 1)
                        ->where(function ($q) use ($keyword) {
                            $q->where('title', 'LIKE', "%{$keyword}%")
                                ->orWhere('description', 'LIKE', "%{$keyword}%");
                        })
                        ->limit(5)
                        ->get();
                } else {
                    // ✅ Category-based search
                    $categoryIds = $this->getAllCategoryIds($search->category_id);
                    $listings = Listing::with(['images', 'category', 'creator'])->withCount('views')
                        ->where('status', 1)
                        ->whereIn('category_id', $categoryIds)
                        ->limit(5)
                        ->get();
                }

                $searchResults[] = [
                    'id' => $search->id,
                    'keyword' => $keyword,
                    'path' => $search->category_path,
                    'listings' => $listings,
                ];
            }
        } else {
            $userId = auth('api')->id();

            $pastSearches = SearchHistory::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($pastSearches as $search) {
                $keyword = $search->keyword;

                if ($search->category_id === null) {
                    $listings = Listing::with(['images', 'category', 'creator'])->withCount('views')
                        ->where('status', 1)
                        ->where(function ($q) use ($keyword) {
                            $q->where('title', 'LIKE', "%{$keyword}%")
                                ->orWhere('description', 'LIKE', "%{$keyword}%");
                        })
                        ->limit(5)
                        ->get();
                } else {
                    $categoryIds = $this->getAllCategoryIds($search->category_id);
                    $listings = Listing::with(['images', 'category', 'creator'])->withCount('views')
                        ->where('status', 1)
                        ->whereIn('category_id', $categoryIds)
                        ->limit(5)
                        ->get();
                }

                $searchResults[] = [
                    'id' => $search->id,
                    'keyword' => $keyword,
                    'path' => $search->category_path,
                    'listings' => $listings,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Home suggestions fetched successfully',
            'data' => $searchResults,
        ]);
    }

    public function removePastSearch($searchId)
    {
        try {
            if (! auth('api')->check()) {
                $guestId = request()->header('X-Guest-ID');
                if (! $guestId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Guest ID missing',
                        'data' => [],
                    ], 400);
                }
                $search = SearchHistory::where('id', $searchId)->where('guest_id', $guestId)->first();
                if (! $search) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Search not found',
                        'data' => [],
                    ], 404);
                }
                $search->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Search removed successfully',
                    'data' => [],
                ]);
            } else {
                $userId = auth('api')->id();
                $search = SearchHistory::where('id', $searchId)->where('user_id', $userId)->first();
                if (! $search) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Search not found',
                        'data' => [],
                    ], 404);
                }
                $search->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Search removed successfully',
                    'data' => [],
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'somthing went wrong',
                'error' => $e,
            ]);
        }
    }

    public function filtersMetadata(Request $request)
    {
        $listingType = $request->input('listing_type');

        $filters = ListingAttribute::query()
            ->whereHas('listing', function ($q) use ($listingType) {
                $q->where('listing_type', $listingType);
            })
            ->select('key', 'value')
            ->distinct()
            ->get()
            ->groupBy('key')
            ->map(function ($items) {
                return $items->pluck('value')->unique()->values();
            });

        return response()->json([
            'status' => true,
            'listing_type' => $listingType,
            'filters' => $filters,
        ]);
    }

    public function searchById(Request $request, $id)
    {
        try {
            $listing = Listing::with(['images', 'category', 'creator', 'attributes', 'bids.user', 'winningBid.user', 'buyNowPurchases.buyer'])->withCount('views')->findOrFail($id);

            // Increment view count
            if (auth('api')->check()) {
                $userId = auth('api')->id();
                ListingView::firstOrCreate(
                    ['listing_id' => $listing->id, 'user_id' => $userId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                ListingView::firstOrCreate(
                    ['listing_id' => $listing->id, 'guest_id' => $guestId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing fetched successfully',
                'data' => $listing,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found',
                'data' => null,
            ], 404);
        }
    }

    // Get listings by type (jobs, motors, property, services, marketplace)
    public function indexByType($type)
    {
        try {
            $listings = Listing::with(['user', 'category'])->byType($type)->get();

            return response()->json(['status' => true, 'data' => $listings]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch listings by type', 'error' => $e->getMessage()], 500);
        }
    }

    private function getAllCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];

        $children = Category::where('parent_id', $categoryId)->pluck('id');

        foreach ($children as $childId) {
            $categoryIds = array_merge($categoryIds, $this->getAllCategoryIds($childId));
        }

        return $categoryIds;
    }

    public function recentViewedListings() {}

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'required|string',
                'condition' => 'required|in:new,used,brand_new,ready_to_move,under_construction,furnished,semi_furnished,unfurnished,recently_renovated',
                'start_price' => 'nullable|numeric|min:0',
                'reserve_price' => 'nullable|numeric|min:0',
                'buy_now_price' => 'nullable|numeric|min:0',
                'allow_offers' => 'boolean',
                'quantity' => 'integer|min:1',
                'authenticated_bidders_only' => 'boolean',
                'pickup_option' => 'nullable|in:no_pickup,pickup_available,must_pickup',
                'shipping_method_id' => 'nullable|exists:shipping_methods,id',
                'payment_method_id' => 'nullable|exists:payment_methods,id',
                'color' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'style' => 'nullable|string|max:100',
                'memory' => 'nullable|string|max:100',
                'hard_drive_size' => 'nullable|string|max:100',
                'cores' => 'nullable|string|max:100',
                'storage' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'meta_title' => 'nullable|string|max:255',
                'latitude' => 'nullable|string|max:50',
                'longitude' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'expire_at' => 'nullable|date|after:now',
                'images.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'attributes' => 'array',
                'attributes.*.key' => 'required|string',
                'attributes.*.value' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $category = Category::find($data['category_id']);
            // return $category;
            if ($data['listing_type'] != $category->category_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Must Select the same category as the listing type',
                    'data' => null,
                ], 422);
            }
            $data['slug'] = Str::slug($request->title.'-'.uniqid());
            $data['status'] = 1;
            $data['is_active'] = 1;
            $data['created_by'] = auth('api')->id(); // or auth('admin-api')->id()

            $listing = Listing::create($data);

            // Save attributes
            if (! empty($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create([
                        'key' => $attr['key'],
                        'value' => $attr['value'],
                    ]);
                }
            }

            // 📁 Ensure directory exists
            $directory = 'listings/images';
            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0775, true);
            }

            // 🖼 Upload images (max 20)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($index >= 20) {
                        break;
                    }

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $index,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing created successfully',
                'data' => $listing->load(['images', 'attributes']),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        $item = Listing::find($id);

        if (! $item) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $item->note = $request->note;
        $item->save();

        return response()->json(['status' => true, 'message' => 'Note updated successfully', 'data' => $item]);
    }

    public function deleteNote($id)
    {
        $item = Listing::find($id);

        if (! $item) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $item->note = null;
        $item->save();

        return response()->json(['status' => true, 'message' => 'Note deleted successfully']);
    }

    public function show($slug)
    {
        try {
            $listing = Listing::with([
                'category',
                'creator',
                'images',
                'bids.user',
                'winningBid.user',
                'attributes',
                'comments.user:id,name,profile_photo',
                'comments.replies.user:id,name,profile_photo',
            ])
                ->withCount('views')
                ->where('slug', $slug)
                ->first();
            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }

            $listing->bid_count = $listing->bids()->count();
            $listing->view_count = $listing->views()->count();

            // Log listing view with caching
            // Log listing view with caching
            $cacheKey = 'listing_viewed_'.$listing->id.'_'.request()->ip();
            if (! Cache::has($cacheKey)) {
                $data = [
                    'listing_id' => $listing->id,
                    'ip_address' => request()->ip(),
                ];

                if (auth('api')->check()) {
                    $data['user_id'] = auth('api')->id();
                } else {
                    // fallback to guest_id (you must have guest auth/identifier logic)
                    $data['guest_id'] = request()->header('X-Guest-Id') ?? session()->getId();
                }

                ListingView::create($data);
                Cache::put($cacheKey, true, now()->addHour());
            }

            $buyingOffers = collect();
            $sellingOffers = collect();

            if (auth('api')->check()) {
                $userId = auth('api')->id();

                $buyingOffers = ListingOffer::with('user')
                    ->where('listing_id', $listing->id)
                    ->where('user_id', $userId)
                    ->get();

                if ($listing->created_by == $userId) {
                    $sellingOffers = ListingOffer::with('user')
                        ->where('listing_id', $listing->id)
                        ->get();
                }
            }

            // calculate positive feedback percentage
            $totalFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)->count();
            $positiveFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)
                ->whereIn('rating', [4, 5])
                ->count();

            $positiveFeedbackPercentage = $totalFeedbackCount > 0
                ? round(($positiveFeedbackCount / $totalFeedbackCount) * 100, 1)
                : 0;

            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();
            $listingData = array_merge($listing->toArray(), $attributes);
            unset($listingData['attributes']);

            $dealersListing = Listing::with('images:id,listing_id,image_path')
                ->when(
                    $listingData['created_by'] ?? null,
                    fn ($q, $created_by) => $q->where('created_by', $created_by)
                )
                ->when(
                    $listingData['listing_type'] ?? null,
                    fn ($q, $listingType) => $q->where('listing_type', $listingType)
                )
                ->when(
                    $listingData['is_active'] ?? null,
                    fn ($q, $IsActive) => $q->where('is_active', $IsActive)
                )
                ->select('id', 'title', 'slug', 'description', 'listing_type', 'condition', 'start_price', 'buy_now_price', 'created_by')
                ->limit(4)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Listing fetched successfully',
                'data' => [
                    'listing' => $listingData,
                    'buying_offers' => $buyingOffers,
                    'selling_offers' => $sellingOffers,
                    'creator_feedback_percentage' => $positiveFeedbackPercentage,
                    'dealers_other_listings' => $dealersListing,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $slug)
    {
        try {
            $listing = Listing::where('slug', $slug)->with('images')->first();

            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'sometimes|string',
                'condition' => 'required|in:new,used,brand_new,ready_to_move,under_construction,furnished,semi_furnished,unfurnished,recently_renovated',
                'start_price' => 'nullable|numeric|min:0',
                'reserve_price' => 'nullable|numeric|min:0',
                'buy_now_price' => 'nullable|numeric|min:0',
                'allow_offers' => 'boolean',
                'quantity' => 'integer|min:1',
                'authenticated_bidders_only' => 'boolean',
                'pickup_option' => 'required|in:no_pickup,pickup_available,must_pickup',
                'shipping_method_id' => 'nullable|exists:shipping_methods,id',
                'payment_method_id' => 'nullable|exists:payment_methods,id',
                'color' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'style' => 'nullable|string|max:100',
                'memory' => 'nullable|string|max:100',
                'hard_drive_size' => 'nullable|string|max:100',
                'cores' => 'nullable|string|max:100',
                'storage' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'expire_at' => 'nullable|date|after:now',
                'images.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'attributes' => 'array',
                'attributes.*.key' => 'required|string',
                'attributes.*.value' => 'nullable|string',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $listing->update($data);

            //  Sync attributes
            if (! empty($data['attributes'])) {
                $listing->attributes()->delete();
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create($attr);
                }
            }

            // ✅ Only process images if provided
            if ($request->hasFile('images')) {
                $directory = 'listings/images';
                if (! Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0775, true);
                }

                $existingCount = $listing->images->count();

                foreach ($request->file('images') as $index => $image) {
                    if (($existingCount + $index) >= 20) {
                        break;
                    }

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $existingCount + $index,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing updated successfully',
                'data' => $listing->load('images', 'attributes'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // Deleting images
    public function deleteImage($id)
    {
        try {
            $image = ListingImage::find($id);

            if (! $image) {
                return response()->json([
                    'status' => false,
                    'message' => 'Image not found',
                ], 404);
            }

            // Delete image file from storage
            Storage::disk('public')->delete($image->image_path);

            // Delete record from database
            $image->delete();

            return response()->json([
                'status' => true,
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function filters()
    {
        try {
            $conditions = Listing::select('condition')
                ->distinct()
                ->pluck('condition');

            $categories = Category::whereHas('listings')
                ->withCount('listings')
                ->get();

            $priceRange = [
                'min' => Listing::min('start_price'),
                'max' => Listing::max('start_price'),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Filters fetched successfully',
                'data' => [
                    'conditions' => $conditions,
                    'categories' => $categories,
                    'price_range' => $priceRange,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching filters',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($slug)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or unauthorized',
                'data' => null,
            ], 404);
        }

        // Toggle between 1 and 0 for integer type
        $listing->is_active = $listing->is_active == 1 ? 0 : 1;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing activation toggled successfully',
            'data' => [
                'listing_id' => $listing->id,
                'is_active' => $listing->is_active,
            ],
        ]);
    }

    public function destroy($slug)
    {
        try {
            $listing = Listing::where('slug', $slug)->first();

            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }

            // Delete associated images
            foreach ($listing->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            $listing->delete();

            return response()->json([
                'status' => true,
                'message' => 'Listing deleted successfully',
                'data' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting listing',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function withdraw($slug)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [0, 1]) // Only withdraw if pending/approved
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be withdrawn',
                'data' => null,
            ], 404);
        }

        $listing->status = 5; // Withdrawn
        $listing->save();

        // Optional: notify bidders, log event

        return response()->json([
            'status' => true,
            'message' => 'Listing withdrawn successfully',
            'data' => $listing,
        ]);
    }

    public function relist($slug, Request $request)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [2, 4, 5]) // rejected, expired, withdrawn
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be re-listed',
                'data' => null,
            ], 404);
        }

        $listing->status = 1;
        $listing->is_active = 1;
        $listing->expire_at = $request->input('expire_at', now()->addDays(7)); // optional
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing re-listed successfully',
            'data' => $listing,
        ]);
    }

    public function views(Request $request)
    {
        try {
            $query = ListingView::with(['listing', 'user'])
                ->orderBy('created_at', 'desc');

            // 🔒 Filter by authenticated user
            if (auth('api')->check()) {
                $query->where('user_id', auth('api')->id());
            }

            // 📌 Filter by listing ID
            if ($request->filled('listing_id')) {
                $query->where('listing_id', $request->listing_id);
            }

            $views = $query->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'Listing views fetched successfully',
                'data' => $views,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing views',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function approve($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed',
            ], 422);
        }

        $listing->status = 1;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing approved successfully',
        ]);
    }

    public function reject($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed',
            ], 422);
        }

        $listing->status = 2;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing rejected successfully',
        ]);
    }
}

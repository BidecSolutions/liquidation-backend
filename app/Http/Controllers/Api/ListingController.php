<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListingAttribute;
use App\Models\UserFeedback;
use Exception;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingView;
use App\Models\ListingImage;
use App\Models\ListingOffer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;


class ListingController extends Controller
{
    // public function index(Request $request)
    // {
    //     try {
    //         $query = Listing::with(['category', 'creator', 'images', 'bids.user']);
    //         // ->where('status', '!=', 3) // Exclude deleted listings
    //         // ->where('expire_at', '>', now()); // Only active listings

    //         // 🔒 Filter by creator if authenticated (user guard)
    //         if (auth('api')->check()) {
    //             $query->where('created_by', auth('api')->id());
    //         }

    //         // 📌 Filter by search
    //         if ($request->has('search')) {
    //             $query->where('title', 'like', '%' . $request->search . '%')
    //                 ->orWhere('description', 'like', '%' . $request->search . '%')
    //                 ->orWhere('subtitle', 'like', '%' . $request->search . '%')
    //                 ->orWhere('brand', 'like', '%' . $request->search . '%')
    //                 ->orWhere('color', 'like', '%' . $request->search . '%')
    //                 ->orWhere('size', 'like', '%' . $request->search . '%')
    //                 ->orWhere('style', 'like', '%' . $request->search . '%')
    //                 ->orWhere('memory', 'like', '%' . $request->search . '%')
    //                 ->orWhere('hard_drive_size', 'like', '%' . $request->search . '%')
    //                 ->orWhere('cores', 'like', '%' . $request->search . '%')
    //                 ->orWhere('storage', 'like', '%' . $request->search . '%')
    //                 ->orWhereHas('category', function ($q) use ($request) {
    //                     $q->where('name', 'like', '%' . $request->search . '%');
    //                 });
    //         }

    //         // 📌 Filter by slug
    //         if ($request->has('slug')) {
    //             $query->where('slug', $request->slug);
    //         }

    //         // 📌 Filter by status
    //         if ($request->has('status')) {
    //             $query->where('status', $request->status);
    //         }

    //         // 📌 Filter by status
    //         if ($request->has('not_equal_status')) {
    //             $query->where('status', '!=', $request->not_equal_status);
    //         }

    //         // 📌 Filter by isactive status
    //         if ($request->has('is_active')) {
    //             $query->where('is_active', $request->is_active);
    //         }

    //         // 📌 Filter by reserve price
    //         if ($request->has('reserve_price')) {
    //             $query->where('reserve_price', $request->reserve_price);
    //         }

    //         // 📌 Filter by category + child categories
    //         if ($request->filled('category_id')) {
    //             $categoryIds = $this->getAllCategoryIds($request->category_id);
    //             $query->whereIn('category_id', $categoryIds);
    //         }

    //         // 📌 Filter by condition (new/used)
    //         if ($request->filled('condition')) {
    //             $query->where('condition', $request->condition);
    //         }

    //         // 📌 Filter by price range
    //         if ($request->filled('price_from')) {
    //             $query->where('start_price', '>=', $request->price_from);
    //         }

    //         if ($request->filled('price_to')) {
    //             $query->where('start_price', '<=', $request->price_to);
    //         }

    //         $listings = $query->latest()->paginate(20);
    //         $listings->each(function ($listing) {
    //             $listing->bid_count = $listing->bids()->count();
    //             $listing->view_count = $listing->views()->count();
    //             // $listing->is_watched = auth('api')->check() ? $listing->watchlists()->where('user_id', auth('api')->id())->exists() : false;
    //         });

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Listings fetched successfully',
    //             'data' => $listings
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error fetching listings',
    //             'data' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $query = Listing::with(['category', 'creator', 'images', 'bids.user', 'winningBid.user', 'buyNowPurchases.buyer', 'attributes']);

            // 🔒 Filter by creator if authenticated (user guard)
            $authUserId = auth('api')->check() ? auth('api')->id() : null;
            if ($authUserId) {
                $query->where('created_by', $authUserId);
            }

            // 🔎 Filter by search keyword
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                        ->orWhere('brand', 'like', '%' . $request->search . '%')
                        ->orWhere('color', 'like', '%' . $request->search . '%')
                        ->orWhere('size', 'like', '%' . $request->search . '%')
                        ->orWhere('style', 'like', '%' . $request->search . '%')
                        ->orWhere('memory', 'like', '%' . $request->search . '%')
                        ->orWhere('hard_drive_size', 'like', '%' . $request->search . '%')
                        ->orWhere('cores', 'like', '%' . $request->search . '%')
                        ->orWhere('storage', 'like', '%' . $request->search . '%')
                        ->orWhereHas('category', function ($q2) use ($request) {
                            $q2->where('name', 'like', '%' . $request->search . '%');
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
                'data' => $listings
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listings',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function filterListings(Request $request)
    {
        $query = Listing::query()
            ->with(['images', 'category', 'creator'])
            ->where('listing_type', $request->listing_type); // ✅ Only listings with the requested type

        // ✅ Filter by category_id
        if ($request->filled('category_id')) {
            $category = Category::with('children')->find($request->category_id);

            if($category){
                $categoryIds = $category->allchildrenIds();
                $query->whereIn('category_id', $categoryIds);
            }
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
                    if (is_array($value) && !isset($value['min']) && !isset($value['max'])) {
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
        if($request->filled('condition')){
            $query->where('condition', $request->condition);
        }
        if($request->filled('city')){
            $query->whereHas('creator', function($q) use ($request) {
                $q->where('city', $request->city);
            });
        }
        if($request->filled('search')){
            // $query->where('title', 'LIKE', '%'. $request->search. '%');

            $search = $request->search;
            $query->where(function ($q) use ($search){
                 $q->where('title', 'LIKE', "%{$search}%");
                //  ->orWhere('subtitle', 'LIKE', "%{$search}%")
            });
        }
        

        // ✅ Pagination
        $perPage = $request->input('pagination.per_page', 20);
        $listings = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Filtered listings fetched successfully',
            'data' => $listings
        ]);
    }

    public function filtersMetadata(Request $request)
    {
        $listingType = $request->input('listing_type');
        
        $filters = ListingAttribute::query()
               ->whereHas('listing', function($q) use ($listingType){
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
                'filters' => $filters
               ]);
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

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'required|string',
                'condition' => 'required|in:new,used',
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
                'attributes' => 'array', // ✅ extra dynamic attributes
                'attributes.*.key' => 'required|string',
                'attributes.*.value' => 'nullable|string',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['slug'] = Str::slug($request->title . '-' . uniqid());
            $data['status'] = 1;
            $data['is_active'] = 1;
            $data['created_by'] = auth('api')->id(); // or auth('admin-api')->id()

            $listing = Listing::create($data);

            // Save attributes
            if (!empty($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create([
                        'key' => $attr['key'],
                        'value' => $attr['value'],
                    ]);
                }
            }

            // 📁 Ensure directory exists
            $directory = 'listings/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0775, true);
            }

            // 🖼 Upload images (max 20)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($index >= 20)
                        break;

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $index
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing created successfully',
                'data' => $listing->load(['images', 'attributes'])
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function updateNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        $item = Listing::find($id);

        if (!$item) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $item->note = $request->note;
        $item->save();

        return response()->json(['status' => true, 'message' => 'Note updated successfully', 'data' => $item]);
    }

    public function deleteNote($id)
    {
        $item = Listing::find($id);

        if (!$item) {
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
            ])
                ->where('slug', $slug)
                ->first();
            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null
                ], 404);
            }

            $listing->bid_count = $listing->bids()->count();
            $listing->view_count = $listing->views()->count();

            // Log listing view with caching
            $cacheKey = 'listing_viewed_' . $listing->id . '_' . request()->ip();
            if (!Cache::has($cacheKey)) {
                ListingView::create([
                    'listing_id' => $listing->id,
                    'user_id' => auth('api')->id(),
                    'ip_address' => request()->ip()
                ]);
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

            //calculate positive feedback percentage
            $totalFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)->count();
            $positiveFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)
                ->whereIn('rating', [4, 5])
                ->count();

            $positiveFeedbackPercentage = $totalFeedbackCount > 0
                ? round(($positiveFeedbackCount / $totalFeedbackCount) * 100, 1)
                : 0;

            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();
            $listingData = array_merge($listing->toArray(), $attributes);
            return response()->json([
                'status' => true,
                'message' => 'Listing fetched successfully',
                'data' => [
                    'listing' => $listingData,
                    'buying_offers' => $buyingOffers,
                    'selling_offers' => $sellingOffers,
                    'creator_feedback_percentage' => $positiveFeedbackPercentage,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $slug)
    {
        try {
            $listing = Listing::where('slug', $slug)->with('images')->first();

            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'sometimes|string',
                'condition' => 'required|in:new,used',
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
                    'data' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $listing->update($data);

            //  Sync attributes
            if (!empty($data['attributes'])) {
                $listing->attributes()->delete();
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create($attr);
                }
            }


            // ✅ Only process images if provided
            if ($request->hasFile('images')) {
                $directory = 'listings/images';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0775, true);
                }

                $existingCount = $listing->images->count();

                foreach ($request->file('images') as $index => $image) {
                    if (($existingCount + $index) >= 20)
                        break;

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $existingCount + $index
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing updated successfully',
                'data' => $listing->load('images', 'attributes')
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    //Deleting images 
    public function deleteImage($id)
    {
        try {
            $image = ListingImage::find($id);

            if (!$image) {
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
                'data' => $e->getMessage()
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
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching filters',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($slug)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->first();

        if (!$listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or unauthorized',
                'data' => null
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
                'is_active' => $listing->is_active
            ]
        ]);
    }

    public function destroy($slug)
    {
        try {
            $listing = Listing::where('slug', $slug)->first();

            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null
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
                'data' => null
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting listing',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function withdraw($slug)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [0, 1]) // Only withdraw if pending/approved
            ->first();

        if (!$listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be withdrawn',
                'data' => null
            ], 404);
        }

        $listing->status = 5; // Withdrawn
        $listing->save();

        // Optional: notify bidders, log event

        return response()->json([
            'status' => true,
            'message' => 'Listing withdrawn successfully',
            'data' => $listing
        ]);
    }

    public function relist($slug, Request $request)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [2, 4, 5]) // rejected, expired, withdrawn
            ->first();

        if (!$listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be re-listed',
                'data' => null
            ], 404);
        }

        $listing->status = 1;
        $listing->is_active = 1;
        $listing->expire_at = $request->input('expire_at', now()->addDays(7)); // optional
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing re-listed successfully',
            'data' => $listing
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
                'data' => $views
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing views',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function approve($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (!$listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed'
            ], 422);
        }

        $listing->status = 1;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing approved successfully'
        ]);
    }

    public function reject($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (!$listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed'
            ], 422);
        }

        $listing->status = 2;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing rejected successfully'
        ]);
    }
}

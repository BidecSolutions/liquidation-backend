<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $table = 'listings';

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'description',
        'listing_type',
        'condition',
        'start_price',
        'reserve_price',
        'buy_now_price',
        'allow_offers',
        'quantity',
        'authenticated_bidders_only',
        'pickup_option',
        'shipping_method_id',
        'payment_method_id',
        'color',
        'size',
        'brand',
        'style',
        'memory',
        'hard_drive_size',
        'cores',
        'storage',
        'category_id',
        'created_by',
        'meta_title',
        'meta_description',
        'is_featured',
        'status',
        'is_active',
        'expire_at',
        'sold_at',
        'note',
    ];

    protected $casts = [
        'allow_offers' => 'boolean',
        'authenticated_bidders_only' => 'boolean',
        'is_featured' => 'boolean',
        'expire_at' => 'datetime',
        'is_active' => 'integer', // ✅ was boolean before
        'sold_at' => 'datetime',
    ];

   

    protected $appends = [
        'winning_bid', 
        'buyer',
        'country_name',
        'region_name',
        'governorate_name',
        'city_name',
    ];

    // 🔗 Relationships

    public function countries()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function regions()
    {
        return $this->belongsTo(Regions::class, 'regions_id');
    }

    public function governorates()
    {
        return $this->belongsTo(Governorates::class, 'governorates_id');
    }

    public function cities()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    public function offers()
    {
        return $this->hasMany(ListingOffer::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class)->orderByDesc('id')->with(['user']);
    }

    public function views()
    {
        return $this->hasMany(ListingView::class);
    }

    public function reports()
    {
        return $this->hasMany(ListingReport::class);
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'watchlists');
    }

    public function winningBid()
    {
        return $this->hasOne(Bid::class)->orderByDesc('amount')->with(['user']);
    }

    public function winningOffer()
    {
        return $this->hasOne(ListingOffer::class)->orderByDesc('amount');
    }

    // public function getWinningBidAttribute()
    // {
    //     if ($this->expire_at && now()->lessThan($this->expire_at)) {
    //         return null; // hide while auction is active
    //     }

    //     return $this->bids()->with(['user'])->orderByDesc('amount')->first();
    // }

    public function getWinningBidAttribute()
    {
        if ($this->expire_at && now()->lessThan($this->expire_at)) {
            return null; // hide while auction is active
        }

        // Correct way to call the relationship
        return $this->winningBid()->with('user')->first();
    }

    public function buyNowPurchases()
    {
        return $this->hasMany(BuyNowPurchase::class);
    }

    public function getBuyerAttribute()
    {
        return $this->buyNowPurchases()->with('buyer')->latest()->first()?->buyer;
    }

    // Scope for filtering by type (jobs, motors, etc.)
    public function scopeByType($query, $type)
    {
        return $query->whereHas('listingType', fn ($q) => $q->where('code', $type));
    }

    public function attributes()
    {
        return $this->hasMany(ListingAttribute::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(UserFeedback::class, 'listing_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
     protected static function boot()
    {
        parent::boot();

        static::deleting(function ($listing) {
            $listing->images()->delete();
            $listing->offers()->delete();
            $listing->bids()->delete();
            $listing->views()->delete();
            $listing->reports()->delete();
            $listing->attributes()->delete();
            $listing->feedbacks()->delete();
            $listing->comments()->delete();
            $listing->buyNowPurchases()->delete();

            $listing->watchers()->detach();
        });
    }
}

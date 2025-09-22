<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Watchlist;
use App\Models\Listing;
use App\Models\Bid;
use App\Models\ListingOffer;
use App\Models\ListingReport;
use App\Notifications\ResetPasswordNotification;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'user_code',
        'first_name',
        'memberId',
        'is_verified',
        'verification_code',
        'verification_expires_at',
        'last_name',
        'username',
        'gender',
        'email',
        'phone',
        'occupation',
        'about_me',
        'favourite_quote',
        'date_of_birth',
        'landline',
        'account_type',
        'business_name',
        'country',
        'address_finder',
        'address_1',
        'address_2',
        'suburb',
        'post_code',
        'closest_district',
        'billing_address',
        'street_address',
        'apartment',
        'city',
        'state',
        'zip_code',
        'password',
        'profile_photo',
        'background_photo',
        'status',
    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function watchlist()
    {
        return $this->hasMany(Watchlist::class);
    }
    public function listings()
    {
        return $this->hasMany(Listing::class, 'created_by');
    }
    public function buyer_id()
    {
        return $this->hasMany(Appointment::class, 'buyer_id');
    }
    public function seller_id()
    {
        return $this->hasMany(Appointment::class, 'seller_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function offers()
    {
        return $this->hasMany(ListingOffer::class);
    }
    public function reports()
    {
        return $this->hasMany(ListingReport::class);
    }

    public function deliveryAddresses()
    {
        return $this->hasMany(\App\Models\DeliveryAddress::class);
    }

    public function favoriteCategories()
    {
        return $this->hasMany(FavoriteCategory::class);
    }

    public function favoriteSellers()
    {
        return $this->hasMany(FavoriteSeller::class);
    }

    public function getProfilePictureUrlAttribute()
{
    return $this->profile_picture
       ? asset('storage/profile_photos/' . $this->profile_photo)
        : asset('images/default-avatar.png');
}

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }



}

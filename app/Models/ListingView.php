<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingView extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'ip_address',
    ];

    

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }



    // other model methods/relationships...
}

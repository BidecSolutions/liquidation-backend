<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regions extends Model
{
    //
    use HasFactory;
    protected $table = 'regions';
    protected $fillable = ['name'];

    public function governorates()
    {
        return $this->hasMany(Governorates::class, 'region_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

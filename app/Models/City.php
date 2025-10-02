<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $table = 'cities';
    protected $fillable = ['governorate_id', 'name'];

    public function governorate()
    {
        return $this->belongsTo(Governorates::class);
    }
}
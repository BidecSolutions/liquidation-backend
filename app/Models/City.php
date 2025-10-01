<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['governorate_id', 'name'];

    public function governorate()
    {
        return $this->belongsTo(Governorates::class);
    }
}
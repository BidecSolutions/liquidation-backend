<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'keyword',
        'category_id',
        'category_path',
        'filters'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public static function booted()
    {
        static::saving(function ($model) {
            if (is_null($model->user_id) == is_null($model->guest_id)) {
                throw new Exception("Either user_id or guest_id must be set, but not both.");
            }
        });
    }
}

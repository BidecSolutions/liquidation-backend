<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCv extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'is_selected',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_profile_id',
        'name',
        'status',
    ];

    public function jobProfile()
    {
        return $this->belongsTo(JobProfile::class);
    }
}

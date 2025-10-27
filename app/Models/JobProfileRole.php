<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProfileRole extends Model
{
    protected $fillable = ['job_profile_id', 'role_name', 'status'];

    public function jobProfile()
    {
        return $this->belongsTo(JobProfile::class);
    }
}

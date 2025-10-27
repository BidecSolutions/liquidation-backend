<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'summary',
        'preferred_role',
        'open_to_all_roles',
        'industry_id',
        'preferred_locations',
        'right_to_work_in_saudi',
        'minimum_pay_type',
        'minimum_pay_amount',
        'notice_period',
        'work_type',
        'status',
    ];

    protected $appends = ['industry_name'];

    public function getIndustryNameAttribute()
    {
        return $this->industry?->name ?? null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function industry()
    {
        return $this->belongsTo(Category::class, 'industry_id');
    }

    public function preferredRoles()
    {
        return $this->hasMany(JobProfileRole::class);
    }

    public function skills()
    {
        return $this->hasMany(JobSkill::class);
    }
}

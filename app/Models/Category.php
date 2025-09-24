<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'category_type',
        // 'is_active',
        'order',    
        'status',
        'meta_title',
        'meta_description',
        'schema',
        'canonical_url',
        'focus_keywords',
        'redirect_301',
        'redirect_302',
        'icon',
        'image_path',
        'image_path_name',
        'image_path_alt_name',
        'created_by',
    ];

    // 🔁 Parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // 🔁 Subcategories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children', 'listings');
    }

    // 🔁 Recursive children (useful for menus)
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function listings()
    {
        return $this->hasMany(Listing::class, 'category_id')->with(['images','creator']);
    }
    public function parentRecursive()
    {
        return $this->parent()->with('parentRecursive:id,name,slug,parent_id');
    }

    public function allchildrenIds()
    {
        $ids = collect([$this->id]);
        foreach($this->children as $child) {
            $ids = $ids->merge($child->allchildrenIds());
        }
        return $ids;
    }

}

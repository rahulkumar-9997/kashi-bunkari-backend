<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IndustryCategory extends Model
{
    use HasFactory;
    protected $table = 'industry_categories';
    protected $fillable = [
        'title',
        'slug',
        'status',
    ];
    protected $casts = [
        'status' => 'boolean',
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->title);
            }
        });
        static::updating(function ($category) {
            if ($category->isDirty('title')) {
                $category->slug = Str::slug($category->title);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    public function getStatusTextAttribute()
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    public function industries()
    {
        return $this->hasMany(Industry::class, 'industry_category_id');
    }
}
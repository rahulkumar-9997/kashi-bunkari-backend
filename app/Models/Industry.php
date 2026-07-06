<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Industry extends Model
{
    protected $table = 'industries';
    protected $fillable = [
        'title',
        'slug',
        'sort_order',
        'meta_title',
        'meta_description',
        'short_description',
        'long_description',
        'status',
        'page_url',
        'image_file',
        'industry_category_id'
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($industry) {
            $industry->slug = self::generateUniqueSlug($industry->title);
        });
        static::updating(function ($industry) {
            if ($industry->isDirty('title')) {
                $industry->slug = self::generateUniqueSlug($industry->title, $industry->id);
            }
        });
    }

    public static function generateUniqueSlug($title, $id = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (
            self::where('slug', $slug)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
    
    public function products()
    {
        return $this->belongsToMany(Product::class, 'industry_product');
    }

    public function category()
    {
        return $this->belongsTo(IndustryCategory::class, 'industry_category_id');
    }

    
}
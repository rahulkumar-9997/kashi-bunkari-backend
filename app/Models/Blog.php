<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'blogs';
    protected $fillable = [
        'title',
        'reading_title',
        'slug',
        'short_desc',
        'content',
        'meta_title',
        'meta_description',
        'main_image',
        'page_image',
        'tags',
        'status',
        'view_count',
        'published_at'
    ];

    public function paragraphs()
    {
        return $this->hasMany(BlogParagraph::class)->orderBy('sort_order');
    }

    public function images()
    {
        return $this->hasMany(BlogImage::class)->orderBy('sort_order');
    }
}
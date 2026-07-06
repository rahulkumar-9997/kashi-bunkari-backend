<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogImage extends Model
{
    protected $table = 'blog_images';
    protected $fillable = [
        'blog_id',
        'image',
        'alt_text',
        'sort_order'
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
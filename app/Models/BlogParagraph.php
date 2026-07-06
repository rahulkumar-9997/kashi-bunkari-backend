<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogParagraph extends Model
{
    protected $table = 'blog_paragraphs';
    protected $fillable = [
        'blog_id',
        'title',
        'content',
        'image',
        'sort_order'
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
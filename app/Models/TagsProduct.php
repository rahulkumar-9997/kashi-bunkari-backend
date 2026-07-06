<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagsProduct extends Model
{
    protected $table = 'tags_product';

    protected $fillable = [
        'tag_id',
        'product_id',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

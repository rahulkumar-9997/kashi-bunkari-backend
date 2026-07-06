<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;
    protected $table = 'banners';
    protected $fillable = [
        'title',
        'content',
        'image_path_desktop',
        'image_path_mobile',
        'status',
        'collection_link',
        'buy_now_link'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'banner_product');
    }
}

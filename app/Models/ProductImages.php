<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImages extends Model
{
    use HasFactory;
    protected $table = 'product_images';
    protected $fillable = [
        'id',
        'product_id',
        'image_path',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getThumbImages()
    {
        return asset('storage/images/product/thumb/'.$this->image_path);
    }
    public function getSmallImages()
    {
        return asset('storage/images/product/small/'.$this->image_path);
    }
	public function getLargeImages()
    {
        return asset('storage/images/product/large/'.$this->image_path);
    }
}

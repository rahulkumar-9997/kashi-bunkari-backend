<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PrimaryCategory extends Model
{
    protected $table = 'primary_categories';
    protected $fillable = [
        'title',
        'short_heading',
        'slug',
        'additional_slug',
        'image_path',
        'link',
        'meta_title',
        'meta_description',
        'primary_category_description',
        'status'
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'primary_category_product')
        ->withTimestamps();
    }
}
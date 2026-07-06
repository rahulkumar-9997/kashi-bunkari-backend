<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDuplication extends Model
{
    use HasFactory;
    
    protected $table = 'product_duplications';    
    protected $fillable = [
        'id',
        'original_product_id',
        'new_product_id',
    ];
    
    public function originalProduct()
    {
        return $this->belongsTo(Product::class, 'original_product_id');
    }
    
    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }
    
   
}
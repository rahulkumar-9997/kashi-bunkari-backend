<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndustryProduct extends Model
{
    protected $table = 'industry_product';

    public $timestamps = false; 
    protected $fillable = [
        'industry_id',
        'product_id',
        'title',
        'sort_order'
    ];
}
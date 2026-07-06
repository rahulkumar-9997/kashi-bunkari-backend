<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class OrderStatus extends Model
{
    use HasFactory;    
    protected $table = 'order_statuses'; 
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'color',
        'sort_order'
    ];
    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }
}
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;    
    protected $table = 'order_addresses';    
    protected $fillable = [
        'customer_id',
        'type',
        'full_name',
        'phone_number',
        'email',
        'country',
        'address',
        'apartment',
        'city',
        'state',
        'pin_code'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function shippingOrders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    public function billingOrders()
    {
        return $this->hasMany(Order::class, 'billing_address_id');
    }
}
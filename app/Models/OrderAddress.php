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
        'alternate_phone',
        'email',
        'country',
        'address',
        'apartment',
        'city',
        'state',
        'pin_code',
        'locality',
        'landmark',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_address_id');
    }
}
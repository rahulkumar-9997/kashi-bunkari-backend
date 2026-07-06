<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;    
    protected $table = 'orders';    
    protected $fillable = [
        'order_number',
        'order_date',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'grand_total',
        'payment_mode',
        'payment_received',
        'payment_fail_reason',
        'customer_id',
        'shipping_address_id',
        'billing_address_id',
        'order_status_id',
        'order_cancel_reason',
        'coupon_code',
        'coupon_discount_amount',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'payment_received' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(OrderAddress::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(OrderAddress::class, 'billing_address_id');
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class, 'order_id');
    }
}
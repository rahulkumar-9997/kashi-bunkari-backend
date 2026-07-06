<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Address extends Model
{
    use HasFactory;    
    protected $table = 'addresses';    
    protected $fillable = [
        'customer_id',
        'name',
        'phone_number',
        'country',
        'address',
        'apartment',
        'city',
        'state',
        'zip_code',
        'is_default'
    ];
    protected $casts = [
        'is_default' => 'boolean',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
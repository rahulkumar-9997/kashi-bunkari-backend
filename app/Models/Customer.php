<?php
// app/Models/Customer.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;    
    protected $table = 'customers';    
    protected $fillable = [
        'name',
        'email',
        'customer_id',
        'password',
        'otp',
        'google_id',
        'profile_img',
        'phone_number',
        'status',
        'date_of_birth',
        'gender',
        'bio',
        'login_attempts',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'otp',
    ];
    
    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'login_attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function generateOtp()
    {
        $otp = rand(100000, 999999); 
        $this->otp = $otp;
        $this->save();
        cache()->put('otp_' . $this->id, $otp, now()->addMinutes(10));        
        return $otp;
    }

    public function verifyOtp($otp)
    {
        $cachedOtp = cache()->get('otp_' . $this->id);        
        if ($cachedOtp && $cachedOtp == $otp) {
            $this->otp = null;
            $this->save();
            cache()->forget('otp_' . $this->id);
            return true;
        }
        return false;
    }

    public static function generateCustomerId()
    {
        $prefix = 'WS';
        do {
            $number = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $customerId = $prefix . $number;
        } while (self::where('customer_id', $customerId)->exists());
        return $customerId;
    }

    public function isActive()
    {
        return $this->status == 1;
    }

    public function incrementLoginAttempts()
    {
        $this->increment('login_attempts');
    }

    public function resetLoginAttempts()
    {
        $this->update(['login_attempts' => 0]);
    }

    public function isLocked()
    {
        return $this->login_attempts >= 5;
    }

    public function getProfileImgAttribute($value)
    {
        if (!$value) return null;
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        return asset('storage/images/customer-profile/' . $value);
    }

   
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function orderAddresses()
    {
        return $this->hasMany(OrderAddress::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y');
    }

     public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_login_at) {
            return false;
        }
        $lastLogin = $this->last_login_at instanceof \Carbon\Carbon 
            ? $this->last_login_at 
            : \Carbon\Carbon::parse($this->last_login_at);
        
        return $lastLogin->diffInHours(now()) < 1;
    }
    public function getLastLoginFormattedAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }
        
        $lastLogin = $this->last_login_at instanceof \Carbon\Carbon 
            ? $this->last_login_at 
            : \Carbon\Carbon::parse($this->last_login_at);
        
        if ($lastLogin->diffInHours(now()) < 24) {
            return $lastLogin->diffForHumans();
        }
        
        return $lastLogin->format('d M Y H:i');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'customer_id');
    }
}
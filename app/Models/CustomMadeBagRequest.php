<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomMadeBagRequest extends Model
{
    use HasFactory;
    protected $table = 'custom_made_bag_request';
    protected $fillable = [
        'company_name',
        'name',
        'email',
        'phone',
        'request_for',
        'message',
        'attachment',
        'marketing',
        'status'
    ];
}

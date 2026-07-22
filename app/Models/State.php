<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class State extends Model
{
    use HasFactory;
    protected $table = 'states';
    protected $fillable = [
        'id',
        'code',
        'name',
    ];
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    use HasFactory;
    protected $fillable = [
        'from',
        'to',
        'follow',
        'subscribe',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteAddress extends Model
{
    protected $table = 'favorite_addresses';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'latitude',
        'longitude',
        'address',
    ];

    protected $appends = [];
}

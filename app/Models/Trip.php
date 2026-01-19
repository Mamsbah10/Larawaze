<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'start_lat', 'start_lng', 'end_lat', 'end_lng', 'mode', 'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

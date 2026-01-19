<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'latitude',
        'longitude',
        'address',
    ];

    protected $appends = ['name', 'lat', 'lon'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors to map DB columns to expected JSON keys
    public function getNameAttribute()
    {
        return $this->address ?? $this->query ?? 'Sans titre';
    }

    public function getLatAttribute()
    {
        return $this->latitude;
    }

    public function getLonAttribute()
    {
        return $this->longitude;
    }
}

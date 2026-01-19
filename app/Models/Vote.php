<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = ['event_id','user_id','type'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

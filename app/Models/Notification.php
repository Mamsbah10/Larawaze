<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * Les attributs assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'message',
        'user_id',
    ];
}

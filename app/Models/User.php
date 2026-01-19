<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name','email','password','role'];
    protected $hidden = ['password'];

public function events()
{
    return $this->hasMany(Event::class);
}

public function votes()
{
    return $this->hasMany(Vote::class);
}


}

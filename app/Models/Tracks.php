<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tracks extends Model 
{
    use SoftDeletes; 

    /**
     * 
     */
    public function User() {
        return $this->hasOne(User::class, 'id', 'users_id');
    }
}

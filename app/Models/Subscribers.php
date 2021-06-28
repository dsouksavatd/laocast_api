<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Subscribers extends Model 
{
    //use SoftDeletes; 
    /**
     * 
     */
    public function Channels() {
        return $this->hasOne(Channels::class, 'id', 'channels_id');
    }
    
}

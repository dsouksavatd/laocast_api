<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ads extends Model 
{
    use SoftDeletes; 

    /**
     * 
     */
    public function getImageAttribute($value) {
        return env('APP_URL').$value;
    }

    /**
     * 
     */
    public function getBannerAttribute($value) {
        return env('APP_URL').$value;
    }
}

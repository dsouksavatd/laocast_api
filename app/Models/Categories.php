<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model 
{   
    use SoftDeletes; 
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * 
     */
    public function getNameAttribute($value) {
        return trans('app.categories.'.$value);
    }

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Channels extends Model 
{
    use SoftDeletes; 
    
    /**
     * 
     */
    public function Tracks() {
        return $this->hasMany(Tracks::class, 'channels_id', 'id')
            ->where('publish', 1)
            ->orderBy('id', 'DESC')
            ->limit(5);
    }
}

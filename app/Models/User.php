<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        "email_verified_at",
        "two_factor_secret",
        "two_factor_recovery_codes",
        "remember_token",
        "current_team_id",
        "verify_code",
        "verify_code_expiry",
        "updated_at"
    ];

    /**
     * 
     */
    public function Notifications() {
        return $this->hasMany(Notifications::class, 'users_id', 'id')->where('read', 0);
    }

}

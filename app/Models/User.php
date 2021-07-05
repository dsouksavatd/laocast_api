<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Auth;

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

    /**
     * 
     */
    public function pushNotify($param, $sound = false) {

        // user should not notify for commen/subscrib their own data 
        if($this->id != Auth::id()) { 

            // add notification record
            $notify = new Notifications();
            $notify->users_id = $this->id;
            $notify->type = $param['type'];
            $notify->scheme = json_encode([
                'avatar' => $this->profile_photo_path,
                'description' => $param['body'],
                'target_id' => $param['target_id'],
                'tracks_id' => $param['tracks_id'],
                'track' => $param['track']
            ],JSON_UNESCAPED_SLASHES);
            $notify->save();
           
            // check if user enable notification
            if($this->notification) {
                $pushTokens = app('db')->select("
                    SELECT push_token
                    FROM oauth_access_tokens
                    WHERE user_id = ".$this->id."
                    AND revoked = 0
                    AND push_token != ''
                    GROUP BY push_token
                ");
                $expo = \ExponentPhpSDK\Expo::normalSetup();
                $channelName = env('APP_NAME').'_ExpoPushChannel';

                try {
                    if($pushTokens) {
                        foreach($pushTokens as $token) {
                            $expo->subscribe($channelName, $token->push_token);
                            $notification = [
                                'title' => $param['title'], 
                                'body' => $param['body'],
                            ];
                            if($sound) {
                                $notification = array_merge($notification, ['sound'=>'default']);
                            }
                        }
                        $expo->notify([$channelName], $notification);
                    }
                } catch(Exception $e) {
                    return 'Caught exception: '. $e->getMessage();
                } 
            }
        }
        
    }
}

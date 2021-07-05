<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use App\Models\OauthAccessTokens;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use URL;
use Illuminate\Support\Facades\Hash;
use App\Mail\MailTemplate;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Subscribers;
use App\Models\TrackViews;

class UserController extends Controller
{
    public static $CODE = 200;
    public static $MESSAGE = "success";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)  {
        app('translator')->setLocale($request->header('Content-Language'));
        $this->middleware('auth:api');
    }

    /**
     * User Get
     * @return JSON
     */
    public function profile() {
        $user = User::withCount('notifications')->find(Auth::id());
        return response()->json(
            $user
        ,self::$CODE);
    }

    /**
     * 
     */
    public function profileUpdate(Request $request) {

        if($request->isMethod('PATCH')) {

            /* Validate */
            $this->validate($request, [
                'name' => 'required|string',
                'mobile' => 'nullable|numeric|min:8'
            ]);

            $user = Auth::user();
            $user->name = $request->name;
            $user->mobile = $request->mobile;
            $user->save();
            
            self::$MESSAGE = trans('message.success.profile_saved');
            return response()->json([
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }

    /**
     * 
     */
    public function myAccount() {
        $user = Auth::user();
        return response()->json([
            'points' => $user->points,
            'isStaff' => $user->BranchStaff ? 1 : 0
        ], self::$CODE);
    }

    /**
     * 
     */
    public function saveNumber(Request $request) {
        
        if($request->isMethod('POST')) {

            $this->validate($request, [
                'number' => 'required|min:7|unique:numbers,number'
            ]);

            $number = new Numbers();
            $number->users_id = Auth::id();
            $number->number = $request->number;
            
            if(!$number->save()) {
                self::$CODE = 422;
                self::$MESSAGE = 'save_error';

                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            self::$MESSAGE = 'save_success';
            return response()->json([
                'message' => [ 0 => self::$MESSAGE ]
            ], self::$CODE);

        }
    }

    /**
     * 
     */
    public function removeProfilePicture(Request $request) {
        
        if($request->isMethod('DELETE')) {

            $user = Auth::user();
            //$user->profile_photo_path = env('USER_PICTURE_URL').'no-avatar.png';
            $user->profile_photo_path = 'https://images.laocast.com/no-avatar.png';
            $user->save();

            return response()->json([
                'profile_photo_path' => $user->profile_photo_path,
                'message' => self::$MESSAGE
            ], self::$CODE);

        }
    }

    /**
     * Profile Photo Upload
     */
    public function profilePictureUpload(Request $request) {

        if($request->isMethod('POST')) {

            $base64_image = $request->picture;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64_image)) {
            //if( $base64_image) {

                $data = substr($base64_image, strpos($base64_image, ',') + 1);
                $data = base64_decode($data);
                $photo_name = Str::uuid()->toString().'.png';

                $user = Auth::user();

                if(file_put_contents('./img/users/'.$photo_name, $data)) {
                    
                    $user->profile_photo_path = env('USER_PICTURE_URL').$photo_name;
                    $user->save();
                    self::$CODE = 200;
                    self::$MESSAGE = trans('messages.success.uploaded');

                } else {
                    self::$CODE = 422;
                    self::$MESSAGE = trans('messages.error.upload');
                }
                
            } else {
                self::$CODE = 422;
                self::$MESSAGE = trans('messages.error.upload');
            }

            return response()->json([
                'message' => self::$CODE == 200 ? self::$MESSAGE : [ 0 => self::$MESSAGE ],
                'profile_photo_path' => self::$CODE == 200 ? $user->profile_photo_path : false
            ], self::$CODE);
        }
    }

    /**
     * 
     */
    public function changePassword(Request $request) {

        if($request->isMethod("PATCH")) {

            $user = Auth::user();

            $this->validate($request, [
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
                'new_password_confirmation' => 'required_with:new_password'
            ]);

            /* check password */
            if( !Hash::check( $request->current_password, $user->password ) ) {
                self::$MESSAGE = trans('messages.errors.current_password');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            /* send password reset confirmation email */
            $subject = trans('app.email.password_changed.subject');
            $body = trans('app.email.password_changed.body', ['email' => $user->email]);
            Mail::to($user->email)->send(new MailTemplate($subject, [
                'name' => $user->name,
                'target' => "#",
                'action' => false,
                'body' => $body,
                'footer' => false
            ]));

            return response()->json([
                'message' => trans('messages.success.password_changed',['email' => $user->email]),
            ], self::$CODE);

        }
    }

    /**
     * 
     */
    function subscription() {
        $subscription = Subscribers::select(['id','users_id','channels_id'])
                            ->with([
                                'Channels:id,name,image',
                                'Channels.Tracks:id,name,views,favorites,channels_id,track,duration'
                            ])->where('users_id', Auth::id())->get();
        return response()->json([
            'subscription' => $subscription
        ], self::$CODE);
    }

    /**
     * 
     */
    function history() {
        $data = app('db')->select("
            SELECT 
            tracks.id as id,
            tracks.name as name,
            channels.image as image,
            channels.name as channel,
            tracks.track as track,
            tracks.duration as duration,
            tracks.views as views,
            tracks.favorites as favorites,
            track_views.created_at as created_at
            FROM track_views
            JOIN tracks ON tracks.id = track_views.tracks_id
            JOIN channels ON tracks.channels_id = channels.id
            WHERE track_views.users_id = ".Auth::id()."
            AND tracks.publish = 1
            AND channels.publish = 1
            AND tracks.deleted_at IS NULL
            ORDER BY track_views.created_at DESC
        ");
        return response()->json([
            'data' => $data
        ], self::$CODE);
    }

    /**
     * 
     */
    function favorite() {
        $data = app('db')->select("
            SELECT 
            tracks.id as id,
            tracks.name as name,
            channels.image as image,
            channels.name as channel,
            tracks.track as track,
            tracks.duration as duration,
            tracks.views as views,
            tracks.favorites as favorites,
            track_favorites.created_at as created_at
            FROM track_favorites
            JOIN tracks ON tracks.id = track_favorites.tracks_id
            JOIN channels ON tracks.channels_id = channels.id
            WHERE track_favorites.users_id = ".Auth::id()."
            AND tracks.publish = 1
            AND channels.publish = 1
            AND tracks.deleted_at IS NULL
            ORDER BY track_favorites.created_at DESC
        ");
        return response()->json([
            'data' => $data
        ], self::$CODE);
    }

    /**
     * 
     */
    public function notifications() {
        $data  = app('db')->select("
            SELECT
            notifications.id as id,
            notifications.type as type,
            notifications.scheme as scheme,
            notifications.read as `read`,
            notifications.created_at as created_at
            FROM notifications
            JOIN users ON users.id = notifications.users_id
            WHERE notifications.users_id = ".Auth::id()."
            ORDER BY notifications.created_at DESC
        ");

        $notifications = app('db')->select("
            SELECT
            COUNT(notifications.id) as notifications
            FROM notifications
            JOIN users ON users.id = notifications.users_id
            WHERE notifications.users_id = ".Auth::id()."
            AND notifications.read = 0"
        );

        return response()->json([
            'notifications' => $notifications[0]->notifications,
            'userNotification' => Auth::user()->notification,
            'data' => $data
        ], self::$CODE);
    }
    
    /**
     * 
     */
    public function notificationUpdate(Request $request) {
        if($request->isMethod('PATCH')) {

            /* Validate */
            $this->validate($request, [
                'notifications_id' => 'required|numeric',
            ]);

            /* update record */
            app('db')->select("
                UPDATE notifications
                SET `read` = 1
                WHERE notifications.users_id = ".Auth::id()."
                AND notifications.id = ".$request->notifications_id."
            ");

            /* count remain notifications */
            $notification = app('db')->select("
                SELECT 
                count(id) as count
                FROM notifications
                WHERE notifications.users_id = ".Auth::id()."
                AND notifications.`read` = 0
            ")[0]->count;

            return response()->json([
                'notifications' => $notification
            ], self::$CODE);
        }
    }

    /**
     * 
     */
    public function notificationMarkAsRead() {
        /* update all record */
        app('db')->select("
            UPDATE notifications
            SET `read` = 1
            WHERE notifications.users_id = ".Auth::id()."
        ");

        $data  = app('db')->select("
            SELECT
            notifications.id as id,
            notifications.type as type,
            notifications.scheme as scheme,
            notifications.read as `read`,
            notifications.created_at as created_at
            FROM notifications
            JOIN users ON users.id = notifications.users_id
            WHERE notifications.users_id = ".Auth::id()."
            ORDER BY notifications.created_at DESC
        ");

        return response()->json([
            'data' => $data,
            'notifications' => 0
        ], self::$CODE);
    }

    /**
     * 
     */
    public function notificationClear() {
        /* delete all records */
        app('db')->select("
            DELETE FROM notifications
            WHERE notifications.users_id = ".Auth::id()."
        ");

        return response()->json([
            'notifications' => 0
        ], self::$CODE);
    }

    /**
     * 
     */
    public function notificationOffon() {
        /* delete all records */
        $user = Auth::user();
        $user->notification = $user->notification == 1 ? 0 : 1;
        $user->save();

        return response()->json([
            'userNotification' => $user->notification
        ], self::$CODE);
    }
}

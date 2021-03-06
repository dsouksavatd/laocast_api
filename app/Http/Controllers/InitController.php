<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use App\Models\Ads;

class InitController extends Controller
{
    public static $CODE = 200;
    public static $MESSAGE = "success";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request) {

        app('translator')->setLocale($request->header('Content-Language'));
        $this->middleware('auth:api', [
            'except' => [
                'initialize'
            ]
        ]);
    }

    /**
     * 
     */
    public function initialize() {
        $notifications = 0;

        if(Auth::id()) {
            $notifications = app('db')->select("
                SELECT 
                count(id) as count
                FROM notifications
                WHERE notifications.read=0
                AND notifications.users_id = ".Auth::id()."
            ")[0]->count;
        }

        /** ADS */
        $adsData = Ads::select('link','type','image','banner')->get();
        $ads = $adsData->pluck('banner');
        $adsSet = $adsData;

        $data = [
            'facebook' => env('FACEBOOK'),
            'mobile' => env('MOBILE'),
            'notifications' => $notifications,
            'version' => env('APP_VERSION'),
            'updateLinkAndroid' => env('UPDATE_LINK_ANDROID'),
            'updateLinkiOS' => env('UPDATE_LINK_iOS'),
            'ads' => $ads,
            'adsSet' => $adsSet
        ];

        return response()->json([
            'data' => $data
        ], self::$CODE);
    }

}

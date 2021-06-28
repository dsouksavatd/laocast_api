<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Subscribers;
use App\Models\Channels;
use Auth;

class CasterController extends Controller
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
          
            ]
        ]);
    }
    
    /**
     * 
     */
    private function jsonResponse($params) {
        return response()->json($params,self::$CODE)
        ->header('Access-Control-Expose-Headers', 'cotent-length, server, x-total-count')
        ->header('X-Total-Count', 20);
    }

    /**
     * 
     */
    public function channels() {
        $channels = app('db')->select("
            SELECT * FROM channels WHERE users_id = ".Auth::id()."
        ");
        return self::jsonResponse($channels);
    }

    /**
     * 
     */
    public function tracks() {
        $tracks = app('db')->select("
            SELECT * FROM tracks WHERE users_id = ".Auth::id()."
            ORDER BY id DESC
        ");
        return self::jsonResponse($tracks);
    }

    /**
     * 
     */
    public function comments() {
        $comments = app('db')->select("
            SELECT * FROM track_comments ORDER BY id DESC
        ");
        return self::jsonResponse($comments);
    }


}

<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class SponsorController extends Controller
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
                'index',
                'sponsors'
            ]
        ]);
    }
    

    /**
     * 
     */
    public function index() {

        $data = app('db')->select("
            SELECT id, title, avatar, amount
            FROM sponsor_menus
            WHERE deleted_at IS NULL");
        
        return response()->json([
            'data' => $data,
        ],self::$CODE);
    }

     /**
     * 
     */
    public function sponsors($channels_id) {
        
        $data = app('db')->select("
            SELECT 
            sponsors.id as id,
            users.name as name,
            users.profile_photo_path as avatar,
            sponsor_menus.title as title,
            sponsors.amount as amount,
            sponsors.created_at as created_at
            FROM sponsors
            JOIN sponsor_menus ON sponsor_menus.id = sponsors.sponsor_menus_id
            JOIN users ON users.id = sponsors.users_id
            WHERE sponsors.channels_id = ".$channels_id."
            AND status=1
            ORDER BY sponsors.id DESC
            LIMIT 0,50
        ");

        $total = app('db')->select("
            SELECT count(id) as total FROM sponsors
            WHERE sponsors.channels_id = ".$channels_id."
            AND status=1
        ")[0]->total;
        
        return response()->json([
            'sponsors' => $data,
            'sponsorTotal' => $total
        ],self::$CODE);
    }
}

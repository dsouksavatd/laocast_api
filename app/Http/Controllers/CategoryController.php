<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Categories;

class CategoryController extends Controller
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
                'index'
            ]
        ]);
    }

    /**
     * 
     */
    public function index() {

        //$data = app('db')->select("SELECT id,name,image,count FROM categories WHERE deleted_at IS NULL");
        $data = Categories::select('id','name','image','count')->get();
        
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }
}

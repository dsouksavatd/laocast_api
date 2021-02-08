<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Subscribers;
use App\Models\Channels;
use Auth;

class ChannelController extends Controller
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
    public function subscribe(Request $request) {

        if($request->isMethod('PATCH')) {

            /* Validate */  
            $this->validate($request, [
                'channels_id' => 'required|numeric'
            ]);
            
            $channel = Channels::select('id','name','image','subscribers')
                            ->find($request->channels_id);

            $subscriber = Subscribers::where('channels_id', $request->channels_id)
                                    ->where('users_id', Auth::id())
                                    ->first();
            
            if($subscriber) {
                $subscriber->delete();
                self::$MESSAGE = 'unsubscribe';
                $channel->decrement('subscribers');
            } else {
                $subscriber = new Subscribers();
                $subscriber->channels_id = $request->channels_id;
                $subscriber->users_id = Auth::id();
                $subscriber->save();
                self::$MESSAGE = 'subscribed';
                $channel->increment('subscribers');
            }

            return response()->json([
                'subscribers' => $channel->subscribers,
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }

}

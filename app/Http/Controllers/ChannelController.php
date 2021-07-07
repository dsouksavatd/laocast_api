<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Subscribers;
use App\Models\Channels;
use Auth;
use App\Models\Tracks;

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
                'index',
                'popular',
                'subscribers',
                'recent',
                'findByShortenCode',
                'trackByChannelsId'
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
            
            $channel = Channels::select('id','name','image','subscribers','users_id')
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

                // Push Notify
                $title = trans('app.push_notification.subscribe.title');
                $body = trans('app.push_notification.subscribe.body', [
                    'name' => Auth::user()->name,
                    'channel' => $channel->name
                ]);

                // find track
                $track = Tracks::where('channels_id', $request->channels_id)->first();

                $channel->User->pushNotify([
                    'type' => 'subscribe',
                    'target_id' => $channel->id,
                    'title' => $title,
                    'body' => $body,
                    'tracks_id' => $track->id,
                    'track' => $track->track,
                    'cover' => $channel->image
                ], false);
            }

            return response()->json([
                'subscribers' => $channel->subscribers,
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }

    /**
     * 
     */
    public function subscribers($channels_id) {
        
        $data = app('db')->select("
            SELECT
            users.name as name,
            users.profile_photo_path as avatar,
            subscribers.created_at as created_at
            FROM subscribers
            JOIN users ON users.id = subscribers.users_id
            JOIN channels ON channels.id = subscribers.channels_id
            WHERE subscribers.channels_id = ".$channels_id."
            ORDER BY subscribers.id DESC
            LIMIT 0,30
        ");

        $total = app('db')->select("
            SELECT count(id) as total
            FROM subscribers
            WHERE subscribers.channels_id = ".$channels_id."
        ")[0]->total;

        return response()->json([
            'subscriberList' => $data,
            'subscriberTotal' => $total
        ], self::$CODE);
    }

    /**
     * 
     */
    public function popular($_offset, $_limit) {

        $data = app('db')->select("
            SELECT
            MIN(tracks.id) as id,
            channels.name as channel,
            channels.id as channels_id,
            channels.subscribers as subscribers,
            channels.image as image,
            MIN(tracks.name) as `name`,
            MIN(tracks.track) as track,
            MIN(tracks.duration) as duration,
            COUNT(tracks.id) as totalTracks
            FROM channels
            JOIN tracks ON tracks.channels_id = channels.id
            WHERE channels.publish = 1
            GROUP BY channels.id
            ORDER BY channels.subscribers DESC
            LIMIT ".$_offset.",".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function recent($_offset, $_limit) {

        $data = app('db')->select("
            SELECT
            MIN(tracks.id) as id,
            channels.id as channels_id,
            channels.name as channel,
            channels.subscribers as subscribers,
            channels.image as image,
            MIN(tracks.name) as `name`,
            MIN(tracks.track) as track,
            MIN(tracks.duration) as duration,
            COUNT(tracks.id) as totalTracks
            FROM channels
            JOIN tracks ON tracks.channels_id = channels.id
            WHERE channels.publish = 1
            GROUP BY channels.id
            ORDER BY channels.id DESC
            LIMIT ".$_offset.",".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function trackByChannelsId($channels_id) {

        $data = app('db')->select("
            SELECT
            MIN(tracks.id) as id,
            channels.name as channel,
            channels.subscribers as subscribers,
            channels.image as image,
            MIN(tracks.name) as `name`,
            MIN(tracks.track) as track,
            MIN(tracks.duration) as duration,
            COUNT(tracks.id) as totalTracks
            FROM channels
            JOIN tracks ON tracks.channels_id = channels.id
            WHERE channels.publish = 1
            AND channels.id = ".$channels_id."
            GROUP BY channels.id
            ORDER BY channels.id DESC
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function findByShortenCode($_shorten_code) {

        $data = app('db')->select("
            SELECT
            MIN(tracks.id) as id,
            MIN(tracks.track) as track,
            channels.name as channel,
            channels.image as image
            FROM channels
            JOIN tracks ON tracks.channels_id = channels.id
            WHERE channels.publish = 1
            AND channels.shorten_code = '".$_shorten_code."'
            GROUP BY channels.id
            ORDER BY channels.id DESC
        ");

        return response()->json([
            'data' => $data
        ],self::$CODE);
    }
}

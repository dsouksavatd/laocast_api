<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TrackComments;
use App\Models\Tracks;
use App\Models\Channels;
use App\Models\Subscribers;
use App\Models\TrackFavorites;
use App\Models\TrackViews;
use Auth;

class TrackController extends Controller
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
                'recent',
                'find',
                'findPublicComments',
                'subscription',
                'popular',
                'search',
                'category'
            ]
        ]);

        foreach(Tracks::get() as $track) {
            $track = Tracks::find($track->id);
            $track->shorten_code = '123123';
            $track->save();
        }
    }
    
    /**
     * 
     */
    public function recent($_offset, $_limit) {

        $data = app('db')->select("
            SELECT 
            tracks.id as id,
            channels.`name` as channel,
            channels.image as image,
            tracks.name as name,
            tracks.duration as duration,
            tracks.track as track,
            tracks.favorites as favorites,
            tracks.views as views
            FROM tracks
            JOIN channels ON channels.id = tracks.channels_id
            WHERE tracks.publish = 1 AND tracks.deleted_at IS NULL   
            ORDER BY id DESC
            LIMIT ".$_offset.",".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function subscription($_offset, $_limit) {

        $data = [];

        if(Auth::id()) {
            $data = app("db")->select("
                SELECT
                tracks.id as id,
                channels.name as channel,
                channels.image as image,
                tracks.name as name,
                tracks.duration as duration,
                tracks.views as views,
                tracks.duration as duration,
                tracks.favorites as favorites
                FROM tracks
                JOIN channels ON channels.id = tracks.channels_id
                WHERE tracks.channels_id IN (
                    SELECT channels_id FROM subscribers WHERE users_id = ".Auth::id()."
                )
                AND tracks.publish = 1
                AND tracks.deleted_at IS NULL
                ORDER BY tracks.created_at DESC
                LIMIT ".$_offset.",".$_limit."
            ");
        }
    
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function popular($_offset, $_limit) {

        $data = app('db')->select("
            SELECT
            tracks.id as id,
            channels.name as channel,
            channels.image as image,
            tracks.name as name,
            tracks.duration as duration,
            tracks.views as views,
            tracks.duration as duration,
            tracks.favorites as favorites,
            tracks.track as track
            FROM tracks
            JOIN channels ON channels.id = tracks.channels_id
            WHERE tracks.publish = 1
            AND tracks.deleted_at IS NULL
            ORDER BY tracks.views DESC
            LIMIT ".$_offset.", ".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function search($_keyword, $_offset, $_limit) {

        $data = app('db')->select("
            SELECT
            tracks.id as id,
            channels.name as channel,
            channels.image as image,
            tracks.name as name,
            tracks.duration as duration,
            tracks.views as views,
            tracks.duration as duration,
            tracks.favorites as favorites,
            tracks.track as track
            FROM tracks
            JOIN channels ON channels.id = tracks.channels_id
            WHERE tracks.publish = 1
            AND tracks.deleted_at IS NULL
            AND tracks.keywords LIKE '%".$_keyword."%'
            ORDER BY tracks.views DESC
            LIMIT ".$_offset.", ".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function find($trackId, $latitude, $longitude) {

        $track = app('db')->select("
            SELECT 
            tracks.id as id,
            tracks.name as name,
            tracks.duration as duration,
            tracks.track as track,
            tracks.channels_id as channels_id,
            tracks.favorites as favorites,
            tracks.views as views,
            tracks.comments as comments,
            tracks.users_id as users_id,
            tracks.created_at as created_at
            FROM tracks
            WHERE tracks.publish = 1 
            AND tracks.deleted_at IS NULL   
            AND tracks.id = ".$trackId."
        ")[0];

        $channel = app('db')->select("
            SELECT
            channels.id as id, 
            channels.`name` as name,
            channels.image as image,
            channels.subscribers as subscribers,
            channels.created_at as created_at,
            channels.shorten_url as shorten_url
            FROM channels
            WHERE channels.id = ".$track->channels_id."
            AND channels.deleted_at IS NULL
        ")[0];

        $playlist = app('db')->select("
            SELECT 
            tracks.id as id,
            channels.`name` as channel,
            channels.image as image,
            tracks.name as name,
            tracks.duration as duration,
            tracks.track as track,
            tracks.favorites as favorites,
            tracks.views as views,
            tracks.users_id as users_id,
            tracks.created_at as created_at
            FROM tracks
            JOIN channels ON channels.id = tracks.channels_id
            WHERE tracks.publish = 1 
            AND tracks.deleted_at IS NULL   
            AND channels.id = ".$track->channels_id."
        ");

        $user = User::select('name','profile_photo_path')->find($track->users_id);

        $comment = app('db')->select("
            SELECT 
            users.name as name,
            users.profile_photo_path as profile_photo_path,
            track_comments.comment as comment,
            track_comments.created_at as created_at
            FROM track_comments
            JOIN users ON users.id = track_comments.users_id
            WHERE track_comments.deleted_at IS NULL   
            AND track_comments.tracks_id = ".$trackId."
            ORDER BY track_comments.id DESC
            LIMIT 0,1
        ");
        
        $sponsors = app('db')->select("
            SELECT 
            sponsors.id as id,
            users.name as name,
            users.profile_photo_path as imageUrl,
            sponsor_menus.title as title,
            sponsors.amount as amount,
            sponsors.created_at as created_at
            FROM sponsors
            JOIN sponsor_menus ON sponsor_menus.id = sponsors.sponsor_menus_id
            JOIN users ON users.id = sponsors.users_id
            WHERE sponsors.channels_id = ".$track->channels_id."
            AND status=1
            ORDER BY sponsors.id DESC
            LIMIT 0,5
        ");

        if($comment) {
            $comment = [
                'avatar' => Auth::check() ? Auth::user()->profile_photo_path : "",
                'name' => $comment[0]->name,
                'profile_photo_path' => $comment[0]->profile_photo_path,
                'comment' => $comment[0]->comment,
                'date' => $comment[0]->created_at,
                'total' => $track->comments,
            ];
        } else {
            $comment = [
                'avatar' => Auth::check() ? Auth::user()->profile_photo_path : "",
                'name' => '',
                'profile_photo_path' => '',
                'comment' => 'no_comment',
                'date' => '',
                'total' => 0
            ];
        }
        
        /**
         * Update View and History
         */
        self::trackView($trackId, $latitude, $longitude);

        return response()->json([
            'track' => array_merge( (array) $track, [
                'favorite' => self::checkFavorite($trackId)
            ]),
            'channel' => array_merge( (array) $channel, [
                'subscribed' => self::checkSubscribe($track->channels_id)
            ]),
            'playlist' => [
                'total' => count($playlist),
                'data' => $playlist
            ],
            'comment' => $comment,
            'sponsors' => $sponsors,
        ],self::$CODE);
    }

    /**
     * 
     */
    private function trackView($trackId, $latitude, $longitude) {
        if(Auth::id()) {
            $view = TrackViews::where('tracks_id', $trackId)
                                ->whereDate('created_at', '=', date('Y-m-d'))
                                ->first();
            if($view) {
                // update track_views if user view same track in same day
                $view->updated_at = date('Y-m-d H:i:s');
                $view->update();

            } else {
                // update views for non-public
                $view = new TrackViews();
                $view->tracks_id = $trackId;
                $view->users_id = Auth::id();
                $view->latitude = $latitude;
                $view->longitude = $longitude;
                $view->save();
            }
            // update views
            $track = Tracks::find($trackId)->increment('views');
        } else {
            // update views for public view
            $track = Tracks::find($trackId);
            // public view update only views from differenct IP Address
            if($track->ip_address != $_SERVER['REMOTE_ADDR']) {
                $track->increment('views');
                $track->ip_address = $_SERVER['REMOTE_ADDR'];
                $track->save();
            }
        }
    }

    /**
     * 
     */
    private function checkSubscribe($channels_id) {
        if(Auth::id() && Subscribers::where('channels_id', $channels_id)->where('users_id', Auth::id())->first()) {
            return true;
        } else {
            return false;
        }
    }   

    /**
     * 
     */
    private function checkFavorite($tracks_id) {
        if(Auth::id() && TrackFavorites::where('tracks_id', $tracks_id)->where('users_id', Auth::id())->first()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function findComments($trackId) {

        $comments = app('db')->select("
            SELECT 
            track_comments.id as id,
            users.name as name,
            users.profile_photo_path as photo,
            track_comments.comment as comment,
            track_comments.users_id as users_id,
            track_comments.created_at as created_at
            FROM track_comments
            JOIN users ON users.id = track_comments.users_id
            WHERE track_comments.deleted_at IS NULL   
            AND track_comments.tracks_id = ".$trackId."
            ORDER BY id DESC
            LIMIT 0,30
        ");

        return response()->json([
            'profile_photo_path' => Auth::user()->profile_photo_path,
            'data' => $comments
        ],self::$CODE);
    }

    /**
     * 
     */
    public function findPublicComments($trackId) {

        $comments = app('db')->select("
            SELECT 
            track_comments.id as id,
            users.name as name,
            users.profile_photo_path as photo,
            track_comments.comment as comment,
            track_comments.users_id as users_id,
            track_comments.created_at as created_at
            FROM track_comments
            JOIN users ON users.id = track_comments.users_id
            WHERE track_comments.deleted_at IS NULL   
            AND track_comments.tracks_id = ".$trackId."
            ORDER BY id DESC
            LIMIT 0,30
        ");

        return response()->json([
            'profile_photo_path' => '',
            'data' => $comments
        ],self::$CODE);
    }

    /**
     * 
     */
    public function category($_id, $_offset, $_limit) {

        $data = app('db')->select("
            SELECT
            tracks.id as id,
            channels.name as channel,
            channels.image as image,
            tracks.name as name,
            tracks.duration as duration,
            tracks.views as views,
            tracks.duration as duration,
            tracks.favorites as favorites
            FROM tracks
            JOIN channels ON channels.id = tracks.channels_id
            WHERE tracks.categories_id = ".$_id."
            AND tracks.publish = 1
            AND tracks.deleted_at IS NULL
            ORDER BY tracks.id DESC
            LIMIT ".$_offset.", ".$_limit."
        ");
        return response()->json([
            'data' => $data
        ],self::$CODE);
    }

    /**
     * 
     */
    public function postComment(Request $request) {

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'tracks_id' => 'required|numeric',
                'comment' => 'required',
            ]);

            $comment = new TrackComments();
            $comment->comment = $request->comment;
            $comment->tracks_id = $request->tracks_id;
            $comment->users_id = Auth::id();
            $comment->save();

            /* user */
            $user = Auth::user();

            /* update track comment */
            $track = Tracks::find($request->tracks_id);
            $track->increment('comments');

            // Push Notify
           /*  $title = trans('app.push_notification.comment.title');
            $body = trans('app.push_notification.comment.body', [
                'name' => Auth::user()->name,
                'track' => $track->name
            ]);
            $track->User->pushNotify([
                'type' => 'comment',
                'target_id' => $track->id,
                'title' => $title,
                'body' => $body
            ], true); */

            return response()->json([
                'message' => 'comment has been saved',
                'comment' => [
                    'name' => $user->name,
                    'avatar' => Auth::check() ? Auth::user()->profile_photo_path : "",
                    'profile_photo_path' => $user->profile_photo_path,
                    'comment' => $comment->comment,
                    'date' => $comment->created_at,
                    'total' => $track->comments
                ]
            ],self::$CODE);
            
        }
    }

    /**
     * 
     */
    public function favorite(Request $request) {

        if($request->isMethod('PATCH')) {

            /* Validate */  
            $this->validate($request, [
                'tracks_id' => 'required|numeric'
            ]);
            
            $track = Tracks::select('id','favorites','users_id', 'name')->find($request->tracks_id);
            
            $track_favorite = TrackFavorites::where('tracks_id', $request->tracks_id)
                                    ->where('users_id', Auth::id())
                                    ->first();
            
            if($track_favorite) {
                $track_favorite->delete();
                self::$MESSAGE = 'unfavorite';
                $favorite = false;
                $track->decrement('favorites');
            } else {
                $track_favorite = new TrackFavorites();
                $track_favorite->tracks_id = $request->tracks_id;
                $track_favorite->users_id = Auth::id();
                $track_favorite->save();
                self::$MESSAGE = 'favorited';
                $favorite = true;
                $track->increment('favorites');

                // Push Notify
                /* $title = trans('app.push_notification.favorite.title');
                $body = trans('app.push_notification.favorite.body', [
                    'name' => Auth::user()->name,
                    'track' => $track->name
                ]);
                $track->User->pushNotify([
                    'type' => 'favorite',
                    'target_id' => $track->id,
                    'title' => $title,
                    'body' => $body
                ], false); */

            }

            return response()->json([
                'favorites' => $track->favorites,
                'favorite' => $favorite,
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }
}

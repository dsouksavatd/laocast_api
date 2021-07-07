<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/**
 * Initialize
 */
$router->get('/initialize', ['uses' => 'InitController@initialize']);
$router->get('/categories', ['uses' => 'CategoryController@index']);

/**
 * Authentication
 */
$router->post('/signup', ['uses' => 'AuthController@signup']);
$router->post('/signin', ['uses' => 'AuthController@signin']);
$router->post('/signout', ['uses' => 'AuthController@signout']);
$router->options('/signin', ['uses' => 'AuthController@signin']);
$router->post('/signin/social', ['uses' => 'AuthController@signinSocial']);
$router->post('/reset-password', ['uses' => 'AuthController@resetPassword']);
$router->post('/password-reset', ['uses' => 'AuthController@passwordReset']);
$router->post('/email-verification-code/resend', ['uses' => 'AuthController@resendCode']);
$router->patch('/email-verification', ['uses' => 'AuthController@emailVerification']);

/**
 * Deep Linking
 */
$router->get('/channel/find/{_shorten_code}', ['uses' => 'ChannelController@findByShortenCode']);
$router->get('/track/find/code/{_shorten_code}', ['uses' => 'TrackController@findByShortenCode']);


/**
 * Tracks
 */
$router->get('/track/recent/{_offset}/{_limit}', ['uses' => 'TrackController@recent']);
$router->get('/track/subscription/{_offset}/{_limit}', ['uses' => 'TrackController@subscription']);
$router->get('/track/popular/{_offset}/{_limit}', ['uses' => 'TrackController@popular']);
$router->get('/track/find/{trackId}/{latitude}/{longitude}', ['uses' => 'TrackController@find']);
$router->get('/track/public/comments/find/{trackId}', ['uses' => 'TrackController@findPublicComments']);
$router->get('/track/comments/find/{trackId}', ['uses' => 'TrackController@findComments']);
$router->post('/track/comment/post', ['uses' => 'TrackController@postComment']);
$router->patch('/track/favorite', ['uses' => 'TrackController@favorite']);
$router->get('/track/search', ['uses' => 'TrackController@search']);
$router->get('/track/category/{_id}/{_offset}/{_limit}', ['uses' => 'TrackController@category']);


/**
 * Channels
 */
$router->patch('/channel/subscribe', ['uses' => 'ChannelController@subscribe']);
$router->get('/subscribers/{channels_id}', ['uses' => 'ChannelController@subscribers']);
$router->get('/channel/popular/{_offset}/{_limit}', ['uses' => 'ChannelController@popular']);
$router->get('/channel/recent/{_offset}/{_limit}', ['uses' => 'ChannelController@recent']);
$router->get('/channel/tracks/{_channels_id}', ['uses' => 'ChannelController@recent']);

/**
 * User
 */
$router->get('/profile', ['uses' => 'UserController@profile']);
$router->post('/profile-picture-upload', ['uses' => 'UserController@profilePictureUpload']);
$router->delete('/profile-picture-remove', ['uses' => 'UserController@removeProfilePicture']);
$router->patch('/profile-update', ['uses' => 'UserController@profileUpdate']);
$router->patch('/change-password', ['uses' => 'UserController@changePassword']);
$router->get('/subscription', ['uses' => 'UserController@subscription']);
$router->get('/history', ['uses' => 'UserController@history']);
$router->get('/favorite', ['uses' => 'UserController@favorite']);
$router->get('/notifications', ['uses' => 'UserController@notifications']);
$router->patch('/notification-update', ['uses' => 'UserController@notificationUpdate']);
$router->patch('/notification/mark-as-read', ['uses' => 'UserController@notificationMarkAsRead']);
$router->delete('/notification/clear', ['uses' => 'UserController@notificationClear']);
$router->patch('/notification/offon', ['uses' => 'UserController@notificationOffon']);
$router->get('/notification/count', ['uses' => 'UserController@notificationCount']);

/**
 * Sponsor
 */
$router->get('/sponsors/{channels_id}', ['uses' => 'SponsorController@sponsors']);
$router->get('/sponsor/menu', ['uses' => 'SponsorController@index']);

/**
 * Payment
 */
$router->post('/op-gen', ['uses' => 'PaymentController@generateOnePayQrCode']);
$router->patch('/sponsor/onepay/check', ['uses' => 'PaymentController@onepayCheck']);

/**
 * Caster
 */
$router->options('/caster/channels', function(){ return response('', 200); });

$router->get('/caster/channels', ['uses' => 'CasterController@channels']);

$router->options('/caster/tracks', function(){ return response('', 200); });
$router->get('/caster/tracks', ['uses' => 'CasterController@tracks']);

$router->options('/caster/comments', function(){ return response('', 200); });
$router->get('/caster/comments', ['uses' => 'CasterController@comments']);
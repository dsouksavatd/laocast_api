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
$router->get('/categories', ['uses' => 'CategoryController@index']);

/**
 * Authentication
 */
$router->post('/signup', ['uses' => 'AuthController@signup']);
$router->post('/signin', ['uses' => 'AuthController@signin']);
$router->post('/reset-password', ['uses' => 'AuthController@resetPassword']);
$router->post('/password-reset', ['uses' => 'AuthController@passwordReset']);
$router->post('/email-verification-code/resend', ['uses' => 'AuthController@resendCode']);
$router->patch('/email-verification', ['uses' => 'AuthController@emailVerification']);

/**
 * Tracks
 */
$router->get('/track/recent', ['uses' => 'TrackController@recent']);
$router->get('/track/find/{trackId}/{latitude}/{longitude}', ['uses' => 'TrackController@find']);
$router->get('/track/public/comments/find/{trackId}', ['uses' => 'TrackController@findPublicComments']);
$router->get('/track/comments/find/{trackId}', ['uses' => 'TrackController@findComments']);
$router->post('/track/comment/post', ['uses' => 'TrackController@postComment']);
$router->patch('/track/favorite', ['uses' => 'TrackController@favorite']);

/**
 * Channels
 */
$router->patch('/channel/subscribe', ['uses' => 'ChannelController@subscribe']);
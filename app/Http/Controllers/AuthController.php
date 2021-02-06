<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\Models\OauthAccessTokens;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Client;
use Exception;
use Validator;
use App\Models\PasswordResets;

class AuthController extends Controller
{
    public static $CODE = 200;
    public static $MESSAGE = "success";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        app('translator')->setLocale($request->header('Content-Language'));

        $this->middleware('auth', [
            'except' => [
                'signup',
                'signin',
                'signinSocial',
                'resetPassword',
                'passwordReset'
            ]
        ]);
    }

    /**
     * Core Authentication
     */
    private function coreAuth($email, $password, $device_brand, $push_token, $manufacturer, $model_name, $device_year, $os_name, $os_version, $os_build_id, $device_name, $method, $latitude, $longitude) {

        $API_KEY = Str::uuid();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => env("CORE_URL")."oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => array(
                'client_id' => env("PASSPORT_CLIENT_ID"),
                'client_secret' => env("PASSPORT_CLIENT_SECRET"),
                'grant_type' => 'password',
                'username' => $email,
                'password' => $password,
                'scope' => '*',
                'api_key' => $API_KEY,
                'device_brand' => $device_brand,
                'push_token' => $push_token,
                'manufacturer' => $manufacturer,
                'model_name' => $model_name,
                'device_year' => $device_year,
                'os_name' => $os_name,
                'os_version' => $os_version,
                'os_build_id' => $os_build_id,
                'device_name' => $device_name,
                'method' => $method,
                'latitude' => $latitude,
                'longitude' => $longitude
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return ['response' => $response, 'API_KEY' => $API_KEY];
    }

    /**
     * Native Sign in
     */
    public function signin(Request $request) {

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);
            
            $user = User::where('email', $request->email)->first();
            
            /* check password */
            if( !$user || !Hash::check( $request->password, $user->password ) ) {
                self::$MESSAGE = trans('messages.errors.signin');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }
            
            /* Core Auth */
            $coreAuth = self::coreAuth(
                $request->email, 
                $request->password, 
                $request->device_brand,
                $request->push_token,
                $request->manufacturer,
                $request->model_name,
                $request->device_year,
                $request->os_name,
                $request->os_version,
                $request->os_build_id,
                $request->device_name,
                $request->method,
                $request->latitude,
                $request->longitude
            );
            
            /* check verification */
            if(!$user->email_verified_at) {

                $code = mt_rand(100000, 999999);
                $data = [
                    'name' => $user->name,
                    'code' => $code
                ];

                $user->verify_code = $code;
                $user->verify_code_expiry = date('Y-m-d H:i:s', time()+86400);
                $user->save();

                /* send verification email */
               /*  $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
                $beautymail->send('email.verification', $data, function($message) use ($request) {
                    $message
                        ->from(env('NO_REPLY_EMAIL'))
                        ->to($request->email, $request->name)
                        ->subject(trans('email.email_verfication'));
                }); */
            }

            return response()->json([
                'API_KEY' => $coreAuth['API_KEY'],
                'EMAIL_VERIFICATION' => $user->email_verified_at ? 'YES' : 'NO',
                'return' => $coreAuth['response'],
            ], self::$CODE);

        }
    }

    /**
     * Native Sign up
     */
    public function signup(Request $request) {

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required_with:password'
            ]);
            
            $user = new User();
            $user->name = $request->name;
            $user->password = Hash::make($request->password);
            $user->email = $request->email;
           
            $code = mt_rand(100000, 999999);
            $data = [
                'name' => $request->name,
                'code' => $code
            ];

            $user->verify_code = $code;
            $user->verify_code_expiry = date('Y-m-d H:i:s', time()+86400);

            if(!$user->save()) {
                self::$MESSAGE = trans('messages.errors.signup');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            /* Core Auth */
            $coreAuth = self::coreAuth(
                $request->email, 
                $request->password, 
                $request->push_token, 
                $request->login_method,
                $request->platform,
                $request->version
            );

            /* send verification email */
            /* $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $beautymail->send('email.verification', $data, function($message) use ($request) {
                $message
                    ->from(env('NO_REPLY_EMAIL'))
                    ->to($request->email, $request->name)
                    ->subject(trans('email.email_verfication'));
            }); */

            return response()->json([
                'API_KEY' => $coreAuth['API_KEY'],
                'EMAIL_VERIFICATION' => 'NO',
                'return' => $coreAuth['response'],
            ], self::$CODE);

        }
    }

    /**
     * Email Verification
     */
    public function verification(Request $request) {

        app('translator')->setLocale($request->header('Content-Language'));

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'email' => 'required|exists:users',
                'verification_code' => 'required|min:6|numeric'
            ]);
            
            $user = User::where('email',$request->email)->first();
                
            /* Check Verify Code and expiry time */
            if($user->verify_code != $request->verification_code || strtotime($user->verify_code_expiry) < time() ) {
                self::$MESSAGE = trans('messages.errors.invalid_verification_code');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->verify_code_expiry = date('Y-m-d H:i:s');

            if(!$user->save()) {
                self::$MESSAGE = trans('messages.errors.verification');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            self::$MESSAGE = trans('messages.success.verification_success');
            return response()->json([
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }

    /**
     * Social Sign in
     */
    public function signinSocial(Request $request) {

        $INVALID_EMAIL_PASSWORD = "invalid_email_and_password";

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'email' => 'required',
                'password' => 'required'
            ]);
            
            $user = User::where('email', $request->email)->first();

            /* Check if user existed */
            if(!$user) {
                $user = new User();
                $user->email = $request->email;
                $user->name = $request->name;
                $user->password = Hash::make($request->password);
                $user->save();
            }

            /* Update password if existed */
            $user->password = Hash::make($request->password);
            $user->email_verified_at = date("Y-m-d H:i:s");
            $user->save();

            /* Core Auth */
            $coreAuth = self::coreAuth(
                $request->email, 
                $request->password, 
                $request->push_token, 
                $request->login_method,
                $request->platform,
                $request->version
            );
            
            return response()->json([
                'API_KEY' => $coreAuth['API_KEY'],
                'EMAIL_VERIFICATION' => 'YES',
                'return' => $coreAuth['response']
            ], self::$CODE);
        }
    }

    /**
     * Resend Email Verification Code
     */
    public function resendCode(Request $request) {

        app('translator')->setLocale($request->header('Content-Language'));

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'email' => 'required|exists:users',
            ]);
            
            $user = User::where('email', $request->email)->first();
           
            $code = mt_rand(100000, 999999);
            $data = [
                'name' => $user->name,
                'code' => $code
            ];

            $user->verify_code = $code;
            $user->verify_code_expiry = date('Y-m-d H:i:s', time()+86400);
            $user->save();

            /* send verification email */
            $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $beautymail->send('email.verification', $data, function($message) use ($user) {
                $message
                    ->from(env('NO_REPLY_EMAIL'))
                    ->to($user->email, $user->name)
                    ->subject(trans('email.email_verfication'));
            });

            return response()->json([
                'message' => trans('messages.success.verification_code_sent',['email' => $request->email]),
            ], self::$CODE);
        }
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request) {

        if($request->isMethod('POST')) {

            /* Validate */
            $this->validate($request, [
                'email' => 'required|exists:users',
            ]);
           
            $user = User::where('email', $request->email)->first();

            $code = mt_rand(100000, 999999);
            $data = [
                'name' => $user->name,
                'code' => $code
            ];

            PasswordResets::where('email', $request->email)->delete();

            $passwordResets = new PasswordResets();
            $passwordResets->token = $code;
            $passwordResets->email = $request->email;
            $passwordResets->save();

            /* send reset code email */
            /* $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $beautymail->send('email.password_reset', $data, function($message) use ($user) {
                $message
                    ->from(env('NO_REPLY_EMAIL'))
                    ->to($user->email, $user->name)
                    ->subject(trans('email.reset_password'));
            }); */

            return response()->json([
                'message' => trans('messages.success.password_reset_code_sent',['email' => $request->email]),
            ], self::$CODE);
        }
    }

    /**
     * Password Reset
     */
    public function passwordReset(Request $request) {

        app('translator')->setLocale($request->header('Content-Language'));

        if($request->isMethod('POST')) {

            $passwordReset = PasswordResets::where('token', $request->token)
                                    ->where('email', $request->email)
                                    ->first();

            if(!$passwordReset) {
                self::$MESSAGE = trans('messages.errors.password_reset_code');
                self::$CODE = 422;
                return response()->json([
                    'message' => [ 0 => self::$MESSAGE ]
                ], self::$CODE);
            }

            /* Validate */
            $this->validate($request, [
                'email' => 'required|exists:users',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required_with:password'
            ]);

            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            $data = [
                'name' => $user->name,
                'email' => $request->email
            ];

            PasswordResets::where('email', $request->email)->delete();

            /* send reset code email */
            /* $beautymail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $beautymail->send('email.password_changed', $data, function($message) use ($user) {
                $message
                    ->from(env('NO_REPLY_EMAIL'))
                    ->to($user->email, $user->name)
                    ->subject(trans('email.password_changed'));
            }); */

            return response()->json([
                'message' => trans('messages.success.password_changed',['email' => $request->email]),
            ], self::$CODE);
        }
    }

    /**
     * Sign out
     */
    public function signout(Request $request) {

        if($request->isMethod('POST')) {

            $authorization = explode(' ', $request->header('Authorization'));
            $api_key = $authorization[1];
            $OauthAccessToken = OauthAccessTokens::where('api_key', $api_key)->first();
            $OauthAccessToken->revoked = 1;
            $OauthAccessToken->expires_at = date('Y-m-d H:i:s', time());
            $OauthAccessToken->save();

            return response()->json([
                'message' => self::$MESSAGE
            ], self::$CODE);
        }
    }

}

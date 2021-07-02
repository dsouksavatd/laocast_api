<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\OauthAccessTokens;
use Auth;
use App\Models\Sponsors;

class PaymentController extends Controller
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
    function buildqr($arr) {
        $res = "";
       /*  foreach ($arr as $key => $val){
            if (!$val) continue;
            $res .= str_pad($key, 2, "0", STR_PAD_LEFT) .
            str_pad(strlen($val), 2, "0", STR_PAD_LEFT) .
            $val;
        } */
        foreach($arr as $key => $val) {
            if(!$val) continue;
            $res.= $key.str_pad(strlen($val),2,"0",STR_PAD_LEFT).$val;
        }
        return $res; 
    }

    /**
     * 
     */
    function crc16($sStr, $aParams = array()){
        $aDefaults = array(
            "polynome" => 0x1021,
            "init" => 0xFFFF,
            "xor_out" => 0,
        );
        foreach ($aDefaults as $key => $val){
            if (!isset($aParams[$key])) {
                $aParams[$key] = $val;
            }
        }
        $sStr.= "";
        $crc = $aParams['init'];
        $len = strlen($sStr);
        $i = 0;
        while ($len--) {
            $crc ^= ord($sStr[$i++]) << 8;
            $crc &= 0xffff;
            for ($j = 0; $j < 8; $j++){
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ $aParams['polynome'] :
                $crc << 1;
                $crc &= 0xffff;
            }
        }
        $crc^= $aParams['xor_out'];
        return str_pad(strtoupper(dechex($crc)), 4, "0", STR_PAD_LEFT);
    }

    /**
     * 
     */
    public function generateOnePayQrCode(Request $request) {

        if($request->isMethod('POST')) {

            // Bank provide these data
            $mcid = env("BCEL_MCID");
            $mcc = env("BCEL_MCC");
            $ccy = env("BCEL_CCY");
            $country = env("BCEL_COUNTRY");
            $province = env("BCEL_PROVINCE");

            // You set these data
            $amount = env("BCEL_DEBUG") ? 1 : $request->amount;
            $channels_id = $request->channels_id;
            $uuid = 'uuid-' . $mcid . "-" . (string) Str::uuid();

            $rawqr = self::buildqr([
                "00" => "01",
                "01" => "12",
                "33" => self::buildqr([
                    "00" => "BCEL",
                    "01" => "ONEPAY",
                    "02" => $mcid
                ]),
                "52" => $mcc,
                "53" => $ccy,
                "54" => $amount,
                "58" => $country,
                "60" => $province,
                "62" => self::buildqr([
                    "01" => $channels_id,
                    "05" => $uuid,
                    "07" => null,
                    "08" => null
                ])
            ]);

            $fullqr = $rawqr . self::buildqr([63 => self::crc16($rawqr . "6304")]);

            /* save data */
            $sponsor = new Sponsors();
            $sponsor->users_id = Auth::id();
            $sponsor->uuid = $uuid;
            $sponsor->sponsor_menus_id = $request->sponsor_menus_id;
            $sponsor->amount = $request->amount;
            $sponsor->channels_id = $request->channels_id;
            $sponsor->tracks_id = $request->tracks_id;
            $sponsor->fullqr = $fullqr;
            $sponsor->save();

            return response()->json([
                'uuid' => $uuid,
                'message' => self::$MESSAGE,
                'fullqr' => $fullqr
            ],self::$CODE);
        }
    }

    /**
     * 
     */
    public function onepayCheck(Request $request) {

        if($request->isMethod('PATCH')) {

            $curl = curl_init();
            $mcid = env('BCEL_MCID');
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://bcel.la:8083/onepay/gettransaction.php?mcid=".$mcid."&uuid=".$request->uuid,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));
    
            $response = curl_exec($curl);
            curl_close($curl);
    
            if(!$response) {
                self::$MESSAGE = trans('app.messages.error.onepay');
                self::$CODE = 422;
                return response()->json([
                    'uuid' => $uuid,
                    'message' => self::$MESSAGE,
                    'paymentSuccess' => false
                ],self::$CODE); 
            }
    
            //$notify = (boolean) $_GET['notify'];
    
            // Send push notification
            /* if($notify) {
                
                $pushTokens = OauthAccessTokens::where('push_token' ,'!=', NULL)
                                            ->where('user_id', Auth::id())
                                            ->where('revoked', 0)
                                            ->get();
    
                $expo = \ExponentPhpSDK\Expo::normalSetup();
                $channelName = 'PaymentNotificationChannel';
    
                foreach($pushTokens as $token) {
                    $expo->subscribe($channelName, $token->push_token);
                    $notification = [
                        'title' => "Payment Verification", 
                        'body' =>'Your payment is under verifying'
                    ];
                }
                $expo->notify([$channelName], $notification);
            } */
    
            /* Update Payment reference */
            $sponsor = Sponsors::where('uuid', $request->uuid)->first();
            $sponsor->status = 1;
            $sponsor->save();

            /* $orderBase = MshopOrderBase::find($baseOrderId);
            $orderBase->customerref = "{uuid:".$uuid."}";
            $orderBase->save(); */
            
            self::$MESSAGE = 'payment successful';
            return response()->json([
                'uuid' => $uuid,
                'message' => self::$MESSAGE,
                'paymentSuccess' => true
            ],self::$CODE); 
    
        }
       
    }
}

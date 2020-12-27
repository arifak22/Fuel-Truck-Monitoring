<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use Request;
use Sideveloper;
use DB;
use JWTAuth;
class AuthController extends Controller
{
    public function postGenerate(){
        $credentials = Request::only('username', 'password');
        if (Auth::attempt($credentials)) {
            // Authentication passed...
            $res['api_status']  = 1;
            $res['api_message'] = 'Token Berhasil di Generate';
            // $token = JWTAuth::attempt($credentials);
            $token = JWTAuth::customClaims(['device' => 'api'])->fromUser(Auth::user());
            $res['jwt_token']   = $token;
        }else{
            $res['api_status']  = 0;
            $res['api_message'] = 'Username & Password tidak sesuai. Coba Lagi.';
            $res['jwt_token']   = null;
        }
        return response()->json($res);

    }
}
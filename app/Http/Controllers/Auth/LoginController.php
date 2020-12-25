<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;
use Request;
use Sideveloper;
use DB;
use JWTAuth;
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * CHANGE HERE
     */
    public function getIndex(){
        return view('login');
    }

    public function postAuth(){
        $credentials = Request::only('username', 'password');

        if (Auth::attempt($credentials)) {
            // Authentication passed...
            $res['api_status']  = 1;
            $res['api_message'] = 'Berhasil Login';
            // $token = JWTAuth::attempt($credentials);
            $token = JWTAuth::customClaims(['device' => 'web'])->fromUser(Auth::user());
            Request::session()->put('menus', Sideveloper::getMenu(Auth::user()->id_privilege));
            Request::session()->put('access', Sideveloper::getAccess(Auth::user()->id_privilege));

            $res['jwt_token']   = $token;
        }else{
            $res['api_status']  = 0;
            $res['api_message'] = 'Username & Password tidak sesuai. Coba Lagi.';
            $res['jwt_token']   = null;
        }
        return response()->json($res);

    }
}

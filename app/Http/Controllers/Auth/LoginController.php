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
use Str;
use Hash;
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

    public function getFormReset(){
        return view('reset');
    }
    public function postAuth(){
        $credentials = Request::only('username', 'password');

        if (Auth::attempt($credentials)) {
            if(Auth::user()->status == 0){
                Auth::logout();
                $res['api_status']  = 0;
                $res['api_message'] = 'User anda sudah dinonaktifkan';
                return response()->json($res);
            }
            
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

    public function postReset(){
        $username          = Request::input('username');
        $user_data = DB::table('users')->where('username', $username)->first();
        if($user_data){
            if($user_data->email){
                $token_reset = $user_data->token_reset ? $user_data->token_reset : Str::random(20);
                $update['token_reset'] = $token_reset;
                DB::table('users')
                    ->where('username', $username)
                    ->update($update);
                $link = url('login/form-reset?id='.$user_data->id.'&token='.$token_reset);
                $tujuan['to'] = $user_data->email;
                $send['contents']   = "<p>Halo ".$user_data->nama.",</p>
                <p>Anda telah mencoba untuk mereset Password anda, Klik tombol berikut:</p>
                <span class=\"btn btn-primary\"><a href=\"$link\" target=\"_blank\">Reset Password</a></span>
                <br/>
                <p>Terima kasih.</p>";
                Sideveloper::sendMail('Reset Password', $tujuan, $send);
                $res['api_status']  = 1;
                $res['api_message'] = 'Berhasil, silahkan cek email anda';
            }else{                
                $res['api_status']  = 0;
                $res['api_message'] = 'User anda belum terdaftar email';
            }
        }else{
            $res['api_status']  = 0;
            $res['api_message'] = 'Usename tidak ditemukan';
        }
        return response()->json($res);
    }

    public function postExecuteReset(){
        $id         = Request::input('id');
        $token      = Request::input('token');
        $password_1 = Request::input('password_1');

        $cek_exist = DB::table('users')->where('id', $id)->where('token_reset', $token)->first();
        if($cek_exist){
            $update['token_reset'] = null;
            $update['password']    = Hash::make($password_1);
            DB::table('users')
                ->where('id', $id)
                ->update($update);
                
            $res['api_status']  = 1;
            $res['api_message'] = 'Password berhasil dirubah';
        }else{
            $res['api_status']  = 0;
            $res['api_message'] = 'Halaman Reset Tidak Sesuai / Sudah Kadaluarsa';
        }
        return response()->json($res);
    }
}

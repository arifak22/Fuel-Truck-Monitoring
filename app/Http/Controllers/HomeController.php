<?php  

namespace App\Http\Controllers;

use App;
use Cache;
use Config;
use Crypt;
use DB;
use File;
use Excel;
use Hash;
use Log;
use PDF;
use Request;
use Route;
use Session;
use Storage;
use Schema;
use Validator;
use Auth;
use Pel;
use URL;
use Mail;
use Carbon;
use Sideveloper;

class HomeController extends MiddleController
{
    var $title = 'Home';
    public function getLogout(){
        Auth::logout();
        return redirect()->intended('login');
    }

    public function postUbahPassword()
    {
        $passlama  = $this->input('passlama','required');
        $passbaru1 = $this->input('passbaru1','required');
        $passbaru2 = $this->input('passbaru2','required');
        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }
        
        if (!Hash::check($passlama, Auth::user()->password)) {
            $res['api_status']  = 0;
            $res['api_message'] = 'Password lama tidak sesuai';
            return $this->api_output($res);
        }
        if(!($passbaru1 == $passbaru2)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Konfirmasi password baru tidak match';
            return $this->api_output($res);
        }

        $save['password']   = Hash::make($passbaru1);
        DB::table('users')
            ->where('id', Auth::user()->id)
            ->update($save);
        $res['api_status']  = 1;
        $res['api_message'] = 'Password berhasil diganti';
        return $this->api_output($res);
    }

    public function getIndex(){
        $data['title'] = 'Dashboard';
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl(''), 'title'=> 'Dashboard'],
            // ['link' => '#', 'title'=> 'View'],
        ];
        
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', false);
        return Sideveloper::load('template', 'home/index', $data);
    }

    public function getTruk(){
        $id = $this->input('id');
        $data['title'] = 'Activity Truk';
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl(''), 'title'=> 'Activity Truk'],
            // ['link' => '#', 'title'=> 'View'],
        ];
        
        $alat   = DB::table('m_alat')->where('id_alat', $id)->first();
        $data['id_alat'] = $id;
        $data['alat'] = $alat;
        return Sideveloper::load('template', 'home/truk', $data);
    }
}
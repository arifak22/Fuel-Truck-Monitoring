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

class MasterController extends MiddleController
{
    var $view = 'master.';

    #View Truk
    public function getTruk(){
        $title    = 'Master Truk';
        $subtitle = 'View';
        $data['title'] = $title;
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl('truk'), 'title'=> $title],
            ['link' => '#', 'title'=> $subtitle],
        ];
        return Sideveloper::load('template', $this->view.'truk', $data);
    }

    
    #View User
    public function getUser(){
        $title    = 'Master User';
        $subtitle = 'View';
        $data['title'] = $title;
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl('user'), 'title'=> $title],
            ['link' => '#', 'title'=> $subtitle],
        ];
        
        $alat   = DB::table('privileges')->get();
        $data['privilege'] = Sideveloper::makeOption($alat, 'id_privilege', 'nama_privilege', false);
        return Sideveloper::load('template', $this->view.'user', $data);
    }
}
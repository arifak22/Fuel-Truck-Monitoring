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

    public function getIndex(){
        $data['test'] = 'test';
        return Sideveloper::load('template', 'home/index', $data);
    }
}
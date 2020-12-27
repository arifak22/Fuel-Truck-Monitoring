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

class TransaksiController extends MiddleController
{
    var $title = 'Transaksi';
    var $table = 'transaksi';
    var $pk    = 'id';
    var $view  = 'transaksi.';

    #VIEW
    public function getIndex(){
        $data['title'] = $this->title;
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl(''), 'title'=> $this->title],
            ['link' => '#', 'title'=> 'View'],
        ];
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', false);
        // print_r($data['alat']);die();
        return Sideveloper::load('template', $this->view.'view', $data);
    }
}
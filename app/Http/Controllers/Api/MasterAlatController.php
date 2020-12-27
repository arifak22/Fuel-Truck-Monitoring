<?php  

namespace App\Http\Controllers\Api;

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
use JWTAuth;
use Pel;
use URL;
use Mail;
use Carbon;
use Sideveloper;
use App\Http\Controllers\MiddleController;

class MasterAlatController extends MiddleController
{
    var $table = 'm_alat';
    var $pk    = 'id_alat';

    #List Data
    public function getList(){
        $datatable = $this->input('draw') ?  true : false;
        $search    = $this->input('search');

        $query = DB::table($this->table);

        if($datatable):
            return datatables()->of($query)->toJson();
        else:
            return $query->get();
        endif;
    }
}
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

class TransaksiController extends MiddleController
{
    var $table = 'transaksi';
    var $pk    = 'id';
    var $view  = 'transaksi.';

    #List Data
    public function getList(){
        $datatable = $this->input('draw') ?  true : false;
        $search    = $this->input('search');

        $query = DB::table($this->table)
            ->select('id', 'm_alat.nama as nama_alat', 'tanggal','bbm_level','gps')
            ->join('m_alat', 'm_alat.id_alat', '=', $this->table.'.id_alat');

        if($datatable)
        return datatables()->of($query)->toJson();
    }

    #Insert / Update Data
    public function postInsert(){
        $id        = $this->input('id');
        $id_alat   = $this->input('id_alat',"required|numeric|exists:m_alat,id_alat");
        $tanggal   = $this->input('tanggal', "required|date_format:Y-m-d H:i:s");
        $bbm_level = $this->input('bbm_level', "required|numeric|min:0");
        $gps       = $this->input('gps', 'required');
        $tipe      = $this->input('tipe') ?  $this->input('tipe') : 1;
        $sess      = JWTAuth::parseToken()->getPayload();

        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }

        #CEK EXIST
        $cek_exist = DB::table($this->table)
            ->where('tanggal', $tanggal)
            ->where('id_alat', $id_alat);
        if($tipe == 2){
            $cek_exist->where('id', '<>', $id);
        }
        if($cek_exist->count() > 0){
            $res['api_status']  = 0;
            $res['api_message'] = 'Data Duplikat berdasarkan Alat dan Waktu';
            Sideveloper::createLog('Data Duplikat berdasarkan Alat dan Waktu', 'LOGIC', 'warning');
            return $this->api_output($res);
        }

        #INSERT DATA
        $save['id_alat']    = $id_alat;
        $save['tanggal']    = $tanggal;
        $save['bbm_level']  = $bbm_level;
        $save['gps']        = $gps;        
        if($tipe == 1){
            $save['jwt_device'] = $sess['device'];
            $save['created_by'] = JWTAuth::user()->id;
            $save['created_at'] = new \DateTime();
            DB::table($this->table)->insert($save);
            $res['api_message'] = 'Berhasil Ditambahkan';
        }else{
            DB::table($this->table)
                ->where($this->pk, $id)
                ->update($save);
            $res['api_message'] = 'Berhasil Diubah';
        }

        #BERHASIL
        $res['api_status']  = 1;
        return $this->api_output($res);
    }

    #Delete Data
    public function postDelete(){
        $id = $this->input('id','required');
        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }
        try {
            DB::table($this->table)->where($this->pk, $id)->delete();
            $res['api_status']  = 1;
            $res['api_message'] = 'Data Berhasil dihapus';
            return $this->api_output($res);
        }catch (\Illuminate\Database\QueryException $e) {
            $res['api_status']  = 0;
            $res['api_message'] = 'Maaf, Terjadi masalah!';
            return $this->api_output($res);
        } catch (PDOException $e) {
            $res['api_status']  = 0;
            $res['api_message'] = 'Error';
            return $this->api_output($res);
        }
    }


    #Get Data Update
    public function getFilter(){
        $id = $this->input('id');

        $data = DB::table($this->table)->where($this->pk, $id)->first();

        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }


}
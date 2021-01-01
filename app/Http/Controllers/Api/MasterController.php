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

class MasterController extends MiddleController
{
    var $table = 'm_users';
    var $pk    = 'id';

    #setup Truk
    public function truk(){
        $this->table = 'm_alat';
        $this->pk    = 'id_alat';
    }

    #List Truk Data
    public function getTrukList(){
        $this->truk();
        $datatable = $this->input('draw') ?  true : false;
        $search    = $this->input('search');

        $query = DB::table($this->table);

        if($datatable):
            return datatables()->of($query)->toJson();
        else:
            return $query->get();
        endif;
    }

    #Truk Insert
    public function postTrukInsert(){
        $this->truk();
        $id        = $this->input('id');
        $kode_alat = $this->input('kode_alat', "required|max:50");
        $nama      = $this->input('nama', "required|max:150");
        $status    = $this->input('status', 'required');
        $tipe      = $this->input('tipe') ?  $this->input('tipe') : 1;
        $sess      = JWTAuth::parseToken()->getPayload();

        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }

        #CEK EXIST
        $cek_exist = DB::table($this->table)
            ->where('kode_alat', $kode_alat);
        if($tipe == 2){
            $cek_exist->where($this->pk, '<>', $id);
        }
        if($cek_exist->count() > 0){
            $res['api_status']  = 0;
            $res['api_message'] = 'Kode Alat Sudah Terdaftar';
            Sideveloper::createLog('Kode Alat Sudah Terdaftar', 'LOGIC', 'warning');
            return $this->api_output($res);
        }

        #INSERT DATA
        $save['kode_alat'] = $kode_alat;
        $save['nama'] = $nama;
        $save['status']    = $status;
        if($tipe == 1){
            $save['created_by'] = JWTAuth::user()->id;
            $save['created_at'] = new \DateTime();
            DB::table($this->table)->insert($save);
            $res['api_message'] = 'Berhasil Ditambahkan';
        }else{
            $save['updated_by'] = JWTAuth::user()->id;
            $save['updated_at'] = new \DateTime();
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
    public function postTrukDelete(){
        $this->truk();
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

    #Get Truk Update
    public function getTrukFilter(){
        $this->truk();
        $id = $this->input('id');

        $data = DB::table($this->table)->where($this->pk, $id)->first();

        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }
}
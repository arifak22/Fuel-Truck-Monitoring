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

    #List Data
    public function getReport(){
        $datatable    = $this->input('draw') ?  true : false;
        $id_alat      = $this->input('id_alat');
        $tipe_laporan = $this->input('tipe_laporan');
        $start_date   = $this->input('start_date');
        $end_date     = $this->input('end_date').' 23:59:59';
        $start_month  = $this->input('start_month').'-01';
        $end_month    = date('Y-m-t', strtotime($this->input('end_month').'-28')).' 23:59:59';
        
        $start = $tipe_laporan == '1' ? $start_date : $start_month;
        $end   = $tipe_laporan == '1' ? $end_date : $end_month;
        $query = Sideveloper::getReport($id_alat, $start, $end);

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
            $getId = DB::table($this->table)->insertGetId($save);

            #INSERT LOG DATA
            $transaksi_sebelum = DB::table($this->table)
                ->where('tanggal', '<', $tanggal)
                ->orderBy('tanggal', 'desc')
                ->first();
            if($transaksi_sebelum){
                $nilai_transaksi = $transaksi_sebelum->bbm_level - $bbm_level;
                $status = 'OUT';
                if($nilai_transaksi < 0){ #MENGISI
                    $nilai_transaksi = abs($nilai_transaksi);
                    $status = 'IN';
                }
                $save_log['id_sebelum'] = $transaksi_sebelum->id;
            }else{
                $save_log['id_sebelum']    = null;
                $nilai_transaksi = $bbm_level;
                $status          = 'IN';
            }
            $save_log['id_trans']   = $getId;
            $save_log['tanggal']    = $tanggal;
            $save_log['nilai']      = $nilai_transaksi;
            $save_log['status']     = $status;
            DB::table('transaksi_log')->insert($save_log);
            
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
    public function postDelete(){
        $id = $this->input('id','required');
        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }
        try {
            DB::table($this->table)->where($this->pk, $id)->delete();
            DB::table('transaksi_log')->where('id_sebelum', $id)->delete();
            DB::table('transaksi_log')->where('id_trans', $id)->delete();
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


    #setup Hourmeter
    public function hourmeter(){
        $this->table = 'hourmeter';
        $this->pk    = 'id';
    }

    #List Data
    public function getHourmeterList(){
        $this->hourmeter();
        $datatable = $this->input('draw') ?  true : false;
        $search    = $this->input('search');

        $query = DB::table($this->table)
            ->select('id', 'm_alat.nama as nama_alat', 'tanggal','hour_meter')
            ->join('m_alat', 'm_alat.id_alat', '=', $this->table.'.id_alat');

        if($datatable)
        return datatables()->of($query)->toJson();
    }

    #Insert / Update Data
    public function postHourmeterInsert(){
        $this->hourmeter();
        $id         = $this->input('id');
        $id_alat    = $this->input('id_alat',"required|numeric|exists:m_alat,id_alat");
        $tanggal    = $this->input('tanggal', "required|date_format:Y-m-d H:i:s");
        $hour_meter = $this->input('hour_meter', "required|numeric|min:0");
        $tipe       = $this->input('tipe') ?  $this->input('tipe') : 1;
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
        $save['hour_meter'] = $hour_meter;
        if($tipe == 1){
            $save['jwt_device'] = $sess['device'];
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
    public function postHourmeterDelete(){
        $this->hourmeter();
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
    public function getHourmeterFilter(){
        $this->hourmeter();
        $id = $this->input('id');

        $data = DB::table($this->table)->where($this->pk, $id)->first();

        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }

    #setup Box
    public function box(){
        $this->table = 'box';
        $this->pk    = 'id';
    }

    #List Data
    public function getBoxList(){
        $this->box();
        $datatable = $this->input('draw') ?  true : false;
        $search    = $this->input('search');

        $query = DB::table($this->table)
            ->select('id', 'm_alat.nama as nama_alat', 'tanggal','box')
            ->join('m_alat', 'm_alat.id_alat', '=', $this->table.'.id_alat');

        if($datatable)
        return datatables()->of($query)->toJson();
    }

    #Insert / Update Data
    public function postBoxInsert(){
        $this->box();
        $id         = $this->input('id');
        $id_alat    = $this->input('id_alat',"required|numeric|exists:m_alat,id_alat");
        $tanggal    = $this->input('tanggal', "required|date_format:Y-m-d H:i:s");
        $box = $this->input('box', "required|numeric|min:0");
        $tipe       = $this->input('tipe') ?  $this->input('tipe') : 1;
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
        $save['id_alat'] = $id_alat;
        $save['tanggal'] = $tanggal;
        $save['box']     = $box;
        if($tipe == 1){
            $save['jwt_device'] = $sess['device'];
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
    public function postBoxDelete(){
        $this->box();
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
    public function getBoxFilter(){
        $this->box();
        $id = $this->input('id');

        $data = DB::table($this->table)->where($this->pk, $id)->first();

        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }
    

    public function getLokasiLast(){
        $last_data = DB::select(DB::raw("
            select m_alat.id_alat, m_alat.nama, m_alat.kode_alat, ta.bbm_level, ta.gps, ta.tanggal as tanggal_bbm, 
            tb.hour_meter, tb.tanggal as tanggal_hm, tc.box, CURDATE() tanggal_box
            from m_alat
            join(select bbm_level, gps, tanggal, a.id_alat from transaksi a 
            where a.tanggal = (select max(aa.tanggal) from transaksi as aa where aa.id_alat = a.id_alat)) as ta 
            on ta.id_alat = m_alat.id_alat
            left join(
            select hour_meter, tanggal, id_alat from hourmeter b where 
            tanggal = (select max(bb.tanggal) from hourmeter as bb where bb.id_alat = b.id_alat)) as tb
            on tb.id_alat = m_alat.id_alat
            left join(select sum(box) box, id_alat from box c where date(c.tanggal) = CURDATE()
            GROUP BY id_alat) tc 
            on tc.id_alat = m_alat.id_alat"
        ));
        // dd($last_data);
        $data = array();
        if($last_data){
            foreach($last_data as $key => $ld):
                $data[$key]['lng']         = Sideveloper::parseNMEA($ld->gps)['lng'];
                $data[$key]['lat']         = Sideveloper::parseNMEA($ld->gps)['lat'];
                $data[$key]['id_alat']     = $ld->id_alat;
                $data[$key]['nama']        = $ld->nama;
                $data[$key]['kode_alat']   = $ld->kode_alat;
                $data[$key]['bbm_level']   = $ld->bbm_level;
                $data[$key]['tanggal_bbm'] = Sideveloper::dateFull($ld->tanggal_bbm);
                $data[$key]['hour_meter']  = $ld->hour_meter;
                $data[$key]['tanggal_hm']  = Sideveloper::dateFull($ld->tanggal_hm);
                $data[$key]['box']         = $ld->box;
                $data[$key]['tanggal_box'] = 'Hari ini';
            endforeach;
        }
        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }


}
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
            ->select('transaksi.id', 'm_alat.nama as nama_alat', 'transaksi.tanggal', 'hour_meter','bbm_level','gps')
            ->leftJoin('hourmeter', 'hourmeter.id_trans', '=', $this->table.'.id')
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

    public function postParseNmea(){
        $latitude  = $this->input('latitude');
        $longitude = $this->input('longitude');
        $parse = Sideveloper::decimalTodms($latitude, $longitude);
        
        #BERHASIL
        $res['api_status'] = 1;
        $res['parse']      = $parse;
        return $this->api_output($res);
    }

    #Insert / Update Data
    public function postInsert(){
        $id         = $this->input('id');
        $id_alat    = $this->input('id_alat',"required|numeric|exists:m_alat,id_alat");
        $tanggal    = $this->input('tanggal', "required|date_format:Y-m-d H:i:s");
        $bbm_level  = $this->input('bbm_level', "required|numeric|min:0");
        $hour_meter = $this->input('hour_meter', "nullable||numeric|min:0");
        $gps        = $this->input('gps');
        $latlong    = $this->input('latlong');
        $tipe       = $this->input('tipe') ?  $this->input('tipe') : 1;
        $sess       = JWTAuth::parseToken()->getPayload();

        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }
        if(!isset($gps) && !isset($latlong)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Gps / LatitudeLongitude is required';
            return $this->api_output($res);
        }
        if($latlong){
            $latlong = explode(',', $latlong);
            $latitude  = floatval($latlong[0]);
            $longitude = floatval($latlong[1]);
            $gps = $gps ? $gps : Sideveloper::decimalTodms($latitude, $longitude);
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
        #END CEK EXIST

        #CEK NILAI HOURMETER
        $hm_sebelum = DB::table('hourmeter')
            ->where('tanggal', '<', $tanggal)
            ->where('id_alat', $id_alat)
            ->orderBy('tanggal', 'desc')
            ->value('hour_meter');
        $hm_setelah = DB::table('hourmeter')
            ->where('tanggal', '>', $tanggal)
            ->where('id_alat', $id_alat)
            ->orderBy('tanggal', 'asc')
            ->value('hour_meter');
        if($hm_sebelum && ($hm_sebelum > $hour_meter)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Hour Meter sebelumnya : '. $hm_sebelum;
            return $this->api_output($res);
        }
        if($hm_setelah && ($hm_setelah < $hour_meter)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Hour Meter setelahnya : '. $hm_setelah;
            return $this->api_output($res);
        }
        #END CEK HOUR METER

        #INSERT DATA
        $save['id_alat']    = $id_alat;
        $save['tanggal']    = $tanggal;
        $save['bbm_level']  = $bbm_level;
        $save['gps']        = $gps;
        
        #HOURMETER DATA
        $save_hour['id_alat']    = $id_alat;
        $save_hour['tanggal']    = $tanggal;
        $save_hour['hour_meter'] = $hour_meter;
        $save_hour['jwt_device'] = $sess['device'];

        DB::beginTransaction();
        try{
            if($tipe == 1){
                $save['jwt_device'] = $sess['device'];
                $save['created_by'] = JWTAuth::user()->id;
                $save['created_at'] = new \DateTime();
                $getId = DB::table($this->table)->insertGetId($save);

                #INSERT WHEN ISSET HOURMETER
                if($hour_meter){
                    $save_hour['created_by'] = JWTAuth::user()->id;
                    $save_hour['created_at'] = new \DateTime();
                    $save_hour['id_trans']   = $getId;
                    DB::table('hourmeter')->insert($save_hour);
                }

                $res['api_message'] = 'Berhasil Ditambahkan';
            }else{
                $save['updated_by'] = JWTAuth::user()->id;
                $save['updated_at'] = new \DateTime();
                DB::table($this->table)
                    ->where($this->pk, $id)
                    ->update($save);

                #HOURMETER WHEN ISSET HOURMETER
                if($hour_meter){
                    #HM IS EXIST IN DB
                    $cek_hm_exist = DB::table('hourmeter')->where('id_trans', $id)->count();
                    if($cek_hm_exist > 0){ #UPDATE
                        $save_hour['updated_by'] = JWTAuth::user()->id;
                        $save_hour['updated_at'] = new \DateTime();
                        DB::table('hourmeter')
                            ->where('id_trans', $id)
                            ->update($save_hour);
                    }else{ #INSERT NEW
                        $save_hour['created_by'] = JWTAuth::user()->id;
                        $save_hour['created_at'] = new \DateTime();
                        $save_hour['id_trans']   = $id;
                        DB::table('hourmeter')->insert($save_hour);
                    }
                }
                #DELETE LAST LOG                
                DB::table('transaksi_log')
                    ->whereYear('tanggal', date('Y'))
                    ->whereMonth('tanggal', date('m'))
                    ->where('id_alat', $id_alat)
                    ->delete();

                #INSERT LOG DATA
                $listperiode = DB::table($this->table)
                    ->whereYear('tanggal', date('Y'))
                    ->whereMonth('tanggal', date('m'))
                    ->where('id_alat', $id_alat)
                    ->orderBy('tanggal', 'asc')
                    ->get();
                $periodesebelum = DB::table($this->table)
                    ->whereYear('tanggal', date('Y', strtotime('-1 month')))
                    ->whereMonth('tanggal', date('m', strtotime('-1 month')))
                    ->where('id_alat', $id_alat)
                    ->orderBy('tanggal', 'desc')
                    ->first();
                $bbmstart = null;
                if($periodesebelum){
                    $bbmstart   = $periodesebelum->bbm_level;
                    $id_sebelum = $periodesebelum->id;
                }

                foreach($listperiode as $k => $lp){
                    if($bbmstart){
                        $nilai_transaksi = $bbmstart - $lp->bbm_level;
                        $status = 'OUT';
                        if($nilai_transaksi < 0){ #MENGISI
                            $status = 'IN';
                            $nilai_transaksi = abs($nilai_transaksi);
                        }
                        $save_log['id_sebelum'] = $id_sebelum;
                    }else{
                        $status          = 'IN';
                        $nilai_transaksi = abs($lp->bbm_level);
                        $save_log['id_sebelum']    = null;
                    }
                    $save_log['id_trans'] = $lp->id;
                    $save_log['tanggal']  = $lp->tanggal;
                    $save_log['nilai']    = abs($nilai_transaksi);
                    $save_log['status']   = $status;
                    $save_log['id_alat']  = $id_alat;
                    $save_log['periode']  = date('Ym');
                    DB::table('transaksi_log')->insert($save_log);

                    $bbmstart   = $lp->bbm_level;
                    $id_sebelum = $lp->id;
                }
                $res['api_message'] = 'Berhasil Diubah';
            }

        #BERHASIL
        DB::commit();
        $res['api_status']  = 1;
        }catch (\Illuminate\Database\QueryException $e) {
            #GAGAL
            DB::rollback();
            $res['api_status']  = 0;
            $res['api_message'] = 'Exception 1 Error';
            $res['e']           = $e;
        }catch (PDOException $e) {
            #GAGAL
            DB::rollback();
            $res['api_status']  = 0;
            $res['api_message'] = 'Exception 2 Error';
            $res['e']           = $e;
        }catch(Exception $e){
            #GAGAL
            DB::rollback();
            $res['api_status']  = 0;
            $res['api_message'] = 'Exception 3 Error';
            $res['e']           = $e;
        }
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

        $data = DB::table($this->table)
            ->select('transaksi.id', 'transaksi.tanggal', 'transaksi.id_alat', 'bbm_level', 'hour_meter', 'gps')
            ->leftJoin('hourmeter', 'hourmeter.id_trans', '=', 'transaksi.id')
            ->where('transaksi.'.$this->pk, $id)->first();

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

        #CEK NILAI HOURMETER
        $hm_sebelum = DB::table('hourmeter')
            ->where('tanggal', '<', $tanggal)
            ->where('id_alat', $id_alat)
            ->orderBy('tanggal', 'desc')
            ->value('hour_meter');
        $hm_setelah = DB::table('hourmeter')
            ->where('tanggal', '>', $tanggal)
            ->where('id_alat', $id_alat)
            ->orderBy('tanggal', 'asc')
            ->value('hour_meter');
        if($hm_sebelum && ($hm_sebelum > $hour_meter)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Hour Meter sebelumnya : '. $hm_sebelum;
            return $this->api_output($res);
        }
        if($hm_setelah && ($hm_setelah < $hour_meter)){
            $res['api_status']  = 0;
            $res['api_message'] = 'Hour Meter setelahnya : '. $hm_setelah;
            return $this->api_output($res);
        }
        #END CEK HOUR METER

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
            ->select('id', 'm_alat.nama as nama_alat', 'tanggal_start', 'tanggal_end','box')
            ->join('m_alat', 'm_alat.id_alat', '=', $this->table.'.id_alat');

        if($datatable)
        return datatables()->of($query)->toJson();
    }

    #Insert / Update Data
    public function postBoxInsert(){
        $this->box();
        $id            = $this->input('id');
        $id_alat       = $this->input('id_alat',"required|numeric|exists:m_alat,id_alat");
        $tanggal_start = $this->input('tanggal_start', "required|date_format:Y-m-d H:i:s");
        $tanggal_end   = $this->input('tanggal_end', "required|date_format:Y-m-d H:i:s");
        $box           = $this->input('box', "required|numeric|min:0");
        $tipe          = $this->input('tipe') ?  $this->input('tipe') : 1;
        $sess      = JWTAuth::parseToken()->getPayload();

        #CEK VALID
        if($this->validator()){
            return  $this->validator(true);
        }

        #CEK Tanggal Start < Tanggal End
        if($tanggal_start > $tanggal_end){
            $res['api_status']  = 0;
            $res['api_message'] = 'Tanggal Mulai lebih besar dari tanggal Selesai';
            Sideveloper::createLog('Tanggal Mulai lebih besar dari tanggal Selesai', 'LOGIC', 'warning');
            return $this->api_output($res);
        }

        #CEK EXIST
        $cek_exist = DB::table($this->table)
            ->where('tanggal_start', $tanggal_start)
            ->where('tanggal_end', $tanggal_end)
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
        $save['id_alat']       = $id_alat;
        $save['tanggal_start'] = $tanggal_start;
        $save['tanggal_end']   = $tanggal_end;
        $save['tanggal']       = $tanggal_start;
        $save['box']           = $box;
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
        $id = $this->input('id');
        $whereadd = "";
        if($id){
            $filter  = DB::table('transaksi')->where('id', $id)->first();
            $tanggal = Sideveloper::defaultDate($filter->tanggal);
            $where   = "a.tanggal = '$filter->tanggal' and a.id_alat = $filter->id_alat";
            $where2   = "b.tanggal <= '$filter->tanggal' and b.id_alat = $filter->id_alat order by b.tanggal desc limit 1";
            $where3  = "date(c.tanggal) = $tanggal and c.id_alat = $filter->id_alat";
            $tanggal_box = Sideveloper::date($tanggal);
        }else{
            $tanggal = $this->input('tanggal');
            $ymd = Sideveloper::defaultDate($tanggal);
            $id_alat = $this->input('alat');
            $alat = implode (", ", $id_alat);
            $whereadd = "where m_alat.id_alat in($alat)";
            $where = "a.tanggal = (select max(aa.tanggal) from transaksi as aa where aa.id_alat in ($alat) and aa.tanggal <= '$tanggal')";
            $where2 = "b.tanggal = (select max(bb.tanggal) from hourmeter as bb where bb.id_alat in ($alat) and bb.tanggal <= '$tanggal')";
            $where3 = "date(c.tanggal) = $ymd";
            $tanggal_box = $ymd == date('Y-m-d') ? 'Hari ini' : Sideveloper::date($tanggal);

        }
        $last_data = DB::select(DB::raw("
            select m_alat.id_alat, m_alat.nama, m_alat.kode_alat, ta.bbm_level, ta.gps, ta.tanggal as tanggal_bbm, 
            ifnull(tb.hour_meter, 0) hour_meter, tb.tanggal as tanggal_hm, tc.box, CURDATE() tanggal_box
            from m_alat
            join(select bbm_level, gps, tanggal, a.id_alat from transaksi a 
            where $where) as ta 
            on ta.id_alat = m_alat.id_alat
            left join(
            select hour_meter, tanggal, id_alat from hourmeter b where $where2) as tb
            on tb.id_alat = m_alat.id_alat
            left join(select sum(box) box, id_alat from box c where $where3
            GROUP BY id_alat) tc 
            on tc.id_alat = m_alat.id_alat
            $whereadd
            "
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
                $data[$key]['tanggal_bbm']  = $ld->tanggal_bbm ? Sideveloper::dateFull($ld->tanggal_bbm) : '';
                $data[$key]['hour_meter']  = $ld->hour_meter;
                $data[$key]['tanggal_hm']  = $ld->tanggal_hm ? Sideveloper::dateFull($ld->tanggal_hm) : '';
                $data[$key]['box']         = $ld->box;
                $data[$key]['tanggal_box'] = $tanggal_box;
            endforeach;
        }
        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        $res['data']        = $data;
        return $this->api_output($res);
    }

    #List Data
    public function getSummaryReport(){
        $datatable    = $this->input('draw') ?  true : false;
        $id_alat      = $this->input('id_alat');
        $tipe_laporan = $this->input('tipe_laporan');
        $start_date   = $this->input('start_date').' 00:00:00';
        $end_date     = $this->input('end_date').' 23:59:59';
        $start_month  = $this->input('start_month').'-01'.' 00:00:00';
        $end_month    = date('Y-m-t', strtotime($this->input('end_month').'-28')).' 23:59:59';
        
        $start = $tipe_laporan == '1' ? $start_date : $start_month;
        $end   = $tipe_laporan == '1' ? $end_date : $end_month;
        $waktu = '';
        $waktu   = "AND TANGGAL BETWEEN '$start' and '$end'";
        $prepare = [];
        $where = '';
        if($id_alat){
            $where .= "AND a.id_alat = :id_alat";
            $prepare['id_alat'] = $id_alat;
        }
        $query = DB::select(DB::raw("SELECT a.id_alat, a.nama as nama_alat, IFNULL(round(b.konsumsi_bbm,2), 0) konsumsi_bbm, IFNULL(c.total_box, 0) total_box, 
            IFNULL(round(d.selisih_hourmeter, 2), 0) selisih_hourmeter
            from m_alat a
            LEFT JOIN(
                SELECT sum(nilai) konsumsi_bbm, id_alat FROM transaksi_log where 
                status = 'OUT' $waktu
                GROUP BY id_alat
            ) b ON a.id_alat = b.id_alat
            LEFT JOIN(
                SELECT sum(box) total_box, id_alat FROM box 
                where 1=1  $waktu
                GROUP BY id_alat
            ) c ON a.id_alat = c.id_alat
            LEFT JOIN(
                SELECT A.id_alat,(B.hour_meter-C.hour_meter) selisih_hourmeter FROM ( SELECT id_alat,
                MAX(tanggal) maximum,MIN(tanggal) minimum FROM hourmeter 
                where 1=1 $waktu
                GROUP BY id_alat ) A
                LEFT JOIN hourmeter B ON B.tanggal = A.maximum
                LEFT JOIN hourmeter C ON C.tanggal = A.minimum
            ) d ON a.id_alat = d.id_alat
            WHERE 1=1 $where
            "

        ), $prepare);
        if($datatable)
        return datatables()->of($query)->toJson();
    }

    public function getGrafik(){
        $id_alat = $this->input('alat');
        $start   = $this->input('start_date').' 00:00:00';
        $end     = $this->input('end_date').' 23:59:59';
        $waktu = '';
        $waktu   = "AND TANGGAL BETWEEN '$start' and '$end'";
        $prepare = [];
        $where = '';
        if($id_alat){
            $alat = implode (", ", $id_alat);
            $where .= "AND a.id_alat in ($alat)";
        }
        $query = DB::select(DB::raw("SELECT a.id_alat, a.nama as nama_alat, IFNULL(round(b.konsumsi_bbm,2), 0) konsumsi_bbm, IFNULL(c.total_box, 0) total_box, 
            IFNULL(round(d.selisih_hourmeter, 2), 0) selisih_hourmeter
            from m_alat a
            LEFT JOIN(
                SELECT sum(nilai) konsumsi_bbm, id_alat FROM transaksi_log where 
                status = 'OUT' $waktu
                GROUP BY id_alat
            ) b ON a.id_alat = b.id_alat
            LEFT JOIN(
                SELECT sum(box) total_box, id_alat FROM box 
                where 1=1  $waktu
                GROUP BY id_alat
            ) c ON a.id_alat = c.id_alat
            LEFT JOIN(
                SELECT A.id_alat,(B.hour_meter-C.hour_meter) selisih_hourmeter FROM ( SELECT id_alat,
                MAX(tanggal) maximum,MIN(tanggal) minimum FROM hourmeter 
                where 1=1 $waktu
                GROUP BY id_alat ) A
                LEFT JOIN hourmeter B ON B.tanggal = A.maximum
                LEFT JOIN hourmeter C ON C.tanggal = A.minimum
            ) d ON a.id_alat = d.id_alat
            WHERE 1=1 $where
            "

        ), $prepare);
        $res['alat']              = null;
        $res['konsumsi_bbm']      = null;
        $res['total_box']         = null;
        $res['selisih_hourmeter'] = null;
        foreach($query as $q){
            $res['alat'][]              = $q->nama_alat;
            $res['konsumsi_bbm'][]      = $q->konsumsi_bbm;
            $res['total_box'][]         = $q->total_box;
            $res['selisih_hourmeter'][] = $q->selisih_hourmeter;
        }
        // dd($query);
        #BERHASIL
        $res['api_status']  = 1;
        $res['api_message'] = 'Filter';
        return $this->api_output($res);

    }

    // public function postProccessKonsumsi(){
    //     $periode = $this->input('periode', $periode);
    //     $tahun = substr($periode, 0, 4);
    //     $bulan = substr($periode, 5, 2);
    //     // $data = DB::table($this->table)
    //     //     ->whereYear()
    // }


}
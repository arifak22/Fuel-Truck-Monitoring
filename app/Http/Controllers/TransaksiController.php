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
        return Sideveloper::load('template', $this->view.'view', $data);
    }

    #MAP
    public function getMap(){
        $data['title'] = 'Truck Map';
        $data['breadcrumbs'] = [
            ['link' => '#', 'title'=> 'Truck Map'],
        ];
        $data['test'] = DB::table($this->table)->first();
        return Sideveloper::load('template', $this->view.'map', $data);
    }

    #HOUR METER
    public function getHourMeter(){
        $data['title'] = 'Hour Meter';
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl('hour-meter'), 'title'=> $data['title']],
            ['link' => '#', 'title'=> 'View'],
        ];
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', false);
        return Sideveloper::load('template', $this->view.'hour_meter', $data);
    }

    #BOX
    public function getBox(){
        $data['title'] = 'Box';
        $data['breadcrumbs'] = [
            ['link' => Sideveloper::selfUrl('box'), 'title'=> $data['title']],
            ['link' => '#', 'title'=> 'View'],
        ];
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', false);
        return Sideveloper::load('template', $this->view.'box', $data);
    }

    /**
     * REPORT
     */

    #BBM / LITER
    public function getReport(){
        $data['title'] = 'Laporan BBM / Lokasi';
        $data['breadcrumbs'] = [
            ['link' => '#', 'title'=> $data['title']],
        ];
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', true);
        return Sideveloper::load('template', $this->view.'report', $data);
    }

    #EXPORT EXCEL
    public function getExportReport(){
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $title        = 'Report Lokasi BBM';
        $id_alat      = $this->input('id_alat');
        $nama_alat    = $id_alat ? DB::table('m_alat')->where('id_alat', $id_alat)->value('nama') : 'Semua Alat';
        $tipe_laporan = $this->input('tipe_laporan');
        $nama_tipe    = $tipe_laporan == '1' ? 'Filter Harian' : 'Filter Bulanan';
        $start_date   = $this->input('start_date');
        $end_date     = $this->input('end_date');
        $start_month  = $this->input('start_month');
        $end_month    = $this->input('end_month');

        $nstart_date = $start_date;
        $nend_date   = $end_date.' 23:59:59';        
        $nstart_month  = $start_month.'-01';
        $nend_month    = date('Y-m-t', strtotime($end_month.'-28')).' 23:59:59';

        $start = $tipe_laporan == '1' ? $nstart_date : $nstart_month;
        $end   = $tipe_laporan == '1' ? $nend_date : $nend_month;

        $query = Sideveloper::getReport($id_alat, $start, $end);
        $data = $query->get();

        #SET TITLE
        $spreadsheet->getProperties()->setCreator(Sideveloper::config('appname'))
            ->setTitle($title);
        $spreadsheet->getActiveSheet()->setTitle($title);

        #VALUE EXCEL
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        #SET PROPERTIES
        $start_sebut = $tipe_laporan == '1' ? Sideveloper::date($start_date) : Sideveloper::datePeriode($start_month);
        $end_sebut   = $tipe_laporan == '1' ? Sideveloper::date($end_date) : Sideveloper::datePeriode($end_month);

        $sheet->setCellValue('A1', 'Alat')->setCellValue('B1',':')->setCellValue('C1', $nama_alat);
        $sheet->setCellValue('A2', 'Tipe Laporan')->setCellValue('B2',':')->setCellValue('C2', $nama_tipe);
        $sheet->setCellValue('A3', 'Filter Waktu')->setCellValue('B3',':')->setCellValue('C3', $start_sebut . ' s/d ' . $end_sebut);
        #SET KOLOM
        $sheet->setCellValue('A8', 'NAMA ALAT')
            ->setCellValue('B8', 'TANGGAL')
            ->setCellValue('C8', 'BBM LEVEL')
            ->setCellValue('D8', 'NMEA LOKASI');
        #SET VALUE
        $i = 9;
        foreach($data as $d):
            $sheet->setCellValue('A'.$i, $d->nama_alat)
                ->setCellValue('B'.$i, $d->tanggal)
                ->setCellValue('C'.$i, $d->bbm_level)
                ->setCellValue('D'.$i, $d->gps);
            $i++;
        endforeach;
        #SET SIZE
        $sheet->getColumnDimension('A')->setAutoSize(TRUE);
        $sheet->getColumnDimension('B')->setAutoSize(TRUE);
        $sheet->getColumnDimension('C')->setAutoSize(TRUE);
        $sheet->getColumnDimension('D')->setAutoSize(TRUE);

        #OUTPUT EXCEL
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$title.'.xlsx"');
        $writer->save("php://output");
    }

    

    public function getSummaryReport(){
        $data['title'] = 'Summary Report';
        $data['breadcrumbs'] = [
            ['link' => '#', 'title'=> $data['title']],
        ];
        $alat   = Sideveloper::getAlat()->get();
        $data['alat'] = Sideveloper::makeOption($alat, 'id_alat', 'nama', true);
        return Sideveloper::load('template', $this->view.'summary-report', $data);
    }
}
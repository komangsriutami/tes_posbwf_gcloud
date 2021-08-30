<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use App\Http\Requests;
use App\MasterObat;
use App\MasterApotek;
use App;
use Datatables;
use DB;
use Auth;
use Illuminate\Support\Carbon;
use Mail;
use Spipu\Html2Pdf\Html2Pdf;
use PDF;
use Response;

use Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceAppController extends BaseController
{
 	// API Lavie
    public function ef4c2ce3032d8f024c320308d9880a06() {
        $inisial = 'lv';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        			->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'd.satuan',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'a.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok'
                        ])
        			->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
        			->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
        			->where('a.is_disabled', 0)
        			->where('a.is_deleted', 0)
        			->get();

        echo json_encode($rekaps);
        /* if(count($rekaps) > 0){ 
            return $this->sendResponse($rekaps, 'Successfully get data stock apotek lavie.');
        } 
        else{ 
            return $this->sendError('Failed.', ['error'=>'Failed get data stock apotek lavie']);
        } */
    }
    // API Bekul
    public function f31d5936f25442ecf43a2e4a9aa911d1() {
        $inisial = 'bkl';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        			->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'd.satuan',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
        			->get();

        echo json_encode($rekaps);
        /* if(count($rekaps) > 0){ 
            return $this->sendResponse($rekaps, 'Successfully get data stock apotek bekul.');
        } 
        else{ 
            return $this->sendError('Failed.', ['error'=>'Failed get data stock apotek bekul']);
        } */
    }

    // API Pjm
    public function f36c008db00e367c7dae1c4a856e55ca() {
        $inisial = 'pjm';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        			->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'd.satuan',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
        			->get();

        echo json_encode($rekaps);
        /* if(count($rekaps) > 0){ 
            return $this->sendResponse($rekaps, 'Successfully get data stock apotek pujamandala.');
        } 
        else{ 
            return $this->sendError('Failed.', ['error'=>'Failed get data stock apotek pujamandala']);
        } */
    }

    // API PG
    public function ed70a85853284244f63de7fbd08ccea5(){
        $inisial = 'pg';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        			->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'd.satuan',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
        			->get();
                    
        echo json_encode($rekaps);
        /* if(count($rekaps) > 0){ 
            return $this->sendResponse($rekaps, 'Successfully get data stock apotek puri gading.');
        } 
        else{ 
            return $this->sendError('Failed.', ['error'=>'Failed get data stock apotek puri gading']);
        } */
    }

    // API TL
    public function f60ba84e9e162c05eaf305d15372e4f4(){
        $inisial = 'tl';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        			->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'd.satuan',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
        			->get();
        
        echo json_encode($rekaps);
        /* if(count($rekaps) > 0){ 
            return $this->sendResponse($rekaps, 'Successfully get data stock apotek legian 777.');
        } 
        else{ 
            return $this->sendError('Failed.', ['error'=>'Failed get data stock apotek legian 777']);
        } */
    }

    public function download_apotek()
    {
        $date_now = date('Ymd');
        $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
            ,   'Content-type'        => 'text/csv'
            ,   'Content-Disposition' => 'attachment; filename=APOTIKBWF_MASTERSTORE_'.$date_now.'.csv'
            ,   'Expires'             => '0'
            ,   'Pragma'              => 'public'
        ];

        $list = MasterApotek::select('id as STORE_NUMBER', 'nama_singkat as STORE_NAME', 'alamat as STORE_ADDRESS')->whereIn('id', [1, 2, 3, 4])->get()->toArray();

        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

       $callback = function() use ($list) 
        {
            $FH = fopen('php://output', 'w');
            foreach ($list as $row) { 
                fputcsv($FH, $row, "|");
            }
            fclose($FH);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function download_master_obat()
    {
        $date_now = date('Ymd');
        $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
            ,   'Content-type'        => 'text/csv'
            ,   'Content-Disposition' => 'attachment; filename=APOTIKBWF_MASTERPRODUCT_'.$date_now.'.csv'
            ,   'Expires'             => '0'
            ,   'Pragma'              => 'public'
        ];

        $list = MasterObat::select('tb_m_obat.id as NO_SKU', 
                            'tb_m_obat.nama as PRODUCT_NAME', 
                            DB::raw('a.satuan as UNIT'), 
                            'tb_m_obat.barcode as BAR_CODE', 
                            DB::raw('"-" as CATEGORY'), 
                            DB::raw('"-" as SUBCATEGORY'),
                            DB::raw('"-" as BRAND'), 
                            DB::raw('"-" as DOSE'))
                    ->leftJoin('tb_m_satuan as a', 'a.id', '=', 'tb_m_obat.id_satuan')
                    ->where('tb_m_obat.is_deleted', 0)
                    ->get()
                    ->toArray();

        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

       $callback = function() use ($list) 
        {
            $FH = fopen('php://output', 'w');
            foreach ($list as $row) { 
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function download_stok_obat()
    {
        $date_now = date('YmdHms');
        $headers = [
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
            ,   'Content-type'        => 'text/csv'
            ,   'Content-Disposition' => 'attachment; filename=APOTIKBWF_PRODUCTONHAND_'.$date_now.'.csv'
            ,   'Expires'             => '0'
            ,   'Pragma'              => 'public'
        ];
        
    
        $data_apotek_LV = $this->hitung_stok_apotek(1, 'lv');
        $data_apotek_BKL = $this->hitung_stok_apotek(2, 'bkl');
        $data_apotek_PJM = $this->hitung_stok_apotek(3, 'pjm');
        $data_apotek_PG = $this->hitung_stok_apotek(4, 'pg');


        $data_new = $data_apotek_LV;
        $data_new = array_merge($data_new, $data_apotek_BKL);
        $data_new = array_merge($data_new, $data_apotek_PJM);
        $data_new = array_merge($data_new, $data_apotek_PG);

        # add headers for each column in the CSV download
        array_unshift($data_new, array_keys($data_new[0]));
      
        $callback = function() use ($data_new) 
        {
            $FH = fopen('php://output', 'w');
            foreach ($data_new as $row) { 
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function hitung_stok_apotek($var, $apotek) {
        $list = DB::table('tb_m_stok_harga_'.$apotek.' as a')
        			->select([DB::raw(''.$var.' as STORE_NUMBER'),
        					'b.id as NO_SKU',
                            'a.stok_akhir as STOCK_AVAILABILITY',
                            'a.harga_jual as BASE_PRICE',
                            'a.harga_jual as DISCOUNT_PRICE'
                        ])
        			->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
        			->where('a.is_deleted', 0)
        			->get()->toArray();
        $array = json_decode(json_encode($list), true);
       

        return $array;
    }

    // API template go apotek lavie
    public function template_lv() {
        $inisial = 'lv';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
                    ->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok',
                            'd.satuan'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
                    ->get();
        
    

        $collection = collect();
        $no = 0;
        $total_excel=0;
        foreach($rekaps as $rekap) {
            $no++;
            $collection[] = array(
                $rekap->id,
                $rekap->nama,
                $rekap->satuan,
                $rekap->patokanhargajual,
                $rekap->total_stok
            );
        }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['Kode Item', 'Nama Item', 'Satuan', 'Harga', 'Stok'];
                    } 

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Template Sinkron Go Apotek_Lavie.xlsx");
    }
    // API Bekul
    public function template_bkl() {
        $inisial = 'bkl';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
                    ->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok',
                            'd.satuan'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
                    ->get();
        
    

        $collection = collect();
        $no = 0;
        $total_excel=0;
        foreach($rekaps as $rekap) {
            $no++;
            $collection[] = array(
                $rekap->id,
                $rekap->nama,
                $rekap->satuan,
                $rekap->patokanhargajual,
                $rekap->total_stok
            );
        }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['Kode Item', 'Nama Item', 'Satuan', 'Harga', 'Stok'];
                    } 

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Template Sinkron Go Apotek_Bekul.xlsx");
    }

    // API Pjm
    public function template_pjm() {
        $inisial = 'pjm';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
                    ->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok',
                            'd.satuan'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
                    ->get();
        
    

        $collection = collect();
        $no = 0;
        $total_excel=0;
        foreach($rekaps as $rekap) {
            $no++;
            $collection[] = array(
                $rekap->id,
                $rekap->nama,
                $rekap->satuan,
                $rekap->patokanhargajual,
                $rekap->total_stok
            );
        }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['Kode Item', 'Nama Item', 'Satuan', 'Harga', 'Stok'];
                    } 

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Template Sinkron Go Apotek_Pujamandala.xlsx");
    }

    // API PG
    public function template_pg(){
        $inisial = 'pg';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
                    ->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok',
                            'd.satuan'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
                    ->get();
        
    

        $collection = collect();
        $no = 0;
        $total_excel=0;
        foreach($rekaps as $rekap) {
            $no++;
            $collection[] = array(
                $rekap->id,
                $rekap->nama,
                $rekap->satuan,
                $rekap->patokanhargajual,
                $rekap->total_stok
            );
        }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['Kode Item', 'Nama Item', 'Satuan', 'Harga', 'Stok'];
                    } 

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Template Sinkron Go Apotek_Puri Gading.xlsx");
    }

    // API TL
    public function template_tl(){
        $inisial = 'tl';

        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.' as a')
                    ->select(['b.id',
                            'b.barcode', 
                            'b.nama', 
                            'c.keterangan as golongan_obat',
                            'b.isi_tab as isibox',
                            'b.isi_strip as isistrip',
                            'b.harga_jual as patokanhargajual', 
                            'a.stok_akhir as total_stok',
                            'd.satuan'
                        ])
                    ->leftJoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
                    ->leftJoin('tb_m_golongan_obat as c','c.id','=','b.id_golongan_obat')
                    ->leftJoin('tb_m_satuan as d','d.id','=','b.id_satuan')
                    ->where('a.is_disabled', 0)
                    ->where('a.is_deleted', 0)
                    ->get();
        
    

        $collection = collect();
        $no = 0;
        $total_excel=0;
        foreach($rekaps as $rekap) {
            $no++;
            $collection[] = array(
                $rekap->id,
                $rekap->nama,
                $rekap->satuan,
                $rekap->patokanhargajual,
                $rekap->total_stok
            );
        }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['Kode Item', 'Nama Item', 'Satuan', 'Harga', 'Stok'];
                    } 

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Template Sinkron Go Apotek_Legian.xlsx");
    }
}

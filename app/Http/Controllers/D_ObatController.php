<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Requests;
use App\MasterObat;
use App\MasterGolonganObat;
use App\MasterPenandaanObat;
use App\MasterProdusen;
use App\MasterSatuan;
use App\MasterApotek;
use App\TransaksiPembelian;
use App\TransaksiPembelianDetail;
use App\TransaksiTO;
use App\TransaksiTODetail;
use App\PenyesuaianStok;
use App\MasterJenisTransaksi;
use App\MasterSuplier;
use App\TransaksiPenjualan;
use App\TransaksiPenjualanDetail;
use App\TransaksiPODetail;
use App\TransaksiPO;
use App\TransaksiTD;
use App\TransaksiTDDetail;
use App\User;
use App;
use Datatables;
use DB;
use Excel;
use Auth;
use Cache;
use Input;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Imports\GolonganObatImport;

class D_ObatController extends Controller
{
    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 25/02/2020
        =======================================================================================
    */
    public function index()
    {
        return view('data_obat.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 25/02/2020
        =======================================================================================
    */
    public function list_data_obat(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        $apotek = MasterApotek::find(session('id_apotek_active'));
        $apoteker = User::find($apotek->id_apoteker);
        $id_user = Auth::user()->id;
        $hak_akses = 0;
        if($apoteker->id == $id_user) {
            $hak_akses = 1;
        }

        if($id_user == 1 || $id_user == 2 || $id_user == 16) {
            $hak_akses = 1;
        }

        $apoteks = MasterApotek::where('is_deleted', 0)->whereNotIn('id', [$apotek->id])->get();

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_m_stok_harga_'.$inisial.'')->select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_m_stok_harga_'.$inisial.'.*', 'tb_m_obat.nama', 'tb_m_obat.barcode', 'tb_m_obat.isi_tab', 'tb_m_obat.isi_strip', 'tb_m_obat.rak', 'tb_m_obat.untung_jual'])
        			->join('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0);
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('tb_m_obat.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_m_obat.barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('nama', function($data) use($apoteks){
            $info = '<small>';
            $i = 0;
            foreach($apoteks as $obj) {
                $i++;
                $inisial = strtolower($obj->nama_singkat);
                $cek_ = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $data->id_obat)->first();
                $info .= $obj->nama_singkat.' : '.$cek_->stok_akhir;
                if($i != count($apoteks)) {
                    $info .= ' | ';
                }
            }
            $info .= '</small>';
            return '<b>'.$data->nama.'</b><br>'.$info;
        })
        ->editcolumn('isi_tab', function($data){
            return $data->isi_tab.'/'.$data->isi_strip; 
        }) 
        ->editcolumn('untung_jual', function($data){
            return $data->untung_jual.'%'; 
        }) 
        ->editcolumn('harga_beli', function($data) use($hak_akses){
            $info = '';
            $info .= $data->harga_beli.'<br>';
            if($hak_akses == 1) {
                $info .= '<span class="label" onClick="edit_harga_beli('.$data->id_obat.')" data-toggle="tooltip" data-placement="top" title="Edit Data" style="font-size:10pt;color:#0097a7;">[Edit]</span>';
            }
            return $info; 
        }) 
        ->editcolumn('harga_beli_ppn', function($data) use($hak_akses){
            $info = '';
            $info .= $data->harga_beli_ppn.'<br>';
            if($hak_akses == 1) {
                $info .= '<span class="label" onClick="edit_harga_beli_ppn('.$data->id_obat.')" data-toggle="tooltip" data-placement="top" title="Edit Data" style="font-size:10pt;color:#0097a7;">[Edit]</span>';
            }
            return $info; 
        }) 
        ->editcolumn('harga_jual', function($data) use($hak_akses){
            $info = '';
            $info .= $data->harga_jual.'<br>';
            if($hak_akses == 1) {
                $info .= '<span class="label" onClick="edit_harga_jual('.$data->id_obat.')" data-toggle="tooltip" data-placement="top" title="Edit Data" style="font-size:10pt;color:#0097a7;">[Edit]</span>';
            }
            return $info; 
        }) 
        ->editcolumn('is_disabled', function($data){
            if($data->is_disabled == 1) {
                $s = 'Non Aktif';'<small style="color:#d32f2f;"><i class="fa fa-close"></i></small>';
            } else {
                $s = 'Aktif';//;'<small style="color:#388e3c;"><i class="fa fa-check-square-o"></i></small>';
            }
            return $s; 
        }) 
        ->editcolumn('is_status_harga', function($data){
            $status = '';
            $status .= '<label class="switch">';
            if($data->is_status_harga == 0) {
                $status .= '<input type="checkbox" name="is_status_harga" id="is_status_harga" value="0" onclick="checkStatus(0, '.$data->id.')" >
                <span class="slider round"></span>';
            } else {
                $status .= '<input type="checkbox" name="is_status_harga" id="is_status_harga" value="1" checked="checked" onclick="checkStatus(1, '.$data->id.')">
                <span class="slider round"></span>';
             }
            $status .= '</label>';

            return $status; 
        }) 
        ->addcolumn('action', function($data) use ($hak_akses, $apoteker) {
            $btn = '<div class="btn-group">';
            if($hak_akses == 1) {
                $btn .= '<span class="btn btn-primary" onClick="edit_data('.$data->id_obat.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
            }
            $btn .= '<a href="'.url('/data_obat/stok_obat/'.$data->id_obat).'" title="Stok Obat" class="btn btn-info"><span data-toggle="tooltip" data-placement="top" title="Stok Obat"><i class="fa fa-prescription-bottle-alt"></i></span></a>';
            $btn .= '<a href="'.url('/data_obat/histori_harga/'.$data->id_obat).'" title="Histori Harga" class="btn btn-secondary"><span data-toggle="tooltip" data-placement="top" title="Histori Harga"><i class="fa fa-history"></i></span></a>';
            $btn .= '<a href="'.url('/data_obat/histori_all/'.$data->id_obat).'" title="Histori All" class="btn btn-warning"><span data-toggle="tooltip" data-placement="top" title="Histori All"><i class="fa fa-clone"></i></span></a>';

            if($hak_akses == 1) {
                    $btn .= '<a href="'.url('/data_obat/penyesuaian_stok/'.$data->id_obat).'" class="btn"  style="background-color: #8BC34A; color:#fff;" onClick="#" data-toggle="tooltip" data-placement="top" title="Penyesuaian Stok"><i class="fa fa-flag"></i></a>';
                    $btn .= '<span class="btn btn-danger" onClick="disabled_obat('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Non-aktifkan Obat"><i class="fa fa-power-off"></i></span>';
            } 
            // request kak rudy, minta buat dimatikan
           /* $btn .= '<span class="btn btn-warning" onClick="sycn_harga_obat('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Sinkronisasi Obat"><i class="fa fa-sync"></i></span>';*/
            
            
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['isi_tab', 'is_disabled', 'DT_RowIndex', 'action', 'nama', 'harga_beli', 'harga_beli_ppn', 'harga_jual', 'is_status_harga', 'untung_jual'])
        ->addIndexColumn()
        ->make(true);  
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 26/02/2020
        =======================================================================================
    */
    public function edit($id)
    {
        $obat = MasterObat::find($id);
        $id_apotek = session('id_apotek_active');
        $apotek = MasterApotek::find($id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
        $outlet = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obat->id)->first();

        $produsens = MasterProdusen::where('is_deleted', 0)->pluck('nama', 'id');
        $produsens->prepend('-- Pilih Produsen --','');

        $satuans = MasterSatuan::where('is_deleted', 0)->pluck('satuan', 'id');
        $satuans->prepend('-- Pilih Satuan --','');

        $golongan_obats = MasterGolonganObat::where('is_deleted', 0)->pluck('keterangan', 'id');
        $golongan_obats->prepend('-- Pilih Golongan Obat --','');

        $penandaan_obats = MasterPenandaanObat::where('is_deleted', 0)->pluck('nama', 'id');
        $penandaan_obats->prepend('-- Pilih Penandaan Obat --','');

        return view('data_obat.edit')->with(compact('obat', 'produsens', 'satuans', 'golongan_obats', 'penandaan_obats', 'outlet'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 22/02/2020
        =======================================================================================
    */
    public function show($id)
    {
        //
    }


    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 26/02/2020
        =======================================================================================
    */
    public function update(Request $request, $id)
    {
        $obat = MasterObat::find($id);
        $id_apotek = session('id_apotek_active');
        $apotek = MasterApotek::find($id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
        $outlet = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obat->id)->first();
        $harga_beli_awal = $outlet->harga_beli;
        $harga_jual_awal = $outlet->harga_jual;
        $validator = $obat->validate();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            // add histori perubahan data harga obat
            if($harga_jual_awal != $request->harga_jual) {
                $data_histori_ = array('id_obat' => $obat->id, 'harga_beli_awal' => $harga_beli_awal, 'harga_beli_akhir' => $request->harga_beli, 'harga_jual_awal' => $harga_jual_awal, 'harga_jual_akhir' => $request->harga_jual, 'created_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'));

                // update harga obat
                $inisial = strtolower($apotek->nama_singkat);
                DB::table('tb_histori_harga_'.$inisial.'')->insert($data_histori_);
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obat->id)->update(['updated_at' => date('Y-m-d H:i:s'), 'harga_jual' => $request->harga_jual, 'updated_by' => Auth::user()->id]);
            } 

            echo json_encode(array('status' => 1));
        }
    }

    public function sycn_harga_obat_all() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $data = DB::table('tb_m_stok_harga_'.$inisial.'')->get();
        $i=0;
        foreach ($data as $key => $val) {
            $cek_ = MasterObat::find($val->id_obat);
            if(!empty($cek_)) {
                if($cek_->harga_beli != $val->harga_beli OR $cek_->harga_jual != $val->harga_jual) {
                    $i++;
                    DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id', $val->id)
                    ->update(['harga_beli' => $cek_->harga_beli, 'harga_jual' => $cek_->harga_jual, 'is_sync' => 1, 'sync_by' => Auth::id(), 'sync_at' => date('Y-m-d H:i:s')]);

                    // add histori perubahan data harga obat
                    $data_histori_ = array('id_obat' => $val->id_obat, 'harga_beli_awal' => $val->harga_beli, 'harga_beli_akhir' => $cek_->harga_beli, 'harga_jual_awal' => $val->harga_beli, 'harga_jual_akhir' => $cek_->harga_jual, 'created_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'));

    	   			DB::table('tb_histori_harga_'.$inisial.'')->insert($data_histori_);
                } 
            } else {
                DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id', $val->id)
                    ->update(['is_deleted' => 1, 'deleted_by' => Auth::user()->id, 'deleted_at' => date('Y-m-d H:i:s')]);
            }
        }

        if($i > 0) {
            echo 1;
        } else {
            echo 0;
        }
    }

    public function sycn_harga_obat(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $val = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id)->first();
        $cek_ = MasterObat::find($request->id);

        $i=0;
        if($cek_->harga_beli != $val->harga_beli OR $cek_->harga_jual != $val->harga_jual) {
        	$i++;
            DB::table('tb_m_stok_harga_'.$inisial.'')
            ->where('id', $val->id)
            ->update(['harga_beli' => $cek_->harga_beli, 'harga_jual' => $cek_->harga_jual, 'is_sync' => 1, 'sync_by' => Auth::id(), 'sync_at' => date('Y-m-d H:i:s')]);

            // add histori perubahan data harga obat
            $data_histori_ = array('id_obat' => $val->id_obat, 'harga_beli_awal' => $val->harga_beli, 'harga_beli_akhir' => $cek_->harga_beli, 'harga_jual_awal' => $val->harga_beli, 'harga_jual_akhir' => $cek_->harga_jual, 'created_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'));

   			DB::table('tb_histori_harga_'.$inisial.'')->insert($data_histori_);
        } 

        if($i > 0) {
            echo 1;
        } else {
            echo 0;
        }
    }

    public function disabled_obat(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        DB::table('tb_m_stok_harga_'.$inisial.'')
                ->where('id', $request->id)
                ->update(['is_disabled' => 1, 'disabled_by' => Auth::id(), 'disabled_at' => date('Y-m-d H:i:s')]);

        echo 1;
    }

    public function stok_obat($id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $id)->first();
        $obat = MasterObat::find($id);
        $jenis_transasksis      = MasterJenisTransaksi::pluck('nama', 'id');
        $jenis_transasksis->prepend('-- Pilih Jenis Transaksi --','');
        return view('data_obat.stok_obat')->with(compact('obat', 'stok_harga', 'jenis_transasksis'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 21/06/2020
        =======================================================================================
    */
    public function list_data_stok_obat(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_stok_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_stok_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_stok_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_stok_'.$inisial.'.id_jenis_transaksi')
                ->where(function($query) use($request, $inisial){
                    $query->where('tb_histori_stok_'.$inisial.'.id_obat', $request->id_obat);
                    $query->where('tb_histori_stok_'.$inisial.'.id_jenis_transaksi','LIKE',($request->id_jenis_transaksi > 0 ? $request->id_jenis_transaksi : '%'.$request->id_jenis_transaksi.'%'));

                    if($request->tgl_awal != "") {
                        $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                        $query->whereDate('tb_histori_stok_'.$inisial.'.created_at','>=', $tgl_awal);
                    }

                    if($request->tgl_akhir != "") {
                        $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                        $query->whereDate('tb_histori_stok_'.$inisial.'.created_at','<=', $tgl_akhir);
                    }

                });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_stok_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_stok_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            return date('d-m-Y', strtotime($data->created_at)); 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            $string = '';
            $id_nota = ''; 
            $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
            $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
            $data_tf_keluar_ = array(4, 8, 17);
            $data_penjualan_ = array(1, 5, 6, 15);
            $data_penyesuaian_ = array(9,10);
            $data_so_ = array(11);
            $data_po_ = array(18, 19, 20, 21);
            $data_td_ = array(22, 23, 24, 25);
            if (in_array($data->id_jenis_transaksi, $data_pembelian_)) {
                $check = TransaksiPembelianDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id.' | No.Faktur : '.$check->nota->no_faktur;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_masuk_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Masuk dari '.$check->nota->apotek_asal->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_keluar_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Tujuan ke '.$check->nota->apotek_tujuan->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_penjualan_)) {
                $check = TransaksiPenjualanDetail::find($data->id_transaksi);
                if($check->nota->is_kredit == 1) {
                    $string = '<b>Vendor : '.$check->nota->vendor->nama.'</b>';
                } else {
                    $string = '<b>Member : - </b>';
                }
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_po_)) {
                $check = TransaksiPODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_td_)) {
                $check = TransaksiTDDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else  if(in_array($data->id_jenis_transaksi, array(26))) {
                $retur = ReturPembelian::find($data->id_transaksi);
                $check = TransaksiPembelianDetail::find($retur->id_detail_nota);
                $id_nota = ' | IDNota : '.$check->nota->id.' | No.Faktur : '.$check->nota->no_faktur;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } 

            if($string != '') {
                $string = '<br>'.$string;
            }

            return $data->nama_transaksi.$string.'<br>'.'IDdet : '.$data->id_transaksi.$id_nota; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by'])
        ->make(true);  
    }

    public function histori_harga($id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $id)->first();
        $obat = MasterObat::find($id);
        return view('data_obat.histori_harga')->with(compact('obat', 'stok_harga'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 21/06/2020
        =======================================================================================
    */
    public function list_data_histori_harga(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_harga_'.$inisial.'')->select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_histori_harga_'.$inisial.'.*', 'users.nama as oleh'])
                ->join('users', 'users.id', '=', 'tb_histori_harga_'.$inisial.'.created_by')
                ->where('tb_histori_harga_'.$inisial.'.id_obat', $request->id_obat);
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_harga_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            return date('d-m-Y', strtotime($data->created_at)); 
        }) 
        ->editcolumn('harga_beli_awal', function($data){
            $harga_beli_awal = 'Rp '.number_format($data->harga_beli_awal,0,',','.');
            return $harga_beli_awal; 
        }) 
        ->editcolumn('harga_beli_akhir', function($data){
            $harga_beli_akhir = 'Rp '.number_format($data->harga_beli_akhir,0,',','.');
            return $harga_beli_akhir; 
        }) 
        ->editcolumn('harga_jual_awal', function($data){
            $harga_jual_awal = 'Rp '.number_format($data->harga_jual_awal,0,',','.');
            return $harga_jual_awal; 
        }) 
        ->editcolumn('harga_jual_akhir', function($data){
            $harga_jual_akhir = 'Rp '.number_format($data->harga_jual_akhir,0,',','.');
            return $harga_jual_akhir; 
        }) 
        ->editcolumn('id_asal', function($data){
            $string = '';
            if($data->is_asal == 1) {
                $string = 'Pembelian<b> | ID Transaksi : '.$data->id_asal.'</b>';
            } else if($data->is_asal == 2) {
                $string = 'Penyesuaian Harga<b> | Stok Opnam</b>';
            } else {
                $string = 'Penyesuaian Harga<b> | Master Data</b>';
            }
            return $string; 
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 20) {
                $trimstring = substr($data->oleh, 0, 20);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'created_by', 'id_asal'])
        ->make(true);  
    }

    public function export_data_obat_stok(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        return Excel::download(new MasterObat, 'siswa.xlsx');

        

      // ::import(new MasterObat, 'users.xlsx');

        /*$myFile =  Excel::create('Data Stok Obat', function($excel) use ($request, $inisial) {
            
            $excel->sheet('Sheet 1', function($sheet) use ($request, $inisial) {

                $headings = array('No', 'Barcode','Nama', 'Isi /tab','Isi /strip', 'Harga Beli', 'Harga Jual', 'Stok');
                 $sheet->cell('A1:Q1', function($cell) {
                    $cell->setFontWeight('bold');
                });

                $sheet->appendRow(1, $headings);

                DB::statement(DB::raw('set @rownum = 0'));
                $data = DB::table('tb_m_stok_harga_'.$inisial.'')->select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_m_stok_harga_'.$inisial.'.*', 'tb_m_obat.nama', 'tb_m_obat.barcode', 'tb_m_obat.isitab', 'tb_m_obat.isistrip'])
                    ->join('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0)
                    ->where('tb_m_stok_harga_'.$inisial.'.is_disabled', 0)
                    ->get();

                $no = 0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $data[] = array(
                        $no,
                        $rekap->barcode,
                        $rekap->nama,
                        $rekap->isitab,
                        $rekap->isistrip,
                        $rekap->harga_beli,
                        $rekap->harga_jual,
                        $rekap->stok_akhir
                    );
                }
                
                $sheet->fromArray($data, null, 'A2', false, false);
            });
        });

        $myFile = $myFile->string('xlsx'); //change xlsx for the format you want, default is xls
        $response =  array(
           'name' => "Data Stok Obat", //no extention needed
           'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,".base64_encode($myFile) //mime type of used format
        );
        return response()->json($response);*/
    }

    public function penyesuaian_stok($id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $id)->first();
        $obat = MasterObat::find($id);
        return view('data_obat.penyesuaian_stok')->with(compact('obat', 'stok_harga'));
    }

    /*$data = DB::table('tb_histori_stok_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_stok_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_stok_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_stok_'.$inisial.'.id_jenis_transaksi')
                ->where('tb_histori_stok_'.$inisial.'.id_obat', $request->id_obat);
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_stok_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_stok_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            return date('d-m-Y', strtotime($data->created_at)); 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            return $data->nama_transaksi.' | ID Transaksi : '.$data->id_transaksi; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            if($data->id_jenis_transaksi == 2) {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            if($data->id_jenis_transaksi == 2) {
                $batch = $data->ed;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by'])
        ->make(true);  */

    public function list_data_penyesuaian_stok_obat(Request $request) {
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        $super_admin = session('super_admin');
        DB::statement(DB::raw('set @rownum = 0'));
        $data = PenyesuaianStok::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_penyesuaian_stok_obat.*', 'users.nama as oleh'])
        ->join('users', 'users.id', '=', 'tb_penyesuaian_stok_obat.created_by')
        ->where(function($query) use($request, $super_admin){
            $query->where('tb_penyesuaian_stok_obat.is_deleted','=','0');
            $query->where('tb_penyesuaian_stok_obat.id_obat', $request->id_obat);
            $query->where('tb_penyesuaian_stok_obat.id_apotek_nota', session('id_apotek_active'));
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('stok_awal','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('stok_akhir','LIKE','%'.$request->get('search')['value'].'%');
            });
        }) 
        ->editcolumn('created_at', function($data) use($request){
            return Carbon::parse($data->created_at)->format('d/m/Y H:i:s');
        })
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'created_by'])
        ->make(true);  
    }

    public function export(Request $request) 
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
       
        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->select([
                            'tb_m_stok_harga_'.$inisial.'.*', 
                            'tb_m_obat.nama', 
                            'tb_m_obat.barcode', 
                            'tb_m_obat.isi_tab', 
                            'tb_m_obat.isi_strip', 
                            'tb_m_obat.rak'
                    ])
                    ->join('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0)
                    ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $collection[] = array(
                        $no,
                        $rekap->barcode,
                        $rekap->nama,
                        $rekap->isi_strip,
                        $rekap->isi_tab,
                        $rekap->rak,
                        "Rp ".number_format($rekap->harga_beli,2),
                        "Rp ".number_format($rekap->harga_beli_ppn,2),
                        "Rp ".number_format($rekap->harga_jual,2),
                        $rekap->stok_akhir
                    );
                }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['No', 'Barcode', 'Nama Obat', 'Isi/strip', 'Isi/tab', 'Rak', 'Harga Beli', 'Harga Beli + PPN', 'Harga Jual', 'Stok'];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 40,
                            'D' => 10,
                            'E' => 10,
                            'F' => 10,
                            'G' => 20,
                            'H' => 20,
                            'I' => 20,  
                            'J' => 10,      
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'D'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'E'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'I'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'J'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Data Obat Apotek ".$apotek->nama_singkat.".xlsx");
    }

    public function persediaan() {
        $tahun = date('Y');
        $bulan = date('m');
        return view('data_obat.persediaan')->with(compact('tahun', 'bulan'));
    }

    public function list_persediaan(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $awal = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select([
                        DB::raw('MIN(tb_histori_stok_'.$inisial.'.id) as id'),
                        'tb_histori_stok_'.$inisial.'.id_obat'
                    ])
                    ->where(function($query) use($request){
                        $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $akhir = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select([
                        DB::raw('MAX(tb_histori_stok_'.$inisial.'.id) as id'),
                        'tb_histori_stok_'.$inisial.'.id_obat'
                    ])
                    ->where(function($query) use($request){
                        $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $p_plus = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select([
                        DB::raw('SUM(tb_histori_stok_'.$inisial.'.jumlah) as total_plus'),
                        'tb_histori_stok_'.$inisial.'.id_obat'
                    ])
                    ->where(function($query) use($request){
                        $query->whereRaw('id_jenis_transaksi = 9');
                        $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $p_min = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select([
                        DB::raw('SUM(tb_histori_stok_'.$inisial.'.jumlah) as total_min'),
                        'tb_histori_stok_'.$inisial.'.id_obat'
                    ])
                    ->where(function($query) use($request){
                        $query->whereRaw('id_jenis_transaksi = 10');
                        $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $penjualan = DB::table('tb_detail_nota_penjualan')
                    ->select([
                        DB::raw('SUM(jumlah-jumlah_cn) as total_jual'),
                        'id_obat'
                    ])
                    ->join('tb_nota_penjualan as a', 'a.id', 'tb_detail_nota_penjualan.id_nota')
                    ->where(function($query) use($request){
                        $query->whereRaw('tb_detail_nota_penjualan.is_deleted = 0');
                        $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                        $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $pembelian = DB::table('tb_detail_nota_pembelian')
                    ->select([
                        DB::raw('SUM(jumlah) as total_beli'),
                        'id_obat'
                    ])
                    ->join('tb_nota_pembelian as a', 'a.id', 'tb_detail_nota_pembelian.id_nota')
                    ->where(function($query) use($request){
                        $query->whereRaw('tb_detail_nota_pembelian.is_deleted = 0');
                        $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                        $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        $transfer = DB::table('tb_detail_nota_transfer_outlet')
                    ->select([
                        DB::raw('SUM(jumlah) as total_transfer'),
                        'id_obat'
                    ])
                    ->join('tb_nota_transfer_outlet as a', 'a.id', 'tb_detail_nota_transfer_outlet.id_nota')
                    ->where(function($query) use($request){
                        $query->whereRaw('tb_detail_nota_transfer_outlet.is_deleted = 0');
                        $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                        $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                        $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                    })
                    ->groupBy('id_obat');

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->select([DB::raw('@rownum  := @rownum  + 1 AS no'),
                        'tb_m_stok_harga_'.$inisial.'.id',
                        'tb_m_stok_harga_'.$inisial.'.id_obat',
                        'tb_m_stok_harga_'.$inisial.'.harga_jual',
                        'tb_m_stok_harga_'.$inisial.'.harga_beli',
                        'tb_m_stok_harga_'.$inisial.'.harga_beli_ppn',
                        'tb_m_stok_harga_'.$inisial.'.stok_awal as awalan_stok',
                        'tb_m_stok_harga_'.$inisial.'.stok_akhir as akhiran_stok',
                        'tb_m_obat.nama', 
                        'tb_m_obat.barcode',
                        'a.id as id_histori_awal',
                        'b.id as id_histori_akhir',
                        'c.total_jual',
                        'd.total_beli',
                        'e.total_transfer',
                        'f.total_plus',
                        'g.total_min'
                    ])
                    ->join('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$awal->toSql()}) as a"), 'a.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$akhir->toSql()}) as b"), 'b.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$penjualan->toSql()}) as c"), 'c.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$pembelian->toSql()}) as d"), 'd.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$transfer->toSql()}) as e"), 'e.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$p_plus->toSql()}) as f"), 'f.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->join(DB::raw("({$p_min->toSql()}) as g"), 'g.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->where(function($query) use($request, $inisial){
                        $query->whereRaw('tb_m_stok_harga_'.$inisial.'.is_deleted = 0');
                    })
                    ->orderBy('tb_m_stok_harga_'.$inisial.'.id_obat', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables  
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
               // $query->orwhere('a.nama','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('id_obat', function($data) {
            return $data->nama;
        })  
        ->editcolumn('stok_awal', function($data) {
            $awal = $data->akhiran_stok;
            if(!empty($data->id_histori_awal)) {
                $awal = $this->cari_stok(1, $data->id_histori_awal);
            }
            return $awal;
        })  
        ->editcolumn('jumlah_jual', function($data) {
            return $data->total_jual;
        })  
        ->editcolumn('jumlah_beli', function($data) {
            return $data->total_beli;
        })  
         ->editcolumn('jumlah_transfer', function($data) {
            return $data->total_transfer;
        })  
         ->editcolumn('jumlah_p_plus', function($data) {
            return $data->total_plus;
        })  
         ->editcolumn('jumlah_p_min', function($data) {
            return $data->total_min;
        })  
        ->editcolumn('stok_akhir', function($data) {
            $akhir = $data->akhiran_stok;
            if(!empty($data->akhir_stok_akhir)) {
                $akhir = $this->cari_stok(2, $data->id_histori_akhir);;
            }
            return $akhir;
        }) 
        ->editcolumn('harga_beli_ppn', function($data) {
            $harga_pokok = $data->harga_beli_ppn;
            $jumlah = $data->total_jual;
            $total = $jumlah * $data->harga_jual;
            $total_hp = $jumlah*$harga_pokok;
            $laba = $total-$total_hp;
            if($laba < 0) {
                $harga_pokok = $data->harga_beli;
            } 
            return "Rp ".number_format($harga_pokok,0);
        })  
        ->editcolumn('harga_jual', function($data) {
            return "Rp ".number_format($data->harga_jual,0);
        })
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'id_obat', 'stok_awal', 'jumlah_jual', 'jumlah_beli', 'stok_akhir', 'harga_beli_ppn', 'harga_jual', 'jumlah_p_plus', 'jumlah_p_min'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function cari_stok($act, $id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $cek = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select([
                        'tb_histori_stok_'.$inisial.'.*'
                    ])
                    ->where('id', $id)
                    ->first();
        
        if ($act == 1) {
            $arr = array(11,14,15,16,17,7,8,12,13,19,20,23,24,25,28,29,30,31,32,33);
            if(in_array($cek->id_jenis_transaksi, $arr)) {
                $new_arr = array('id_jenis_transaksi' => $cek->id_jenis_transaksi, 'stok' => $cek->stok_akhir);
                return $new_arr;
            } else {
                $new_arr = array('id_jenis_transaksi' => $cek->id_jenis_transaksi, 'stok' => $cek->stok_awal);
                return $new_arr;
            }
        } else {
            $new_arr = array('id_jenis_transaksi' => $cek->id_jenis_transaksi, 'stok' => $cek->stok_akhir);
            return $new_arr;
        }
    }

    public function reload_export_persediaan(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $obat = MasterObat::select(DB::raw('MAX(id) as id_obat_last'))->where('is_deleted', 0)->first();
        $max_id_obat = $obat->id_obat_last;

        $expiresAt = now()->addDay(1);

        $data_all = Cache::get('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_rekaps_all_'.$apotek->id);
        if(isset($data_all)) {
            $collection = collect();
            $nomor = Cache::get('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id);
            if(isset($data_all)) {
                $nomor = $nomor+1;
                $last = $nomor+999;
            } else {
                $nomor = 1;
                $last = $nomor+999;
            }

            if($nomor < $max_id_obat) {
                $rekaps = $data_all->whereBetween('id_obat', [$nomor, $last]);
                $no = Cache::get('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id);
                if(isset($data_all)) {
                    $no = $no+1;
                } else {
                    $no = 1;
                }
                foreach($rekaps as $rekap) {
                    $awal = $rekap->akhiran_stok;
                    $akhir = $rekap->akhiran_stok;
                    $first_so = 0;
                    $last_so = 0;
                    
                    if(!empty($rekap->id_histori_awal)) {
                        $cek_awal = $this->cari_stok(1, $rekap->id_histori_awal);
                        $awal = $cek_awal['stok'];
                        $awal_id_jenis_transaksi = $cek_awal['id_jenis_transaksi'];
                        if($awal_id_jenis_transaksi == 11) {
                            $first_so = 1;
                        }

                        if(empty($rekap->id_histori_akhir)) {
                            $cari_data_terakhir = DB::table('tb_histori_stok_'.$inisial.'')
                                                    ->select([
                                                        DB::raw('tb_histori_stok_'.$inisial.'.*')
                                                    ])
                                                    ->where(function($query) use($request, $rekap){
                                                        $bulan = $request->bulan-1;
                                                        $tahun = $request->tahun;
                                                        if($bulan == 0) {
                                                            $tahun = $tahun-1;
                                                            $bulan = 12;
                                                        }
                                                        $query->whereRaw('YEAR(created_at) <='.$tahun.'');
                                                        $query->whereRaw('MONTH(created_at) <= '.$bulan.'');
                                                        $query->whereRaw('id_obat ='.$rekap->id_obat.'');
                                                    })
                                                    ->orderBy('id', 'ASC')
                                                    ->first();
                            if(empty($cari_data_terakhir)) {
                                $awal = 0;
                                $akhir = 0;
                                $awal_id_jenis_transaksi = 0;
                                $akhir_id_jenis_transaksi = 0;
                            } else {
                                $awal = $cari_data_terakhir->stok_awal;
                                $akhir = $cari_data_terakhir->stok_awal;
                                $awal_id_jenis_transaksi = $cari_data_terakhir->id_jenis_transaksi;
                                $akhir_id_jenis_transaksi = $cari_data_terakhir->id_jenis_transaksi;
                                if($awal_id_jenis_transaksi == 11) {
                                    $first_so = 1;
                                    $last_so = 1;
                                }
                            }
                        } else {
                            $cek_akhir = $this->cari_stok(2, $rekap->id_histori_akhir);
                            $akhir = $cek_akhir['stok'];
                            $akhir_id_jenis_transaksi = $cek_akhir['id_jenis_transaksi'];

                            if($akhir_id_jenis_transaksi == 11) {
                                $last_so = 1;
                            }
                        }
                    } else {
                        if(empty($rekap->id_histori_akhir)) {
                            $cari_data_terakhir = DB::table('tb_histori_stok_'.$inisial.'')
                                                    ->select([
                                                        DB::raw('tb_histori_stok_'.$inisial.'.*')
                                                    ])
                                                    ->where(function($query) use($request, $rekap){
                                                        $bulan = $request->bulan-1;
                                                        $tahun = $request->tahun;
                                                        if($bulan == 0) {
                                                            $tahun = $tahun-1;
                                                            $bulan = 12;
                                                        }
                                                        $query->whereRaw('YEAR(created_at) <='.$tahun.'');
                                                        $query->whereRaw('MONTH(created_at) <= '.$bulan.'');
                                                        $query->whereRaw('id_obat ='.$rekap->id_obat.'');
                                                    })
                                                    ->orderBy('id', 'ASC')
                                                    ->first();
                            if(empty($cari_data_terakhir)) {
                                $awal = 0;
                                $akhir = 0;
                                $awal_id_jenis_transaksi = 0;
                                $akhir_id_jenis_transaksi = 0;
                            } else {
                                $awal = $cari_data_terakhir->stok_awal;
                                $akhir = $cari_data_terakhir->stok_awal;
                                $awal_id_jenis_transaksi = $cari_data_terakhir->id_jenis_transaksi;
                                $akhir_id_jenis_transaksi = $cari_data_terakhir->id_jenis_transaksi;
                                if($awal_id_jenis_transaksi == 11) {
                                    $first_so = 1;
                                    $last_so = 1;
                                }
                            }
                        } else {
                            $cek_akhir = $this->cari_stok(2, $rekap->id_histori_akhir);
                            $akhir = $cek_akhir['stok'];
                            $akhir_id_jenis_transaksi = $cek_akhir['id_jenis_transaksi'];
                            if($akhir_id_jenis_transaksi == 11) {
                                $last_so = 1;
                            }
                        }
                    }
                    
                    $harga_pokok = $rekap->harga_beli_ppn;
                    $jumlah = $rekap->total_jual;
                    $total = $jumlah * $rekap->harga_jual;
                    $total_hp = $jumlah*$harga_pokok;
                    $laba = $total-$total_hp;
                    $keterangan = 'dihitung dari harga beli + ppn';
                    if($laba < 0) {
                        $harga_pokok = $rekap->harga_beli;
                        $keterangan = 'harga beli + ppn tidak sesuai (dihitung dari harga beli). jika masih tidak sesuai, maka sesuaikan harga di data master.';
                    } 
                    $harga_pokok_cek = $harga_pokok*5;
                    if($harga_pokok == '' || $harga_pokok_cek < $rekap->harga_jual) {
                        $harga_pokok = $rekap->harga_beli;
                        $keterangan = 'harga beli + ppn tidak sesuai (dihitung dari harga beli). jika masih tidak sesuai, maka sesuaikan harga di data master.';
                    }

                     if($awal == 0) {
                        $awal = '0';
                    }

                    if($akhir == 0) {
                        $akhir = '0';
                    }

                    if($rekap->total_jual == 0) {
                        $rekap->total_jual = '0';
                    }

                    if($rekap->total_beli == 0) {
                        $rekap->total_beli = '0';
                    }

                    if($rekap->total_transfer_keluar == 0) {
                        $rekap->total_transfer_keluar = '0';
                    }

                    if($rekap->total_transfer_masuk == 0) {
                        $rekap->total_transfer_masuk = '0';
                    }

                    $collection[] = array(
                        $no, //a
                        $rekap->barcode, //b
                        $rekap->nama, //c
                        $awal, //d
                        $rekap->total_jual, //e
                        $rekap->total_retur, 
                        $rekap->total_beli,  //f
                        $rekap->total_transfer_keluar, //g
                        $rekap->total_transfer_masuk, //h
                        $rekap->total_plus, //i
                        $rekap->total_min, //j
                        $akhir, //k
                        $first_so, //n
                        $last_so, //o
                        $harga_pokok, //l
                        $rekap->harga_jual, //m
                        $keterangan //p
                    );
                    $no++;
                }

                $cek_ = Cache::get('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id);
                if(isset($cek_)) {
                    $merged = $cek_->merge($collection); 
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id, $merged, $expiresAt);
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id, $last, $expiresAt);
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id, $no, $expiresAt);
                } else {
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id, $collection, $expiresAt);
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id, $last, $expiresAt);
                    Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id);
                    Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id, $no, $expiresAt);
                }

                echo 0;
            } else {
                echo 1;
            }
        } else {
            $awal = DB::table('tb_histori_stok_'.$inisial.'')
                        ->select([
                            DB::raw('MIN(tb_histori_stok_'.$inisial.'.id) as id'),
                            'tb_histori_stok_'.$inisial.'.id_obat'
                        ])
                        ->where(function($query) use($request){
                            $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $akhir = DB::table('tb_histori_stok_'.$inisial.'')
                        ->select([
                            DB::raw('MAX(tb_histori_stok_'.$inisial.'.id) as id'),
                            'tb_histori_stok_'.$inisial.'.id_obat'
                        ])
                        ->where(function($query) use($request){
                            $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $p_plus = DB::table('tb_histori_stok_'.$inisial.'')
                        ->select([
                            DB::raw('SUM(tb_histori_stok_'.$inisial.'.jumlah) as total_plus'),
                            'tb_histori_stok_'.$inisial.'.id_obat'
                        ])
                        ->where(function($query) use($request){
                            $query->whereRaw('id_jenis_transaksi = 9');
                            $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $p_min = DB::table('tb_histori_stok_'.$inisial.'')
                        ->select([
                            DB::raw('SUM(tb_histori_stok_'.$inisial.'.jumlah) as total_min'),
                            'tb_histori_stok_'.$inisial.'.id_obat'
                        ])
                        ->where(function($query) use($request){
                            $query->whereRaw('id_jenis_transaksi = 10');
                            $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $penjualan = DB::table('tb_detail_nota_penjualan')
                        ->select([
                            DB::raw('SUM(jumlah) as total_jual'),
                            'id_obat'
                        ])
                        ->join('tb_nota_penjualan as a', 'a.id', 'tb_detail_nota_penjualan.id_nota')
                        ->where(function($query) use($request){
                            $query->whereRaw('a.is_deleted = 0');
                            $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                            $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $retur = DB::table('tb_histori_stok_'.$inisial.'')
                        ->select([
                            DB::raw('SUM(tb_histori_stok_'.$inisial.'.jumlah) as total_retur'),
                            'tb_histori_stok_'.$inisial.'.id_obat'
                        ])
                        ->where(function($query) use($request){
                            $query->whereRaw('id_jenis_transaksi = 5');
                            $query->whereRaw('YEAR(created_at) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(created_at) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $pembelian = DB::table('tb_detail_nota_pembelian')
                        ->select([
                            DB::raw('SUM(jumlah) as total_beli'),
                            'id_obat'
                        ])
                        ->join('tb_nota_pembelian as a', 'a.id', 'tb_detail_nota_pembelian.id_nota')
                        ->where(function($query) use($request){
                            $query->whereRaw('a.is_deleted = 0');
                            $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                            $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $transfer_keluar = DB::table('tb_detail_nota_transfer_outlet')
                        ->select([
                            DB::raw('SUM(jumlah) as total_transfer'),
                            'id_obat'
                        ])
                        ->join('tb_nota_transfer_outlet as a', 'a.id', 'tb_detail_nota_transfer_outlet.id_nota')
                        ->where(function($query) use($request){
                            $query->whereRaw('a.is_deleted = 0');
                            $query->whereRaw('a.id_apotek_nota = '.session('id_apotek_active').'');
                            $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $transfer_masuk = DB::table('tb_detail_nota_transfer_outlet')
                        ->select([
                            DB::raw('SUM(jumlah) as total_transfer'),
                            'id_obat'
                        ])
                        ->join('tb_nota_transfer_outlet as a', 'a.id', 'tb_detail_nota_transfer_outlet.id_nota')
                        ->where(function($query) use($request){
                            $query->whereRaw('a.is_deleted = 0');
                            $query->whereRaw('a.id_apotek_tujuan = '.session('id_apotek_active').'');
                            $query->whereRaw('YEAR(a.tgl_nota) ='.$request->tahun.'');
                            $query->whereRaw('MONTH(a.tgl_nota) ='.$request->bulan.'');
                        })
                        ->groupBy('id_obat');

            $rekaps = DB::table('tb_m_stok_harga_'.$inisial.'')
                        ->select([
                            'tb_m_stok_harga_'.$inisial.'.id',
                            'tb_m_stok_harga_'.$inisial.'.id_obat',
                            'tb_m_stok_harga_'.$inisial.'.harga_jual',
                            'tb_m_stok_harga_'.$inisial.'.harga_beli',
                            'tb_m_stok_harga_'.$inisial.'.harga_beli_ppn',
                            'tb_m_stok_harga_'.$inisial.'.stok_awal as awalan_stok',
                            'tb_m_stok_harga_'.$inisial.'.stok_akhir as akhiran_stok',
                            'tb_m_obat.nama', 
                            'tb_m_obat.barcode',
                            'a.id as id_histori_awal',
                            'b.id as id_histori_akhir',
                            'c.total_jual',
                            'd.total_beli',
                            'e.total_transfer as total_transfer_keluar',
                            'f.total_transfer as total_transfer_masuk',
                            'g.total_plus',
                            'h.total_min',
                            'i.total_retur'
                        ])
                        ->join('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$awal->toSql()}) as a"), 'a.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$akhir->toSql()}) as b"), 'b.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$penjualan->toSql()}) as c"), 'c.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$retur->toSql()}) as i"), 'i.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$pembelian->toSql()}) as d"), 'd.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$transfer_keluar->toSql()}) as e"), 'e.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$transfer_masuk->toSql()}) as f"), 'f.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$p_plus->toSql()}) as g"), 'g.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->join(DB::raw("({$p_min->toSql()}) as h"), 'h.id_obat', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                        ->where(function($query) use($request, $inisial){
                            $query->whereRaw('tb_m_stok_harga_'.$inisial.'.is_deleted = 0');
                        })
                        ->orderBy('tb_m_stok_harga_'.$inisial.'.id_obat', 'ASC')
                        ->get();

            Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_rekaps_all_'.$apotek->id);
            Cache::put('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_rekaps_all_'.$apotek->id, $rekaps, $expiresAt);
            echo 0;
        }   
    }

    public function clear_cache_persediaan(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_data_'.$apotek->id);
        Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id);
        Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_rekaps_all_'.$apotek->id);
        Cache::forget('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_last_no_'.$apotek->id);
    }

    public function export_persediaan(Request $request) 
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $collection = Cache::get('persediaan_'.$request->tahun.'_'.$request->bulan.'_'.Auth::user()->id.'_data_all_'.$apotek->id);

        $now = date('YmdHis'); // WithColumnFormatting
        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return [
                                'No', // a
                                'Barcode', // b 
                                'Nama Obat',  //c
                                'Stok Awal', //d
                                'Penjualan', //e
                                'Retur', //
                                'Pembelian', //f
                                'T.Keluar', //g
                                'T.Masuk', //h
                                'P.Plus', //i
                                'P.Min', //j
                                'Stok Akhir',  //k
                                'Firt SO', //n
                                'Last SO', //o
                                'Harga Pokok', //l
                                'Harga Jual', //m
                                'Keterangan' //p
                            ];
                    } 

                    /*public function columnFormats(): array
                    {
                        return [
                            'F' => NumberFormat::FORMAT_NUMBER,
                            'G' => NumberFormat::FORMAT_NUMBER,
                        ];
                    }*/

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 15,
                            'C' => 45,
                            'D' => 15,
                            'E' => 15,
                            'F' => 15,
                            'G' => 15,
                            'H' => 20,
                            'I' => 20,
                            'J' => 20,
                            'K' => 20,
                            'L' => 15,
                            'M' => 10,
                            'N' => 10,
                            'O' => 15,
                            'P' => 15,
                            'Q' => 70,
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'D'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'E'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'I'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'J'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'K'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'M'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'O'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'P'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Persediaan_".$apotek->nama_singkat."_".$request->tahun."_".$request->bulan."_".$now.".xlsx");
    }

    public function reload_data_pembelian() {
        $det_pembelians = DB::table('tb_detail_nota_pembelian') 
                                ->select([
                                    'tb_detail_nota_pembelian.*', 
                                    'tb_nota_pembelian.ppn',
                                    DB::raw('CAST(
                                      (
                                        (jumlah * harga_beli) - (
                                          diskon + (
                                            diskon_persen / 100 * (jumlah * harga_beli)
                                          )
                                        )
                                      ) AS DECIMAL (16, 0)
                                    ) AS total_new'),
                                    DB::raw('CAST(total_harga AS DECIMAL (16, 0)) AS total_harga_')
                                ])
                                ->join('tb_nota_pembelian', 'tb_nota_pembelian.id', '=', 'tb_detail_nota_pembelian.id_nota');

        $data_ = DB::table(DB::raw("({$det_pembelians->toSql()}) as x"))
            ->select(['*'])
            ->whereRaw('total_new > total_harga_')
            ->get();

        $i = 0;
        foreach ($data_ as $key => $val) {
            $hb_ppn = $val->harga_beli+(($val->ppn/100)*$val->harga_beli);
            DB::table('tb_detail_nota_pembelian')
                ->where('id', $val->id)
                ->update(['total_harga' => $val->total_new, 'harga_beli_ppn' => $hb_ppn]);
            $i++;
        }

        echo "reload data sebanyak ".$i;
    }


    public function reload_data_histori() {
        $id_apotek = 1;
        $apotek = MasterApotek::find($id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
        $det_historis = DB::table('tb_histori_stok_'.$inisial.'')->whereIn('id_jenis_transaksi', [1, 2, 3, 4, 14, 15, 16, 17])->get();
        $i = 0;
        foreach ($det_historis as $key => $val) {
            DB::table('tb_histori_all_'.$inisial.'')
                ->where('id_obat', $val->id_obat)
                ->where('id_jenis_transaksi', $val->id_jenis_transaksi)
                ->where('id_transaksi', $val->id_transaksi)
                ->whereDate('created_at', $val->created_at)
                ->update(['stok_awal' => $val->stok_akhir, 'stok_akhir' => $val->stok_akhir]);
            $i++;
        }
        echo "reload data sebanyak ".$i;
    }

    public function sycn_harga_obat_tahap_satu(Request $request, $id) {
        $obat = MasterObat::select(['id'])->orderBy('id', 'DESC')->first();
        $last_id_obat = $obat->id;
        $last_id_obat_ex = 0;
        $id_apotek = $id;
        $cek = DB::table('tb_bantu_update')->orderBy('id', 'DESC')->first();
        if(!empty($cek)) {
            $last_id_obat_ex = $cek->last_id_obat_after;
            if($last_id_obat_ex >= $last_id_obat) {
                $id_apotek = $cek->id_apotek+1;
            } else {
                $id_apotek = $cek->id_apotek;
            }
            $apotek = MasterApotek::find($cek->id_apotek);
            $inisial = strtolower($apotek->nama_singkat);
        } else {
            $apotek = MasterApotek::find($id_apotek);
            $inisial = strtolower($apotek->nama_singkat);
        }

        $last_id_obat_ex = $last_id_obat_ex+1;
        $last_id_obat_after = $last_id_obat_ex+200-1;
        DB::table('tb_bantu_update')
            ->insert(['last_id_obat_before' => $last_id_obat_ex, 'last_id_obat_after' => $last_id_obat_after, 'id_apotek' => $id_apotek]);
        
        $data = DB::table('tb_m_stok_harga_'.$inisial.'')->whereBetween('id_obat', [$last_id_obat_ex, $last_id_obat_after])->get();
        $i=0;
        $last_id_obat_after = 0;
        $data_ = array();
        foreach ($data as $key => $val) {
            # data pembelian obat keseluruhan
            $det_pembelians = DB::table('tb_detail_nota_pembelian') 
                                ->select(['tb_detail_nota_pembelian.*', 'tb_nota_pembelian.ppn'])
                                ->join('tb_nota_pembelian', 'tb_nota_pembelian.id', '=', 'tb_detail_nota_pembelian.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->whereDate('created_at', '<', $now)
                                ->get();
            
            foreach ($det_pembelians as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = $val->harga_beli;
                $new_arr['ppn'] = $val->ppn;
                if($val->ppn != null AND $val->ppn > 0) {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli+(($val->ppn/100)*$val->harga_beli);
                } else {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli;
                }
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 2;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = $val->id_batch;
                $new_arr['ed'] = $val->tgl_batch;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data pembelian obat dihapus
            $det_pembelian_hapuss = DB::table('tb_detail_nota_pembelian')
                                ->select(['tb_detail_nota_pembelian.*', 'tb_nota_pembelian.ppn'])
                                ->join('tb_nota_pembelian', 'tb_nota_pembelian.id', '=', 'tb_detail_nota_pembelian.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_nota_pembelian.is_deleted', 1)
                                ->get();

            foreach ($det_pembelian_hapuss as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = $val->harga_beli;
                $new_arr['ppn'] = $val->ppn;
                if($val->ppn != null AND $val->ppn > 0) {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli+(($val->ppn/100)*$val->harga_beli);
                } else {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli;
                }
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 14;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = $val->id_batch;
                $new_arr['ed'] = $val->tgl_batch;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data pembelian obat retur
           /* $det_pembelian_returs = DB::table('tb_detail_nota_pembelian')
                                ->select(['tb_detail_nota_pembelian.*', 'tb_nota_pembelian.ppn'])
                                ->join('tb_nota_pembelian', 'tb_nota_pembelian.id', '=', 'tb_detail_nota_pembelian.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_detail_nota_pembelian.is_retur', 1)
                                ->get();

            foreach ($det_pembelian_returs as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = $val->harga_beli;
                $new_arr['ppn'] = $val->ppn;
                if($val->ppn != null AND $val->ppn > 0) {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli+(($val->ppn/100)*$val->harga_beli);
                } else {
                    $new_arr['harga_beli_ppn'] = $val->harga_beli;
                }
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 26;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = $val->id_batch;
                $new_arr['ed'] = $val->tgl_batch;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                $data_[] = $new_arr;
            }*/

            // -----------------------------------------------------------------------------------
            # data penjualan obat keseluruhan
            $det_penjualans = DB::table('tb_detail_nota_penjualan')
                                ->select(['tb_detail_nota_penjualan.*'])
                                ->join('tb_nota_penjualan', 'tb_nota_penjualan.id', '=', 'tb_detail_nota_penjualan.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->get();

            foreach ($det_penjualans as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = $val->harga_jual;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 1;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data penjualan obat dihapus
            $det_penjualan_hapuss = DB::table('tb_detail_nota_penjualan')
                                ->select(['tb_detail_nota_penjualan.*'])
                                ->join('tb_nota_penjualan', 'tb_nota_penjualan.id', '=', 'tb_detail_nota_penjualan.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_nota_penjualan.is_deleted', 1)
                                ->get();

            foreach ($det_penjualan_hapuss as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = $val->harga_jual;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 15;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data penjualan obat retur
            /*$det_penjualan_returs = DB::table('tb_detail_nota_penjualan')
                                ->select(['tb_detail_nota_penjualan.*'])
                                ->join('tb_nota_penjualan', 'tb_nota_penjualan.id', '=', 'tb_detail_nota_penjualan.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_detail_nota_penjualan.is_cn', 1)
                                ->get();

            foreach ($det_penjualan_returs as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = $val->harga_jual;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 5;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                $data_[] = $new_arr;
            }*/

            // -----------------------------------------------------------------------------------
            # data transfer masuk obat keseluruhan
            $det_transfer_masuks = DB::table('tb_detail_nota_transfer_outlet')
                                ->select(['tb_detail_nota_transfer_outlet.*'])
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_tujuan', $apotek->id)
                                ->get();

            foreach ($det_transfer_masuks as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = $val->harga_outlet;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 3;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data transfer masuk obat dihapus
            $det_transfer_masuk_hapuss = DB::table('tb_detail_nota_transfer_outlet')
                                ->select(['tb_detail_nota_transfer_outlet.*'])
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_tujuan', $apotek->id)
                                ->where('tb_nota_transfer_outlet.is_deleted', 1)
                                ->get();

            foreach ($det_transfer_masuk_hapuss as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = $val->harga_outlet;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 16;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            /*# data transfer masuk obat retur
            $det_transfer_masuk_returs = DB::table('tb_detail_nota_transfer_outlet')
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_tujuan', $apotek->id)
                                ->where('tb_nota_transfer_outlet.is_retur', 1)
                                ->get();*/

            // -----------------------------------------------------------------------------------
            # data transfer keluar obat keseluruhan
            $det_transfer_keluars = DB::table('tb_detail_nota_transfer_outlet')
                                ->select(['tb_detail_nota_transfer_outlet.*'])
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->get();

            foreach ($det_transfer_keluars as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = $val->harga_outlet;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 4;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            # data transfer keluar obat dihapus
            $det_transfer_keluar_hapuss = DB::table('tb_detail_nota_transfer_outlet')
                                ->select(['tb_detail_nota_transfer_outlet.*'])
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_nota_transfer_outlet.is_deleted', 1)
                                ->get();

            foreach ($det_transfer_masuk_hapuss as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = $val->harga_outlet;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = 0;
                $new_arr['stok_akhir'] = 0;
                $new_arr['id_jenis_transaksi'] = 17;
                $new_arr['id_transaksi'] = $val->id;
                $new_arr['batch'] = null;
                $new_arr['ed'] = null;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

           /* # data transfer masuk obat retur
            $det_transfer_keluar_returs = DB::table('tb_detail_nota_transfer_outlet')
                                ->join('tb_nota_transfer_outlet', 'tb_nota_transfer_outlet.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where('id_obat', $val->id_obat)
                                ->where('id_apotek_nota', $apotek->id)
                                ->where('tb_nota_transfer_outlet.is_retur', 1)
                                ->get();*/


            # history lain-lain 
            $det_historis = DB::table('tb_histori_stok_'.$inisial.'')->where('id_obat', $val->id_obat)->whereNotIn('id_jenis_transaksi', [1, 2, 3, 4, 14, 15, 16, 17])->get();

            foreach ($det_historis as $key => $val) {
                $new_arr = array();
                $new_arr['id_obat'] = $val->id_obat;
                $new_arr['harga_beli'] = null;
                $new_arr['ppn'] = null;
                $new_arr['harga_beli_ppn'] = null;
                $new_arr['harga_jual'] = null;
                $new_arr['harga_transfer'] = null;
                $new_arr['jumlah'] = $val->jumlah;
                $new_arr['stok_awal'] = $val->stok_awal;
                $new_arr['stok_akhir'] = $val->stok_akhir;
                $new_arr['id_jenis_transaksi'] = $val->id_jenis_transaksi;
                $new_arr['id_transaksi'] = $val->id_transaksi;
                $new_arr['batch'] = $val->batch;
                $new_arr['ed'] = $val->ed;
                $new_arr['created_at'] = $val->created_at; 
                $new_arr['created_by'] = $val->created_by;
                array_push($data_, $new_arr);
            }

            $i++;
            $last_id_obat_after = $val->id_obat;
        }

        if($i > 0) {
            DB::table('tb_histori_all_lv')
                ->insert($data_);

            DB::table('tb_bantu_update')
                ->insert(['last_id_obat_before' => $last_id_obat_ex, 'last_id_obat_after' => $last_id_obat_after]);

            echo 1;
        } else {
            echo 0;
        }
    }

    public function histori_all($id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $id)->first();
        $obat = MasterObat::find($id);
        $jenis_transasksis      = MasterJenisTransaksi::pluck('nama', 'id');
        $jenis_transasksis->prepend('-- Pilih Jenis Transaksi --','');

        return view('data_obat.histori_all')->with(compact('obat', 'stok_harga', 'jenis_transasksis'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 21/06/2020
        =======================================================================================
    */
    public function list_data_histori_all(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_all_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_all_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_all_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_all_'.$inisial.'.id_jenis_transaksi')
                ->where(function($query) use($request, $inisial){
                    $query->where('tb_histori_all_'.$inisial.'.id_obat', $request->id_obat);
                    $query->where('tb_histori_all_'.$inisial.'.id_jenis_transaksi','LIKE',($request->id_jenis_transaksi > 0 ? $request->id_jenis_transaksi : '%'.$request->id_jenis_transaksi.'%'));

                    if($request->tgl_awal != "") {
                        $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','>=', $tgl_awal);
                    }

                    if($request->tgl_akhir != "") {
                        $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','<=', $tgl_akhir);
                    }

                    $query->whereYear('tb_histori_all_'.$inisial.'.created_at', session('id_tahun_active'));
                })
                ->orderBy('created_at', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_all_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_all_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            return date('d-m-Y', strtotime($data->created_at)); 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            $string = '';
            $id_nota = ''; 
            $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
            $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
            $data_tf_keluar_ = array(4, 8, 17);
            $data_penjualan_ = array(1, 5, 6, 15);
            $data_penyesuaian_ = array(9,10);
            $data_so_ = array(11);
            $data_po_ = array(18, 19, 20, 21);
            $data_td_ = array(22, 23, 24, 25);
            if (in_array($data->id_jenis_transaksi, $data_pembelian_)) {
                $check = TransaksiPembelianDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_masuk_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Masuk dari '.$check->nota->apotek_asal->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_keluar_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Tujuan ke '.$check->nota->apotek_tujuan->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_penjualan_)) {
                $check = TransaksiPenjualanDetail::find($data->id_transaksi);
                if($check->nota->is_kredit == 1) {
                    $string = '<b>Vendor : '.$check->nota->vendor->nama.'</b>';
                } else {
                    $string = '<b>Member : - </b>';
                }
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_po_)) {
                $check = TransaksiPODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_td_)) {
                $check = TransaksiTDDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            }

            if($string != '') {
                $string = '<br>'.$string;
            }

            return $data->nama_transaksi.$string.'<br>'.'IDdet : '.$data->id_transaksi.$id_nota; 
        }) 
        ->editcolumn('harga', function($data){
            $hb = $data->harga_beli;
            if($hb == null) {
                $hb = '-';
            }

            $ppn= $data->ppn.'%';
            if($data->ppn == null) {
                $ppn = '-';
            }

            $harga_beli_ppn= $data->harga_beli_ppn;
            if($harga_beli_ppn == null) {
                $harga_beli_ppn = '-';
            }

            $harga_jual= $data->harga_jual;
            if($harga_jual == null) {
                $harga_jual = '-';
            }

            $harga_transfer= $data->harga_transfer;
            if($harga_transfer == null) {
                $harga_transfer = '-';
            }

            $string = '<span style="font-size:9pt;">';
            $string.= 'HB     : '.$hb.'<br>';
            $string.= 'PPN    : '.$ppn.'<br>';
            $string.= 'HB+PPN : '.$harga_beli_ppn.'<br>';
            $string.= 'HJ     : '.$harga_jual.'<br>';
            $string.= 'HT     : '.$harga_transfer;
            $string.= '</span>';
            return $string; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by', 'harga'])
        ->make(true);  
    }

    public function edit_harga_beli($id) {
        $obat = MasterObat::find($id);

        return view('data_obat.edit_harga_beli')->with(compact('obat'));
    }

    public function list_edit_harga_beli(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_all_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_all_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_all_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_all_'.$inisial.'.id_jenis_transaksi')
                ->where(function($query) use($request, $inisial){
                    $query->where('tb_histori_all_'.$inisial.'.id_obat', $request->id_obat);
                    $query->whereIn('tb_histori_all_'.$inisial.'.id_jenis_transaksi', [2, 12, 13, 14, 26, 27, 30, 31]);
                    if($request->tgl_awal != "") {
                        $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','>=', $tgl_awal);
                    }

                    if($request->tgl_akhir != "") {
                        $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','<=', $tgl_akhir);
                    }

                    $query->whereYear('tb_histori_all_'.$inisial.'.created_at', session('id_tahun_active'));
                })
                ->orderBy('created_at', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_all_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_all_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            $btn = '<span class="label" onClick="gunakan_hb('.$data->id.', '.$data->id_obat.', '.$data->harga_beli.', '.$data->harga_beli_ppn.')" data-toggle="tooltip" data-placement="top" title="Gunakan ini" style="font-size:10pt;color:#0097a7;">[Terapkan]</span>';
            return date('d-m-Y', strtotime($data->created_at)).'<br>'.$btn; 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            $string = '';
            $id_nota = ''; 
            $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
            $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
            $data_tf_keluar_ = array(4, 8, 17);
            $data_penjualan_ = array(1, 5, 6, 15);
            $data_penyesuaian_ = array(9,10);
            $data_so_ = array(11);
            $data_po_ = array(18, 19, 20, 21);
            $data_td_ = array(22, 23, 24, 25);
            if (in_array($data->id_jenis_transaksi, $data_pembelian_)) {
                $check = TransaksiPembelianDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_masuk_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Masuk dari '.$check->nota->apotek_asal->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_keluar_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Tujuan ke '.$check->nota->apotek_tujuan->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_penjualan_)) {
                $check = TransaksiPenjualanDetail::find($data->id_transaksi);
                if($check->nota->is_kredit == 1) {
                    $string = '<b>Vendor : '.$check->nota->vendor->nama.'</b>';
                } else {
                    $string = '<b>Member : - </b>';
                }
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_po_)) {
                $check = TransaksiPODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_td_)) {
                $check = TransaksiTDDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            }

            if($string != '') {
                $string = '<br>'.$string;
            }

            return $data->nama_transaksi.$string.'<br>'.'IDdet : '.$data->id_transaksi.$id_nota; 
        }) 
        ->editcolumn('harga', function($data){
            $hb = $data->harga_beli;
            if($hb == null) {
                $hb = '-';
            }

            $ppn= $data->ppn.'%';
            if($data->ppn == null) {
                $ppn = '-';
            }

            $harga_beli_ppn= $data->harga_beli_ppn;
            if($harga_beli_ppn == null) {
                $harga_beli_ppn = '-';
            }

            $harga_jual= $data->harga_jual;
            if($harga_jual == null) {
                $harga_jual = '-';
            }

            $harga_transfer= $data->harga_transfer;
            if($harga_transfer == null) {
                $harga_transfer = '-';
            }

            $string = '<span style="font-size:9pt;">';
            $string.= 'HB     : '.$hb.'<br>';
            $string.= 'PPN    : '.$ppn.'<br>';
            $string.= 'HB+PPN : '.$harga_beli_ppn.'<br>';
            $string.= 'HJ     : '.$harga_jual.'<br>';
            $string.= 'HT     : '.$harga_transfer;
            $string.= '</span>';
            return $string; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by', 'harga', 'created_at'])
        ->make(true);  
    }

    public function gunakan_hb(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id_obat)->first();

        if(DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id_obat', $request->id_obat)
                    ->update(['harga_beli' => $request->hb, 'harga_beli_ppn' => $request->hb_ppn, 'id_histori_hb' => $request->id, 'id_histori_hb_ppn' => $request->id])){
            echo 1;
        } else {
            echo 0;
        }
    }


    public function edit_harga_beli_ppn($id) {
        $obat = MasterObat::find($id);

        return view('data_obat.edit_harga_beli_ppn')->with(compact('obat'));
    }

    public function list_edit_harga_beli_ppn(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_all_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_all_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_all_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_all_'.$inisial.'.id_jenis_transaksi')
                ->where(function($query) use($request, $inisial){
                    $query->where('tb_histori_all_'.$inisial.'.id_obat', $request->id_obat);
                    $query->whereIn('tb_histori_all_'.$inisial.'.id_jenis_transaksi', [2, 12, 13, 14, 26, 27, 30, 31, 3, 7, 16, 28, 29, 32, 33]);
                    $query->whereYear('tb_histori_all_'.$inisial.'.created_at', session('id_tahun_active'));
                })
                ->orderBy('created_at', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_all_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_all_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            $btn = '<span class="label" onClick="gunakan_hb_ppn('.$data->id.', '.$data->id_obat.', '.$data->id_jenis_transaksi.', '.$data->harga_transfer.', '.$data->harga_beli_ppn.')" data-toggle="tooltip" data-placement="top" title="Gunakan ini" style="font-size:10pt;color:#0097a7;">[Terapkan]</span>';
            return date('d-m-Y', strtotime($data->created_at)).'<br>'.$btn; 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            $string = '';
            $id_nota = ''; 
            $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
            $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
            $data_tf_keluar_ = array(4, 8, 17);
            $data_penjualan_ = array(1, 5, 6, 15);
            $data_penyesuaian_ = array(9,10);
            $data_so_ = array(11);
            $data_po_ = array(18, 19, 20, 21);
            $data_td_ = array(22, 23, 24, 25);
            if (in_array($data->id_jenis_transaksi, $data_pembelian_)) {
                $check = TransaksiPembelianDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_masuk_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Masuk dari '.$check->nota->apotek_asal->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_keluar_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Tujuan ke '.$check->nota->apotek_tujuan->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_penjualan_)) {
                $check = TransaksiPenjualanDetail::find($data->id_transaksi);
                if($check->nota->is_kredit == 1) {
                    $string = '<b>Vendor : '.$check->nota->vendor->nama.'</b>';
                } else {
                    $string = '<b>Member : - </b>';
                }
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_po_)) {
                $check = TransaksiPODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_td_)) {
                $check = TransaksiTDDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            }

            if($string != '') {
                $string = '<br>'.$string;
            }

            return $data->nama_transaksi.$string.'<br>'.'IDdet : '.$data->id_transaksi.$id_nota; 
        }) 
        ->editcolumn('harga', function($data){
            $hb = $data->harga_beli;
            if($hb == null) {
                $hb = '-';
            }

            $ppn= $data->ppn.'%';
            if($data->ppn == null) {
                $ppn = '-';
            }

            $harga_beli_ppn= $data->harga_beli_ppn;
            if($harga_beli_ppn == null) {
                $harga_beli_ppn = '-';
            }

            $harga_jual= $data->harga_jual;
            if($harga_jual == null) {
                $harga_jual = '-';
            }

            $harga_transfer= $data->harga_transfer;
            if($harga_transfer == null) {
                $harga_transfer = '-';
            }

            $string = '<span style="font-size:9pt;">';
            $string.= 'HB     : '.$hb.'<br>';
            $string.= 'PPN    : '.$ppn.'<br>';
            $string.= 'HB+PPN : '.$harga_beli_ppn.'<br>';
            $string.= 'HJ     : '.$harga_jual.'<br>';
            $string.= 'HT     : '.$harga_transfer;
            $string.= '</span>';
            return $string; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by', 'harga', 'created_at'])
        ->make(true);  
    }

    public function gunakan_hb_ppn(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id_obat)->first();

        $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
        $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
        if (in_array($request->id_jenis_transaksi, $data_pembelian_)) {
            $harga = $request->hb_ppn;
        } else if (in_array($request->id_jenis_transaksi, $data_tf_masuk_)) {
            $harga = $request->ht;
        } 


        if(DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id_obat', $request->id_obat)
                    ->update(['harga_beli_ppn' => $harga, 'id_histori_hb_ppn' => $request->id])){
            echo 1;
        } else {
            echo 0;
        }
    }

    public function edit_harga_jual($id) {
        $obat = MasterObat::find($id);
        return view('data_obat.edit_harga_jual')->with(compact('obat'));
    }

    public function list_edit_harga_jual(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_all_'.$inisial.'')->select([
                    DB::raw('@rownum  := @rownum  + 1 AS no'), 
                    'tb_histori_all_'.$inisial.'.*', 
                    'users.nama as oleh',
                    'tb_m_jenis_transaksi.nama as nama_transaksi',
                    'tb_m_jenis_transaksi.act'
                ])
                ->join('users', 'users.id', '=', 'tb_histori_all_'.$inisial.'.created_by')
                ->join('tb_m_jenis_transaksi', 'tb_m_jenis_transaksi.id', '=', 'tb_histori_all_'.$inisial.'.id_jenis_transaksi')
                ->where(function($query) use($request, $inisial){
                    $query->where('tb_histori_all_'.$inisial.'.id_obat', $request->id_obat);
                    $query->whereIn('tb_histori_all_'.$inisial.'.id_jenis_transaksi', [1, 5, 6, 15]);
                    if($request->tgl_awal != "") {
                        $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','>=', $tgl_awal);
                    }

                    if($request->tgl_akhir != "") {
                        $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                        $query->whereDate('tb_histori_all_'.$inisial.'.created_at','<=', $tgl_akhir);
                    }

                    $query->whereYear('tb_histori_all_'.$inisial.'.created_at', session('id_tahun_active'));
                })
                ->orderBy('created_at', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir, $inisial){
            $query->where(function($query) use($request, $inisial){
                $query->orwhere('tb_histori_all_'.$inisial.'.created_at','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('tb_histori_all_'.$inisial.'.batch','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('created_at', function($data){
            $btn = '<span class="label" onClick="gunakan_hj('.$data->id.', '.$data->id_obat.', '.$data->harga_jual.')" data-toggle="tooltip" data-placement="top" title="Gunakan ini" style="font-size:10pt;color:#0097a7;">[Terapkan]</span>';
            return date('d-m-Y', strtotime($data->created_at)).'<br>'.$btn; 
        }) 
        ->editcolumn('id_jenis_transaksi', function($data){
            $string = '';
            $id_nota = ''; 
            $data_pembelian_ = array(2, 12, 13, 14, 26, 27, 30, 31);
            $data_tf_masuk_ = array(3, 7, 16, 28, 29, 32, 33);
            $data_tf_keluar_ = array(4, 8, 17);
            $data_penjualan_ = array(1, 5, 6, 15);
            $data_penyesuaian_ = array(9,10);
            $data_so_ = array(11);
            $data_po_ = array(18, 19, 20, 21);
            $data_td_ = array(22, 23, 24, 25);
            if (in_array($data->id_jenis_transaksi, $data_pembelian_)) {
                $check = TransaksiPembelianDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>'.$check->nota->suplier->nama.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_masuk_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Masuk dari '.$check->nota->apotek_asal->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_tf_keluar_)) {
                $check = TransaksiTODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
                $string = '<b>Tujuan ke '.$check->nota->apotek_tujuan->nama_singkat.'</b>';
            } else if (in_array($data->id_jenis_transaksi, $data_penjualan_)) {
                $check = TransaksiPenjualanDetail::find($data->id_transaksi);
                if($check->nota->is_kredit == 1) {
                    $string = '<b>Vendor : '.$check->nota->vendor->nama.'</b>';
                } else {
                    $string = '<b>Member : - </b>';
                }
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_po_)) {
                $check = TransaksiPODetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            } else if (in_array($data->id_jenis_transaksi, $data_td_)) {
                $check = TransaksiTDDetail::find($data->id_transaksi);
                $id_nota = ' | IDNota : '.$check->nota->id;
            }

            if($string != '') {
                $string = '<br>'.$string;
            }

            return $data->nama_transaksi.$string.'<br>'.'IDdet : '.$data->id_transaksi.$id_nota; 
        }) 
        ->editcolumn('harga', function($data){
            $hb = $data->harga_beli;
            if($hb == null) {
                $hb = '-';
            }

            $ppn= $data->ppn.'%';
            if($data->ppn == null) {
                $ppn = '-';
            }

            $harga_beli_ppn= $data->harga_beli_ppn;
            if($harga_beli_ppn == null) {
                $harga_beli_ppn = '-';
            }

            $harga_jual= $data->harga_jual;
            if($harga_jual == null) {
                $harga_jual = '-';
            }

            $harga_transfer= $data->harga_transfer;
            if($harga_transfer == null) {
                $harga_transfer = '-';
            }

            $string = '<span style="font-size:9pt;">';
            $string.= 'HB     : '.$hb.'<br>';
            $string.= 'PPN    : '.$ppn.'<br>';
            $string.= 'HB+PPN : '.$harga_beli_ppn.'<br>';
            $string.= 'HJ     : '.$harga_jual.'<br>';
            $string.= 'HT     : '.$harga_transfer;
            $string.= '</span>';
            return $string; 
        }) 
        ->editcolumn('masuk', function($data){
            $masuk = 0;
            if($data->act == 1) {
                $masuk = $data->jumlah;
            } 
            return $masuk; 
        }) 
        ->editcolumn('keluar', function($data){
            $keluar = 0;
            if($data->act == 2) {
                $keluar = $data->jumlah;
            } 
            return $keluar;  
        }) 
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->editcolumn('batch', function($data){
            $batch = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $batch;
        }) 
        ->editcolumn('ed', function($data){
            $ed = '-';
            $data_ = array(2, 12, 13, 26, 27);
            if (in_array($data->id_jenis_transaksi, $data_))
            {
                $batch = $data->batch;
            }
            return $ed;
        }) 
        ->editcolumn('created_by', function($data){
            if(strlen($data->oleh) > 15) {
                $trimstring = substr($data->oleh, 0, 15);
                $oleh = 'by '.$trimstring;
            } else {
                $oleh = 'by '.$data->oleh;
            }

            return strtolower($oleh);
        }) 
        ->rawColumns(['craeted_at', 'id_jenis_transaksi', 'masuk', 'keluar', 'stok_akhir', 'batch', 'ed', 'created_by', 'harga', 'created_at'])
        ->make(true);  
    }

    public function gunakan_hj(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id_obat)->first();

        if(DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id_obat', $request->id_obat)
                    ->update(['harga_jual' => $request->hj, 'id_histori_hj' => $request->id])){
            echo 1;
        } else {
            echo 0;
        }
    }

    public function import_data(Request $request) {
        return view('data_obat._import_data');
    }

    public function import_obat_to_excel(Request $request)
    {
        if(Input::hasFile('import_file')){
            $path = Input::file('import_file')->getRealPath();
            Excel::import(new GolonganObatImport, $path);
           // $data = Excel::load($path, function($reader) {})->get();
            
        }
        session()->flash('success', 'Import data reviewer berhasil!');
        return redirect('/data_obat');
    }

    public function perbaikan_data(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $data_ = DB::table('tb_histori_stok_'.$inisial.'')
                    ->where('id_obat', $request->id_obat)
                    ->orderBy('id', 'ASC')
                    ->get();

        # hapus data yang tidak perlu
        $id_skip = array();
        foreach ($data_ as $key => $val) {
            if (!in_array($val->id, $id_skip)) {
                $cek_ = DB::table('tb_histori_stok_'.$inisial.'')
                            ->where('id_obat', $request->id_obat)
                            ->where('id_jenis_transaksi', $val->id_jenis_transaksi)
                            ->where('id_transaksi', $val->id_transaksi)
                            ->where('jumlah', $val->jumlah)
                            ->where('id', '!=',  $val->id)
                            ->get();
                if(count($cek_) > 0) {
                    foreach ($cek_ as $x => $obj) {
                        DB::table('tb_histori_stok_'.$inisial.'')
                            ->where('id', $obj->id)
                            ->delete();
                        $id_skip[] = $obj->id;
                    }
                }
            }
        }

        # sesuaikan datanya
        $data_ = DB::table('tb_histori_stok_'.$inisial.'')
                    ->select(['tb_histori_stok_'.$inisial.'.*', 'a.act'])
                    ->join('tb_m_jenis_transaksi as a', 'a.id', '=', 'tb_histori_stok_'.$inisial.'.id_jenis_transaksi')
                    ->where('id_obat', $request->id_obat)
                    ->orderBy('id', 'ASC')
                    ->get();

        $stok_akhir = 0;
        $stok_awal = 0;
        $i=0;
        foreach ($data_ as $key => $val) {
            $i++;
            if($i != 0) {
                if($val->act == 1) {
                    # kurang stok
                    $jumlah = $val->jumlah;
                    $stok_awal = $stok_akhir;
                    $stok_akhir_new = $stok_akhir+$val->jumlah;

                    if($val->id_jenis_transaksi == 7) {
                        # Revisi Transfer Masuk
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [3,7])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir+0;
                        }
                    } else if($val->id_jenis_transaksi == 13) {
                        # Revisi Pembelian (Minus)
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [2,13])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir+0;
                        }
                    } else if($val->id_jenis_transaksi == 20)  {
                        # Revisi Penjualan Operasional (Minus)
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [18,20])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir+0;
                        }
                    }
                } else if($val->act == 2) {
                    # tambah stok
                    $jumlah = $val->jumlah;
                    $stok_awal = $stok_akhir;
                    $stok_akhir_new = $stok_akhir-$val->jumlah;

                    if($val->id_jenis_transaksi == 8) {
                        # Revisi Transfer keluar
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [3,8])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir-0;
                        }
                    } else if($val->id_jenis_transaksi == 12) {
                        # Revisi Pembelian (Plus)
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [2,12])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                           // $jumlah = 0;
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir-0;
                        }
                    } else if($val->id_jenis_transaksi == 19) {
                        # Revisi Penjualan Operasional (Plus)
                        $last_ = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [18,19])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->first();
                        if($last_->jumlah == $val->jumlah) {
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir-0;
                        }
                    } else if($val->id_jenis_transaksi == 16) {
                        # Hapus Transfer Masuk (Double)
                        # cek ada gk history transaksi yang masuk dengan id tersebut
                        $cek = DB::table('tb_histori_stok_'.$inisial.'')->where('id_transaksi', $val->id_transaksi)->whereIn('id_jenis_transaksi', [7, 3])->orderBy('id', 'DESC')->where('id', '!=',  $val->id)->count();
                        if($cek < 1) {
                            $jumlah = $val->jumlah;
                            $stok_awal = $stok_akhir;
                            $stok_akhir_new = $stok_akhir+$val->jumlah;
                            DB::table('tb_histori_stok_'.$inisial.'')
                            ->where('id', $val->id)
                            ->delete();
                        }
                    }
                } else {
                    $jumlah = $val->jumlah;
                    $stok_awal = $stok_akhir;
                    $stok_akhir_new = $val->jumlah;
                }

                DB::table('tb_histori_stok_'.$inisial.'')->where('id', $val->id)->update(['stok_akhir' => $stok_akhir_new, 'stok_awal' => $stok_awal, 'jumlah' => $jumlah]);
                $stok_akhir = $stok_akhir_new;
                $stok_awal = $stok_awal;
            } else {
                $jumlah = $val->jumlah;
                $stok_akhir = $val->stok_akhir;
                $stok_awal = $val->stok_awal;
            }
        }

        if($i > 0) {
            DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id_obat)->update(['stok_akhir' => $stok_akhir, 'stok_awal' => $stok_awal]);
        }

        echo $i;
    }

    public function set_status_harga_outlet(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        DB::table('tb_m_stok_harga_'.$inisial.'')
                ->where('id', $request->id)
                ->update(['is_status_harga' => $request->nilai, 'status_harga_by' => Auth::id(), 'status_harga_at' => date('Y-m-d H:i:s')]);

        echo 1;
    }

    public function reload_hpp_from_another_outlet() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
      /*  $obats = MasterObat::where('is_deleted', 0)->get();*/
        $obats = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('harga_beli_ppn',0)
                    ->get();
        $i = 0;
        foreach ($obats as $key => $val) {
                $cek2 = DB::table('tb_m_stok_harga_pg')
                        ->where('id_obat', $val->id_obat)
                        ->first();

                if(!empty($cek2)) {
                    DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->where('id_obat', $val->id_obat)
                    ->update(['harga_beli_ppn' => $cek2->harga_beli_ppn, 'updated_by' => Auth::id(), 'updated_at' => date('Y-m-d H:i:s')]);
                    $i++;
                }
                
        }

        echo $i;

    }
}

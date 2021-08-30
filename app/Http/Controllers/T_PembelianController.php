<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\TransaksiPembelian;
use App\TransaksiPembelianDetail;
use App\MasterObat;
use App\MasterApotek;
use App\MasterJenisPembelian;
use App\MasterJasaResep;
use App\DefectaOutlet;
use App\MasterSuplier;
use App\MasterKartu;
use App\MasterMember;
use App\User;
use App\RevisiPembelian;
use App\TransaksiOrder;
use App\TransaksiOrderDetail;
use App\PembayaranKonsinyasi;
use App\ReturPembelian;
use App\MasterAlasanReturPembelian;
use App\KonfirmasiED;
use App\MasterJenisPenanganan;
use App;
use Datatables;
use DB;
use Auth;
use Illuminate\Support\Carbon;
use App\Events\PembelianRetur;
use App\Events\PembelianCreate;
use App\Http\Controllers\Controller;
use Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class T_PembelianController extends Controller
{
    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 07/11/2020
        =======================================================================================
    */
    public function index()
    {
        $supliers =MasterSuplier::where('is_deleted', 0)->get();
        $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->get();
        return view('pembelian.index')->with(compact('supliers', 'jenis_pembelians'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 07/11/2020
        =======================================================================================
    */
    public function list_pembelian(Request $request)
    {
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

        $tanggal = date('Y-m-d');
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelian::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_pembelian.*', 
        ])
        ->where(function($query) use($request, $tanggal){
            $query->where('tb_nota_pembelian.is_deleted','=','0');
            $query->where('tb_nota_pembelian.id_apotek_nota','=',session('id_apotek_active'));
            $query->where('tb_nota_pembelian.no_faktur','LIKE',($request->no_faktur > 0 ? $request->no_faktur : '%'.$request->no_faktur.'%'));
            
            if($request->id_jenis_pembelian > 0) {
                $query->where('tb_nota_pembelian.id_jenis_pembelian',$request->id_jenis_pembelian);
            }

            if($request->id_suplier > 0) {
                $query->where('tb_nota_pembelian.id_suplier',$request->id_suplier);
            }

            if($request->id_apotek > 0) {
                $query->where('tb_nota_pembelian.id_apotek',$request->id_apotek);
            }
            
           // $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
           // $query->where('tb_nota_pembelian.id_supliers','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
            if($request->tgl_awal != "") {
                $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                $query->whereDate('tb_nota_pembelian.tgl_jatuh_tempo','>=', $tgl_awal);
            }

            if($request->tgl_akhir != "") {
                $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                $query->whereDate('tb_nota_pembelian.tgl_jatuh_tempo','<=', $tgl_akhir);
            }

            if($request->tgl_awal_faktur != "") {
                $tgl_awal_faktur       = date('Y-m-d H:i:s',strtotime($request->tgl_awal_faktur));
                $query->whereDate('tb_nota_pembelian.tgl_faktur','>=', $tgl_awal_faktur);
            }

            if($request->tgl_akhir_faktur != "") {
                $tgl_akhir_faktur      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir_faktur));
                $query->whereDate('tb_nota_pembelian.tgl_faktur','<=', $tgl_akhir_faktur);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('tb_nota_pembelian.no_faktur','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('apotek', function($data){
            return $data->apotek->nama_singkat;
        })
        ->editcolumn('suplier', function($data){
            return $data->suplier->nama;
        })
        ->editcolumn('jumlah', function($data){
            $new = $data->detail_pembelian_total->first();
            $total1 = $new->jumlah - ($data->diskon1 + $data->diskon2);
            $total2 = $total1 + ($total1 * $data->ppn/100);
            return "Rp ".number_format($total2,2);
        })
        ->editcolumn('is_lunas', function($data){
            if($data->is_lunas == 0) {
                return '<span class="label label-danger" data-toggle="tooltip" data-placement="top" title="Faktur Belum Dibayar" style="font-size:8pt;color:#e91e63;">Belum Dibayar</span>';
            } else {
                $tgl_lunas_ = $data->lunas_at;
                if($data->id_jenis_pembelian == 1) {
                    $tgl_lunas_ = $data->tgl_faktur;
                } else {
                    if($tgl_lunas_ == '') {
                        $tgl_lunas_ = $data->updated_at;
                    }
                }
                return '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Faktur Sudah Dibayar" style="font-size:8pt;color:#009688;"></i> Lunas <br>@ : '.$tgl_lunas_.'</span>';
            }
        })      
        ->editcolumn('is_tanda_terima', function($data){
            if($data->is_tanda_terima == 0) {
                return '<span class="label label-danger" data-toggle="tooltip" data-placement="top" title="Faktur Asli Belum Diterima">Belum Diterima</span>';
            } else {
                return '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Faktur Asli Sudah Diterima"></i> Sudah Diterima</span>';
            }
        })    
        ->editcolumn('id_jenis_pembelian', function($data){
            if ($data->id_jenis_pembelian == 3) {
                $btn = '<a href="'.url('/pembelian/pembayaran_konsinyasi/'.$data->id).'" title="Pembayaran Konsinyasi" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Pembayaran Konsinyasi"><i class="fa fa-cogs"></i> Set Pembayaran</span></a>';
                return $data->jenis_pembelian->jenis_pembelian.'<br>'.$btn;
            }else {
                return $data->jenis_pembelian->jenis_pembelian;
            }
        })
        ->addcolumn('action', function($data) use($hak_akses) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary btn-sm" onClick="cek_tanda_terima_faktur('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Sudah menerima faktur asli"><i class="fa fa-check"></i> Faktur Asli</span>';
            $btn .= '<a href="'.url('/pembelian/'.$data->id.'/edit').'" title="Edit Data" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i> Edit</span></a>';
            if($hak_akses == 1) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="delete_pembelian('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i> Hapus</span>';
            }
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'is_tanda_terima', 'is_lunas', 'jumlah', 'suplier', 'apotek', 'id_jenis_pembelian'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function create() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
    	$apoteks = MasterApotek::whereIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->pluck('jenis_pembelian', 'id');
        $jenis_pembelians->prepend('-- Pilih Jenis Pembelian --','');
        $tanggal = date('Y-m-d');
        $pembelian = new TransaksiPembelian;
        $detail_pembelians = new TransaksiPembelianDetail;
        $var = 1;
        return view('pembelian.create')->with(compact('pembelian', 'apoteks', 'jenis_pembelians', 'detail_pembelians', 'var', 'apotek', 'inisial'));
    }

    public function store(Request $request) {
        DB::beginTransaction(); 
        try{
            $pembelian = new TransaksiPembelian;
            $pembelian->fill($request->except('_token'));
            $detail_pembelians = $request->detail_pembelian;   

            if($pembelian->id_jenis_pembayaran == 2) {
                $pembelian->is_tanda_terima = 1;
                $pembelian->is_lunas = 1;
            }  

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $apoteks = MasterApotek::whereIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
            $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->pluck('jenis_pembelian', 'id');
            $jenis_pembelians->prepend('-- Pilih Jenis Pembelian --','');
            $tanggal = date('Y-m-d');

            $validator = $pembelian->validate();
            if($validator->fails()){
                $var = 0;
                return view('pembelian.create')->with(compact('pembelian', 'apoteks', 'jenis_pembelians', 'detail_pembelians', 'var', 'apotek', 'inisial'))->withErrors($validator);
            }else{
                $pembelian->save_from_array($detail_pembelians,1);
                DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                //return redirect('pembelian/create');
                return redirect('pembelian');
            } 
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('penjualan');
        }
    }

    public function edit($id) {
        $pembelian = TransaksiPembelian::find($id);
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $apoteks = MasterApotek::whereIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->pluck('jenis_pembelian', 'id');
        $jenis_pembelians->prepend('-- Pilih Jenis Pembelian --','');
        $tanggal = date('Y-m-d');

        $detail_pembelians = $pembelian->detail_pembalian;
        $var = 0;
        return view('pembelian.edit')->with(compact('pembelian', 'apoteks', 'jenis_pembelians', 'detail_pembelians', 'var', 'apotek', 'inisial'));
    }

    public function show($id) {

    }

    public function update(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $pembelian = TransaksiPembelian::find($id);
            $pembelian->fill($request->except('_token'));
            $pembelian->updated_at = date('Y-m-d H:i:s');
            $pembelian->updated_by = Auth::user()->id;

            if($pembelian->id_jenis_pembayaran == 2) {
                $pembelian->is_tanda_terima = 1;
                //$pembelian->is_lunas = 1;
            }  

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);

            $detail_pembelians = $request->detail_pembelian;    

            foreach ($detail_pembelians as $key => $val) {
                if($val['id']>0){
                    $obj = TransaksiPembelianDetail::find($val['id']);
                    $selisih = 0;
                    if($val['id_jenis_revisi'] == 1) {
                        $selisih = $val['jumlah'] - $obj->jumlah;
                    } else if($val['id_jenis_revisi'] == 2) {
                        $selisih = $obj->jumlah - $val['jumlah'];
                    } 
                    
                    $obj->selisih = $selisih;
                    $obj->id_jenis_revisi = $val['id_jenis_revisi'];
                    $obj->total_harga = $val['total_harga'];
                    $obj->harga_beli = $val['harga_beli'];
                    $obj->harga_beli_ppn = $val['harga_beli']+($pembelian->ppn/100*$val['harga_beli']);
                    $obj->jumlah = $val['jumlah'];
                    $obj->diskon = $val['diskon'];
                    $obj->diskon_persen = $val['diskon_persen'];
                    $obj->id_batch = $val['id_batch'];
                    $obj->tgl_batch = $val['tgl_batch'];
                    $obj->updated_by = Auth::user()->id;
                    $obj->updated_at = date('Y-m-d H:i:s');
                    
                    $cek = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                    if($obj->harga_beli != $val['harga_beli']) {
                        $harga_before = DB::table('tb_histori_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                        $harga_now = $val['harga_beli'];
                        $harga_ppn_now = ($pembelian->ppn/100 * $val['harga_beli']) + $val['harga_beli'];

                        # update ke table stok harga
                        DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['harga_beli'=> $harga_now, 'harga_beli_ppn'=> $harga_ppn_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                        # create histori
                        DB::table('tb_histori_harga_'.$inisial)->insert([
                            'id_obat' => $obj->id_obat,
                            'harga_beli_awal' => $cek->harga_beli,
                            'harga_beli_akhir' => $val['harga_beli'],
                            'harga_jual_awal' => $cek->harga_jual,
                            'harga_jual_akhir' => $cek->harga_jual, 
                            'id_asal' => $obj->id,
                            'is_asal' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => Auth::user()->id
                        ]);
                    } else if($obj->harga_beli != $cek->harga_beli){
                        $harga_before = DB::table('tb_histori_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                        $harga_now = $val['harga_beli'];
                        $harga_ppn_now = ($pembelian->ppn/100 * $val['harga_beli']) + $val['harga_beli'];

                        # update ke table stok harga
                        DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['harga_beli'=> $harga_now, 'harga_beli_ppn'=> $harga_ppn_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                        # create histori
                        DB::table('tb_histori_harga_'.$inisial)->insert([
                            'id_obat' => $obj->id_obat,
                            'harga_beli_awal' => $cek->harga_beli,
                            'harga_beli_akhir' => $val['harga_beli'],
                            'harga_jual_awal' => $cek->harga_jual,
                            'harga_jual_akhir' => $cek->harga_jual, 
                            'id_asal' => $obj->id,
                            'is_asal' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => Auth::user()->id
                        ]);
                    }

                    if($obj->jumlah != $val['jumlah']) {
                        // buat histori revisi jumlah
                        $rev_pembelian = new RevisiPembelian;
                        $rev_pembelian->id_detail_nota = $obj->id;
                        $rev_pembelian->id_obat = $val['id_obat'];
                        $rev_pembelian->jumlah_awal = $obj->jumlah;
                        $rev_pembelian->jumlah = $val['jumlah'];
                        $rev_pembelian->harga_beli_awal = $obj->harga_beli;
                        $rev_pembelian->harga_beli = $val['harga_beli'];
                        $rev_pembelian->created_at = date('Y-m-d H:i:s');
                        $rev_pembelian->created_by = Auth::user()->id;
                        $rev_pembelian->save();

                        $obj->id_revisi_pembelian = $rev_pembelian->id;
                        $obj->is_revisi = 1;
                        $obj->revisi_at = date('Y-m-d H:i:s');
                        $obj->revisi_by = Auth::user()->id;
                        $obj->jumlah = $val['jumlah'];
                        $obj->jumlah_revisi = $val['jumlah'];
                        $obj->harga_beli = $val['harga_beli'];
                    } else {
                        $obj->harga_beli = $val['harga_beli'];
                    }

                    if($obj->save()) {
                        PembelianRetur::dispatch($obj);
                    }
                }else{
                    $obj = new TransaksiPembelianDetail;
                    $obj->id_nota = $pembelian->id;
                    $obj->id_obat = $val['id_obat'];
                    $obj->total_harga = $val['total_harga'];
                    $obj->harga_beli = $val['harga_beli'];
                    $obj->harga_beli_ppn = $val['harga_beli']+($pembelian->ppn/100*$val['harga_beli']);
                    $obj->jumlah = $val['jumlah'];
                    $obj->diskon = $val['diskon'];
                    $obj->diskon_persen = $val['diskon_persen'];
                    $obj->id_batch = $val['id_batch'];
                    $obj->tgl_batch = $val['tgl_batch'];
                    $obj->created_by = Auth::user()->id;
                    $obj->created_at = date('Y-m-d H:i:s');
                    $obj->updated_at = date('Y-m-d H:i:s');
                    $obj->updated_by = '';
                    $obj->is_deleted = 0;

                    $obj->save();
                    if($obj->save()) {
                        PembelianCreate::dispatch($obj);
                    }
                }
            }


            $validator = $pembelian->validate();
            if($validator->fails()){
                $apotek = MasterApotek::find(session('id_apotek_active'));
                $inisial = strtolower($apotek->nama_singkat);
                $apoteks = MasterApotek::whereIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
                $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->pluck('jenis_pembelian', 'id');
                $jenis_pembelians->prepend('-- Pilih Jenis Pembelian --','');
                $tanggal = date('Y-m-d');

                $var = 0;
                return view('pembelian.edit')->with(compact('pembelian', 'apoteks', 'jenis_pembelians', 'detail_pembelians', 'var', 'apotek', 'inisial'))->withErrors($validator);
            }else{
                $pembelian->save();
                DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                return redirect('pembelian');
                //return redirect('pembelian/'.$pembelian->id.'/edit');
            } 
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('pembelian');
        }
    }

    public function destroy($id) {
        DB::beginTransaction(); 
        try{
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $pembelian = TransaksiPembelian::find($id);
            $pembelian->is_deleted = 1;

            $detail_pembelians = TransaksiPembelianDetail::where('id_nota', $pembelian->id)->get();
            foreach ($detail_pembelians as $key => $detail_pembelian) {
                $detail_pembelian->is_deleted = 1;
                $detail_pembelian->deleted_at = date('Y-m-d H:i:s');
                $detail_pembelian->deleted_by = Auth::user()->id;
                $detail_pembelian->save();

                $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();
                if($detail_pembelian->id_jenis_revisi == 1) {
                    $jumlah = $detail_pembelian->selisih;
                } else {
                    $jumlah = $detail_pembelian->jumlah;
                }

                $stok_now = $stok_before->stok_akhir-$jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $detail_pembelian->id_obat,
                    'jumlah' => $jumlah,
                    'stok_awal' => $stok_before->stok_akhir,
                    'stok_akhir' => $stok_now,
                    'id_jenis_transaksi' => 14, //hapus pembelian
                    'id_transaksi' => $detail_pembelian->id,
                    'batch' => $detail_pembelian->id_batch,
                    'ed' => $detail_pembelian->tgl_batch,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);
            }

            if($pembelian->save()){
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('penjualan');
        }
    }

    public function open_data_suplier(Request $request) {
        $suplier = $request->suplier;
        return view('pembelian._dialog_open_suplier')->with(compact('suplier'));
    }

    public function list_data_suplier(Request $request)
    {
        $suplier = $request->suplier;

        DB::statement(DB::raw('set @rownum = 0'));
        $data = MasterSuplier::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_m_suplier.*'
        ])
        ->where(function($query) use($request){
            $query->where('tb_m_suplier.is_deleted','=','0');
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request, $suplier){
            $query->where(function($query) use($request, $suplier){
                $query->orwhere('tb_m_suplier.nama','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="add_suplier_dialog('.$data->id.')" data-toggle="tooltip" data-placement="top" title="pilih suplier"><i class="fa fa-check"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['stok_akhir', 'action'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function cari_suplier_dialog(Request $request) {
        $suplier = MasterSuplier::find($request->id);

        return json_encode($suplier);
    }

    public function find_ketentuan_keyboard(){
        return view('pembelian._form_ketentuan_keyboard');
    }

    public function edit_detail(Request $request){
        $id = $request->id;
        $no = $request->no;
        $detail = TransaksiPembelianDetail::find($id);
        return view('pembelian._form_edit_detail')->with(compact('detail', 'no'));
    }

    public function list_pembelian_revisi(Request $request)
    {
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = RevisiPembelian::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_revisi_pembelian_obat.*', 
        ])
        ->join('tb_detail_nota_pembelian as a', 'a.id', '=', 'tb_revisi_pembelian_obat.id_detail_nota')
        ->where(function($query) use($request){
            $query->where('a.is_deleted','=','0');
            $query->where('a.is_revisi','=','1');
            $query->where('a.id_nota','=',$request->id);
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('tb_revisi_pembelian_obat.id','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('tanggal', function($data) use($request){
            return Carbon::parse($data->created_at)->format('d/m/Y H:i:s');
        })
        ->editcolumn('detail_obat', function($data) use($request){
            $string = '<b>'.$data->obat->nama.'<b><br>';
            return $string;
        })
        ->editcolumn('kasir', function($data) use($request){
            return $data->created_oleh->nama;
        })
        ->rawColumns(['kasir', 'tanggal', 'detail_obat'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function data_pembelian_item() {
        return view('pembelian.data_pembelian_item');
    }

    public function list_pembelian_item(Request $request) {

    }

    public function konfirmasi_barang_datang() {
       $orders = TransaksiOrder::select('tb_nota_order.*')
                                ->where('tb_nota_order.is_deleted', 0)
                                ->where('tb_nota_order.is_status', 0)
                                ->where('tb_nota_order.id_apotek', session('id_apotek_active'))
                                ->get();

                
        $supliers = MasterSuplier::where('is_deleted', 0)->pluck('nama','id');
        $pembelian = new TransaksiPembelian;
       // $pembelian->

        return view('konfirmasi_barang.create')->with(compact('supliers', 'orders', 'satuan', 'pembelian'));
    }

    public function konfirmasi_barang_store(Request $request) {
        $pembelian = new TransaksiPembelian;
        $pembelian->fill($request->except('_token'));
        $details = $request->detail_order;

        $detail_pembelians = array();
        foreach ($details as $key => $val) {
            $order = TransaksiOrderDetail::select(['tb_detail_nota_order.*'])
                        ->where('tb_detail_nota_order.id', $val['id_detail_order'])
                        ->first();
            $key = $key-1;
            $pembelian->id_suplier = $order->nota->id_suplier;
            $pembelian->id_apotek = $order->nota->id_apotek;
            $pembelian->id_nota_order = $order->id_nota;
            $detail_pembelians[$key]['id_detail_order'] = $order->id; 
            $detail_pembelians[$key]['id_obat'] = $order->id_obat;
            $detail_pembelians[$key]['nama_obat'] = $order->obat->nama;
            $detail_pembelians[$key]['id'] = '';
            $detail_pembelians[$key]['id_nota'] = '';
            $detail_pembelians[$key]['jumlah'] = $order->jumlah;
            $detail_pembelians[$key]['jumlah_strip'] = 0;
            $detail_pembelians[$key]['jumlah_tab'] = 0;
            $detail_pembelians[$key]['total_harga'] = 0;
            $detail_pembelians[$key]['harga_beli'] = 0;
            $detail_pembelians[$key]['id_batch'] = '';
            $detail_pembelians[$key]['tgl_batch'] = '';
            $detail_pembelians[$key]['diskon'] = 0;
            $detail_pembelians[$key]['diskon_persen'] = 0;
            $detail_pembelians[$key]['total'] = 0;
            $detail_pembelians[$key]['total_harga'] = 0;
            $detail_pembelians[$key]['total_diskon_persen'] = 0;
        }

        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $apoteks = MasterApotek::whereIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $jenis_pembelians = MasterJenisPembelian::where('is_deleted', 0)->pluck('jenis_pembelian', 'id');
        $jenis_pembelians->prepend('-- Pilih Jenis Pembelian --','');
        $tanggal = date('Y-m-d');

        $var = 1;
        return view('pembelian_order.create')->with(compact('pembelian', 'apoteks', 'jenis_pembelians', 'apotek', 'detail_pembelians', 'var', 'is_from_order'));
        
    }

    public function list_data_order(Request $request) {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiOrderDetail::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_detail_nota_order.*'
        ])
        ->where(function($query) use($request){
            $query->where('tb_detail_nota_order.is_deleted','=','0');
            $query->where('tb_detail_nota_order.is_status','=','0');
            $query->where('tb_detail_nota_order.id_nota', $request->id_nota);
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('tb_detail_nota_order.id','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->addColumn('checkList', function ($data) {
           // return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" data-id_apotek="'.$data->id_apotek.'" value="'.$data->id.'"/>';
            return '<input type="checkbox" name="detail_order['.$data->no.'][id_detail_order]" id="detail_order['.$data->no.'][id_detail_order]" value="'.$data->id.'">';
        })
        /*->editcolumn('checkList', function($data){
            return '<input type="checkbox" name="detail_order['.$data->no.'][id_detail_order]" id="detail_order['.$data->no.'][id_detail_order]" value="'.$data->id.'">';
        })*/
        ->editcolumn('id_obat', function($data) {
            $string = $data->obat->nama;
            $string .= '<br><small>Keterangan : '.$data->keterangan.'</small>';
            return $string;
        })
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            /*$btn .= '<span class="btn btn-primary" onClick="add_suplier_dialog('.$data->id.')" data-toggle="tooltip" data-placement="top" title="pilih suplier"><i class="fa fa-check"></i></span>';*/

            if($data->id_obat == 0) {
                $btn .= '<a href="#" onClick="add_data_obat('.$data->id.')" title="Add data obat" data-toggle="modal" ><span class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Add data obat"><i class="fa fa-plus"></i></span> </a>';
            } else {
                $btn .= '<p style="color:#ff5722;">-</p>';
            }

            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['select', 'action', 'checkList', 'id_obat'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function edit_detail_from_order(Request $request){
        $id = $request->id_detail_order;
        $no = $request->no;
        $order = TransaksiOrderDetail::find($id);
        $detail = new TransaksiPembelianDetail;

        return view('pembelian_order._form_edit_detail_from_order')->with(compact('detail', 'no', 'order'));
    }

    public function cek_tanda_terima_faktur(Request $request)
    {
        $pembelian = TransaksiPembelian::find($request->id);
        $pembelian->is_tanda_terima = 1;
        $pembelian->tanda_terima_at = date('Y-m-d H:i:s');
        $pembelian->tanda_terima_by = Auth::user()->id;

        if($pembelian->save()){
            echo 1;
        }else{
            echo 0;
        }
    } 

    public function pembayaran_faktur_belum_lunas(){
        $apoteks = MasterApotek::where('is_deleted', 0)->get();
        $supliers = MasterSuplier::where('is_deleted', 0)->get();

        return view('pembayaran_faktur._form_pembayaran_faktur_belum_lunas')->with(compact('supliers', 'apoteks'));
    }

    public function list_pembayaran_faktur_belum_lunas(Request $request)
    {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelian::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_pembelian.*'])
                ->where(function($query) use($request){
                    $query->where('tb_nota_pembelian.is_deleted','=','0');
                    $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                    $query->where('tb_nota_pembelian.is_lunas','=','0');
                    $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
                    $query->where('tb_nota_pembelian.id_suplier','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
                    $query->where('tb_nota_pembelian.is_lunas','LIKE',($request->id_status_lunas > 0 ? $request->id_status_lunas : '%'.$request->id_status_lunas.'%'));
                    if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $request->tgl_awal);
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $request->tgl_akhir);
                    }
                })
                ->orderBy('tgl_jatuh_tempo','asc')
                ->orderBy('id_suplier')
                ->groupBy('tb_nota_pembelian.id');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('tb_nota_pembelian.id','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('suplier', function($data){
            return $data->suplier->nama;
        })
        ->editcolumn('apotek', function($data){
            return $data->apotek->nama_panjang;
        })
        ->editcolumn('jenis_pembelian', function($data){
            if ($data->id_jenis_pembelian == 3) {
                return $data->jenis_pembelian->jenis_pembelian.'<br><a href="'.url('/pembelian/pembayaran_konsinyasi/'.$data->id).'" title="Ubah" data-toggle="modal" data-id="{!! $id !!}" ><span class="label label-warning" data-toggle="tooltip" data-placement="top" title="Konsinyasi"><i class="fa fa-cogs"></i> Set Pembayaran</span></a>';
            }else {
                return $data->jenis_pembelian->jenis_pembelian;
            }
        })
        ->editcolumn('jumlah', function($data){
            $x = $data->detail_pembelian_total[0];
            $total1 = $x->jumlah - ($data->diskon1 + $data->diskon2);
            $total2 = $total1 + ($total1 * $data->ppn/100);
            return "Rp ".number_format($total2,2);
        })
        ->editcolumn('is_lunas', function($data){
            if ($data->is_lunas == 0) {
                return '<span class="text-info"><i class="fa fa-fw fa-question"></i>Belum Lunas</span>';
            } else if($data->is_lunas == 1) {
                return '<span class="text-success"><i class="fa fa-fw fa-check"></i>Lunas</span>';
            }
           
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';

            $btn .= '<a href="#" onClick="lihat_detail_faktur('.$data->id.')" title="Detail Faktur" data-toggle="modal" ><span class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Lihat Faktur"><i class="fa fa-eye"></i></span> </a>';
            $btn .= '<a href="#" onClick="lunas_pembayaran('.$data->id.')" title="Set Lunas Faktur" data-toggle="modal" ><span class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Set Lunas"><i class="fa fa-check"></i></span> </a>';

            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['suplier', 'action', 'apotek', 'jenis_pembelian', 'jumlah', 'is_lunas'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function pembayaran_faktur_lunas(){
        $apoteks = MasterApotek::where('is_deleted', 0)->get();
        $supliers = MasterSuplier::where('is_deleted', 0)->get();

        return view('pembayaran_faktur._form_pembayaran_faktur_lunas')->with(compact('supliers', 'apoteks'));
    }

    public function list_pembayaran_faktur_lunas(Request $request)
    {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelian::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_pembelian.*'])
                ->where(function($query) use($request){
                    $query->where('tb_nota_pembelian.is_deleted','=','0');
                    $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                    $query->where('tb_nota_pembelian.is_lunas','=','1');
                    $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
                    $query->where('tb_nota_pembelian.id_suplier','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
                    $query->where('tb_nota_pembelian.is_lunas','LIKE',($request->id_status_lunas > 0 ? $request->id_status_lunas : '%'.$request->id_status_lunas.'%'));
                    if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $request->tgl_awal);
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $request->tgl_akhir);
                    }
                })
                ->orderBy('tgl_jatuh_tempo','asc')
                ->orderBy('id_suplier')
                ->groupBy('tb_nota_pembelian.id');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('tb_nota_pembelian.id','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('suplier', function($data){
            return $data->suplier->nama;
        })
        ->editcolumn('apotek', function($data){
            return $data->apotek->nama_panjang;
        })
        ->editcolumn('jenis_pembelian', function($data){
            if ($data->id_jenis_pembelian == 3) {
                return $data->jenis_pembelian->jenis_pembelian.'<br><a href="'.url('/pembelian/pembayaran_konsinyasi/'.$data->id).'" title="Ubah" data-toggle="modal" data-id="{!! $id !!}" ><span class="label label-warning" data-toggle="tooltip" data-placement="top" title="Konsinyasi"><i class="fa fa-cogs"></i> Set Pembayaran</span></a>';
            }else {
                return $data->jenis_pembelian->jenis_pembelian;
            }
        })
        ->editcolumn('jumlah', function($data){
            $x = $data->detail_pembelian_total[0];
            $total1 = $x->jumlah - ($data->diskon1 + $data->diskon2);
            $total2 = $total1 + ($total1 * $data->ppn/100);
            return "Rp ".number_format($total2,2);
        })
        ->editcolumn('is_lunas', function($data){
            if ($data->is_lunas == 0) {
                return '<span class="text-info"><i class="fa fa-fw fa-question"></i>Belum Lunas</span>';
            } else if($data->is_lunas == 1) {
                return '<span class="text-success"><i class="fa fa-fw fa-check"></i>Lunas</span>';
            }
           
        })   
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';

            $btn .= '<a href="#" onClick="lihat_detail_faktur('.$data->id.')" title="Detail Faktur" data-toggle="modal" ><span class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Lihat Faktur"><i class="fa fa-eye"></i></span> </a>';

            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['suplier', 'action', 'apotek', 'jenis_pembelian', 'jumlah', 'is_lunas'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function pembayaran_faktur(){
        $apoteks = MasterApotek::where('is_deleted', 0)->get();
        $supliers = MasterSuplier::where('is_deleted', 0)->get();

        return view('pembayaran_faktur.index')->with(compact('supliers', 'apoteks'));
    }

    public function list_pembayaran_faktur(Request $request)
    {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelian::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_pembelian.*'])
                ->where(function($query) use($request){
                    $query->where('tb_nota_pembelian.is_deleted','=','0');
                    $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                    $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
                    $query->where('tb_nota_pembelian.id_suplier','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
                    $query->where('tb_nota_pembelian.is_lunas','LIKE',($request->id_status_lunas > 0 ? $request->id_status_lunas : '%'.$request->id_status_lunas.'%'));
                    if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $request->tgl_awal);
                        $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $request->tgl_akhir);
                    }
                })
                ->orderBy('tgl_jatuh_tempo','asc')
                ->orderBy('id_suplier')
                ->groupBy('tb_nota_pembelian.id');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('tb_nota_pembelian.id','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('suplier', function($data){
            return $data->suplier->nama;
        })
        ->editcolumn('apotek', function($data){
            return $data->apotek->nama_panjang;
        })
        ->editcolumn('jenis_pembelian', function($data){
            if ($data->id_jenis_pembelian == 3) {
                $btn = '<a href="'.url('/pembelian/pembayaran_konsinyasi/'.$data->id).'" title="Pembayaran Konsinyasi" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Pembayaran Konsinyasi"><i class="fa fa-cogs"></i> Set Pembayaran</span></a>';
                return $data->jenis_pembelian->jenis_pembelian.'<br>'.$btn;
            }else {
                return $data->jenis_pembelian->jenis_pembelian;
            }
        })
        ->editcolumn('jumlah', function($data){
            $x = $data->detail_pembelian_total[0];
            $total1 = $x->jumlah - ($data->diskon1 + $data->diskon2);
            $total2 = $total1 + ($total1 * $data->ppn/100);
            return "Rp ".number_format($total2,2);
        })
        ->editcolumn('is_lunas', function($data){
            if ($data->is_lunas == 0) {
                return '<span class="text-info"><i class="fa fa-fw fa-question"></i>Belum Lunas</span>';
            } else if($data->is_lunas == 1) {
                return '<span class="text-success"><i class="fa fa-fw fa-check"></i>Lunas</span>';
            }
        })  
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';

            $btn .= '<a href="#" onClick="lihat_detail_faktur('.$data->id.')" title="Detail Faktur" data-toggle="modal" ><span class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Lihat Faktur"><i class="fa fa-eye"></i></span> </a>';
            $btn .= '<a href="#" onClick="lunas_pembayaran('.$data->id.')" title="Set Lunas Faktur" data-toggle="modal" ><span class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Set Lunas"><i class="fa fa-check"></i></span> </a>';

            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['suplier', 'action', 'apotek', 'jenis_pembelian', 'jumlah', 'is_lunas'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function lunas_pembayaran(Request $request)
    {
        $pembelian = TransaksiPembelian::find($request->id);
        $pembelian->is_lunas = 1;
        $pembelian->lunas_at = date('Y-m-d H:i:s');
        $pembelian->lunas_by = Auth::user()->id;

        if($pembelian->save()){
            echo 1;
        }else{
            echo 0;
        }
    } 

    public function lihat_detail_faktur(Request $request)
    {
        $pembelian = TransaksiPembelian::find($request->id);

        return view('pembayaran_faktur._form_lihat_detail_faktur')->with(compact('pembelian'));
    } 

    public function reload_harga_beli_ppn() {
        $pembelian = TransaksiPembelian::all();
        $i = 0;
        foreach ($pembelian as $key => $val) {
            $detail_pembelians = TransaksiPembelianDetail::where('id_nota', $val->id)->get();
            foreach ($detail_pembelians as $x => $obj) {
                $i++;
                $obj->harga_beli_ppn = $obj->harga_beli+($val->ppn/100*$obj->harga_beli);
                $obj->save();
            }
        }

        echo $i." data";
    }


    public function reload_harga_ppn_form_outlet($id) {
        $apotek = MasterApotek::find($id);
        $inisial = strtolower($apotek->nama_singkat);
        $data = DB::table('tb_m_stok_harga_'.$inisial.'')->get();
        foreach ($data as $key => $val) {
            $cari_last = TransaksiPembelianDetail::where('id_obat', $val->id_obat)->orderBy('id_old', 'DESC')->first();
            if(!empty($cari_last)) {
                if($val->harga_beli != $cari_last->harga_beli) {
                    $data_histori_ = array('id_obat' => $val->id_obat, 'harga_beli_awal' => $val->harga_beli, 'harga_beli_akhir' => $cari_last->harga_beli, 'harga_jual_awal' => $val->harga_jual, 'harga_jual_akhir' => $val->harga_jual, 'created_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'));

                    DB::table('tb_histori_harga_'.$inisial.'')->insert($data_histori_);

                    // update harga beli dan harga beli ppn
                    DB::table('tb_m_stok_harga_'.$inisial.'')
                        ->where('id', $val->id)
                        ->update(['harga_beli' => $cari_last->harga_beli, 'harga_beli_ppn' => $cari_last->harga_beli_ppn, 'updated_by' => Auth::user()->id, 'updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    // update harga beli ppn
                    DB::table('tb_m_stok_harga_'.$inisial.'')
                        ->where('id', $val->id)
                        ->update(['harga_beli_ppn' => $cari_last->harga_beli_ppn, 'updated_by' => Auth::user()->id, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            } else {
                DB::table('tb_m_stok_harga_'.$inisial.'')
                        ->where('id', $val->id)
                        ->update(['harga_beli_ppn' => $val->harga_beli, 'updated_by' => Auth::user()->id, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    public function pencarian_obat() {
        return view('pembelian.pencarian_obat');
    }

    public function list_pencarian_obat(Request $request) {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelianDetail::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_detail_nota_pembelian.*', 'a.nama','b.no_faktur'])
        ->join('tb_m_obat as a', 'a.id', 'tb_detail_nota_pembelian.id_obat')
        ->join('tb_nota_pembelian as b', 'b.id', 'tb_detail_nota_pembelian.id_nota')
        ->where(function($query) use($request){
            $query->where('tb_detail_nota_pembelian.is_deleted','=','0');
            $query->where('b.id_apotek_nota','=',session('id_apotek_active'));
        })
        ->orderBy('b.id', 'DESC');
        
        $datatables = Datatables::of($data);
        return $datatables  
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('a.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('b.no_faktur','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('created_at', function($data) use($request){
            return Carbon::parse($data->created_at)->format('d/m/Y H:i:s');
        })
        ->editcolumn('created_by', function($data) use($request){
            return $data->created_oleh->nama;
        })
        ->editcolumn('id_obat', function($data) {
            $info = '<small>No Faktur : '.$data->no_faktur.' | Batch : '.$data->id_batch.' | Tanggal Batch : '.Carbon::parse($data->tgl_batch)->format('d/m/Y').'</small>';
            return $data->nama.'<br>'.$info;
        })
        ->editcolumn('total', function($data) {
            $total = ($data->jumlah*$data->harga_beli)-$data->diskon;
            if($total == "" || $total == null) {
                $total = 0;
            }
            $diskon = $data->diskon_persen/100*$total;
            $total2 = $total-$diskon;
            $str_ = '';
            $str_ = $data->jumlah.' X Rp '.number_format($data->harga_beli, 2)."-(Rp ".number_format($diskon,2).') = Rp '.number_format($total2,2);
            return $str_;
        })    
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'id_obat'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function export(Request $request) 
    {
        $rekaps = TransaksiPembelian::select([
                            DB::raw('@rownum  := @rownum  + 1 AS no'),
                            'tb_nota_pembelian.*'])
                            ->where(function($query) use($request){
                                $query->where('tb_nota_pembelian.is_deleted','=','0');
                                $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                                $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
                                $query->where('tb_nota_pembelian.id_suplier','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
                                $query->where('tb_nota_pembelian.is_lunas','LIKE',($request->id_status_lunas > 0 ? $request->id_status_lunas : '%'.$request->id_status_lunas.'%'));
                                if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $request->tgl_awal);
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $request->tgl_akhir);
                                }
                            })
                            ->orderBy('tgl_jatuh_tempo','asc')
                            ->orderBy('id_suplier')
                            ->groupBy('tb_nota_pembelian.id')
                            ->get();
                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $x = $rekap->detail_pembelian_total[0];
                    $total1 = $x->jumlah - ($rekap->diskon1 + $rekap->diskon2);
                    $total2 = $total1 + ($total1 * $rekap->ppn/100);

                    $collection[] = array(
                        $no,
                        $rekap->tgl_faktur,
                        $rekap->tgl_jatuh_tempo,
                        $rekap->suplier->nama,
                        $rekap->apotek->nama_singkat,
                        $rekap->no_faktur,
                        $total2,
                        'Rp '.number_format($total2,2),
                        '',
                    );
                }


        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return [
                        'No', 'Tanggal Faktur', 'Tgl Jatuh Tempo', 'Suplier', 'Apotek', 'No Faktur', 'Total', 'Total Format', 'TTD'
                        ];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 20,
                            'D' => 35,
                            'E' => 15,
                            'F' => 15,
                            'G' => 18,
                            'H' => 18,
                            'I' => 20,           
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'C'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'E'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                        ];
                    }

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Pembayaran Faktur.xlsx");
    }

    public function export_all(Request $request) 
    {
        $rekaps = TransaksiPembelian::select([
                                    DB::raw('@rownum  := @rownum  + 1 AS no'),
                                    'tb_nota_pembelian.*', 
                            ])
                            ->where(function($query) use($request){
                                $query->where('tb_nota_pembelian.is_deleted','=','0');
                                $query->where('tb_nota_pembelian.id_apotek_nota','=',session('id_apotek_active'));
                                $query->where('tb_nota_pembelian.no_faktur','LIKE',($request->no_faktur > 0 ? $request->no_faktur : '%'.$request->no_faktur.'%'));
                                
                                if($request->id_jenis_pembelian > 0) {
                                    $query->where('tb_nota_pembelian.id_jenis_pembelian',$request->id_jenis_pembelian);
                                }

                                if($request->id_suplier > 0) {
                                    $query->where('tb_nota_pembelian.id_suplier',$request->id_suplier);
                                }

                                if($request->id_apotek > 0) {
                                    $query->where('tb_nota_pembelian.id_apotek',$request->id_apotek);
                                }
                                
                               // $query->where('tb_nota_pembelian.id_apotek','LIKE',($request->id_apotek > 0 ? $request->id_apotek : '%'.$request->id_apotek.'%'));
                               // $query->where('tb_nota_pembelian.id_supliers','LIKE',($request->id_suplier > 0 ? $request->id_suplier : '%'.$request->id_suplier.'%'));
                                if($request->tgl_awal != "") {
                                    $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                                    $query->whereDate('tb_nota_pembelian.tgl_jatuh_tempo','>=', $tgl_awal);
                                }

                                if($request->tgl_akhir != "") {
                                    $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                                    $query->whereDate('tb_nota_pembelian.tgl_jatuh_tempo','<=', $tgl_akhir);
                                }

                                if($request->tgl_awal_faktur != "") {
                                    $tgl_awal_faktur       = date('Y-m-d H:i:s',strtotime($request->tgl_awal_faktur));
                                    $query->whereDate('tb_nota_pembelian.tgl_faktur','>=', $tgl_awal_faktur);
                                }

                                if($request->tgl_akhir_faktur != "") {
                                    $tgl_akhir_faktur      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir_faktur));
                                    $query->whereDate('tb_nota_pembelian.tgl_faktur','<=', $tgl_akhir_faktur);
                                }
                            })
                            ->orderBy('tgl_jatuh_tempo','asc')
                            ->orderBy('id_suplier')
                            ->groupBy('tb_nota_pembelian.id')
                            ->get();

                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $x = $rekap->detail_pembelian_total[0];
                    $total1 = $x->jumlah - ($rekap->diskon1 + $rekap->diskon2);
                    $total2 = $total1 + ($total1 * $rekap->ppn/100);
                    $lunas = "Belum Dibayar";
                    $tgl_bayar = '';
                    if($rekap->is_lunas == 1) {
                        $lunas = "Lunas";
                        $tgl_bayar = $rekap->lunas_at;
                    }
                    
                    $pembayaran = "Pembayaran Langsung";
                    if($rekap->id_jenis_pembayaran == 1) {
                        $pembayaran = "Pembayaran Tidak Langsung";
                    }

                    $collection[] = array(
                        $no,
                        $rekap->tgl_faktur,
                        $rekap->tgl_jatuh_tempo,
                        $rekap->jenis_pembelian->jenis_pembelian,
                        $pembayaran,
                        $rekap->suplier->nama,
                        $rekap->apotek->nama_singkat,
                        $rekap->no_faktur,
                        $total2,
                        'Rp '.number_format($total2,2),
                        $lunas,
                        $tgl_bayar,
                        '',
                    );
                }


        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return [
                        'No', 'Tanggal Faktur', 'Tgl Jatuh Tempo', 'Jenis Pembelian', 'Jenis Pembayaran', 'Suplier', 'Apotek', 'No Faktur', 'Total', 'Total Format', 'Status', 'Tanggal Bayar', 'TTD'
                        ];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 20,
                            'D' => 20,
                            'E' => 20,
                            'F' => 35,
                            'G' => 15,
                            'H' => 15,
                            'I' => 18,
                            'J' => 18,
                            'K' => 20,  
                            'L' => 20,           
                            'M' => 20, 
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'C'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'J'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                        ];
                    }

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Rekap Data Pembelian.xlsx");
    }

    public function pembayaran_konsinyasi($id) 
    {
        $pembelian = TransaksiPembelian::find($id);

        $detail_pembelians = TransaksiPembelianDetail::
                                select('tb_detail_nota_pembelian.*')
                                ->where('tb_detail_nota_pembelian.id_nota', $id)
                                ->get();

        return view('pembayaran_faktur._form_pembayaran_konsinyasi')->with(compact('pembelian', 'detail_pembelians'));
    }

    public function set_pembayaran_kosinyasi($id)
    {
        $detail_pembelian = TransaksiPembelianDetail::
                                select('tb_detail_nota_pembelian.id',
                                    'tb_detail_nota_pembelian.id_nota',
                                    'tb_detail_nota_pembelian.id_obat',
                                    'tb_detail_nota_pembelian.jumlah',
                                    'tb_detail_nota_pembelian.harga_beli',
                                    'tb_detail_nota_pembelian.diskon',
                                    'tb_detail_nota_pembelian.is_retur',
                                    DB::raw("SUM(b.jumlah_bayar) as jumlah_bayar"))
                                ->join('tb_pembayaran_konsinyasi as b', 'b.id_detail_nota', '=', 'tb_detail_nota_pembelian.id')
                                ->where('tb_detail_nota_pembelian.id', $id)
                                ->first();

        $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->first();
        $kartu_debets = MasterKartu::where('id_jenis_kartu', 1)->where('is_deleted', 0)->get();

        if(empty($retur_pembelian_obat)) {
            $retur_pembelian_obat = new ReturPembelian;
        }
        $alasan_returs = MasterAlasanReturPembelian::where('id', 2)->pluck('alasan', 'id');

        return view('pembayaran_faktur._form_set_pembayaran_kosinyasi')->with(compact('detail_pembelian', 'alasan_returs', 'retur_pembelian_obat', 'kartu_debets'));
    }

    public function add_pembayaran_konsinyasi(Request $request){
        $counter = $request->counter;
        $no = $counter+1;
        $detail_pembelian = new TransaksiPembelianDetail;
        $pembayaran_konsinyasi = new PembayaranKonsinyasi;
        $kartu_debets = MasterKartu::where('id_jenis_kartu', 1)->where('is_deleted', 0)->get();
        return view('pembayaran_faktur._form_add_pembayaran')->with(compact('no','detail_pembelian', 'pembayaran_konsinyasi', 'kartu_debets'));
    }

    public function update_pembayaran_konsinyasi(Request $request, $id)
    {

        DB::beginTransaction(); 
        try{
            $detail_pembelian = TransaksiPembelianDetail::find($id);
            $detail_pembelian->fill($request->except('_token'));
            $status = 0;
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $array_id_pembayaran = array();
            if(empty($request->is_retur)) {
                $request->is_retur == 0;
                $detail_pembelian->jumlah_retur = 0;
                $detail_pembelian->retur_at = null;
                $detail_pembelian->retur_by = null;
            } else {
                $detail_pembelian->jumlah_retur = $request->total_sisa_bayar;
                $detail_pembelian->retur_at = date('Y-m-d H:i:s');
                $detail_pembelian->retur_by = Auth::user()->id;
            }
            $detail_pembelian->is_retur = $request->is_retur;
            $detail_pembelian->save();

            $pembayaran_konsinyasis = $request->pembayaran_konsinyasi;
            if($request->is_retur == 1) {
                $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->first();

                if(empty($retur_pembelian_obat)) {
                    $retur_pembelian_obat = new ReturPembelian;
                    $retur_pembelian_obat->id_detail_nota = $detail_pembelian->id;
                    $retur_pembelian_obat->jumlah = $request->total_sisa_bayar;
                    $retur_pembelian_obat->id_alasan_retur = $request->id_alasan_retur;
                    $retur_pembelian_obat->alasan_lain = $request->alasan_lain;
                    $retur_pembelian_obat->jumlah = $request->total_sisa_bayar;
                    $retur_pembelian_obat->created_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->created_by = Auth::user()->id;
                    $retur_pembelian_obat->save();

                    // sesuaikan stok 
                    $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();
                    $stok_now = $stok_before->stok_akhir-$retur_pembelian_obat->jumlah;
                
                    # update ke table stok harga
                    DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                    # create histori
                    DB::table('tb_histori_stok_'.$inisial)->insert([
                        'id_obat' => $detail_pembelian->id_obat,
                        'jumlah' => $retur_pembelian_obat->jumlah,
                        'stok_awal' => $stok_before->stok_akhir,
                        'stok_akhir' => $stok_now,
                        'id_jenis_transaksi' => 26, //retur pembelian
                        'id_transaksi' => $retur_pembelian_obat->id,
                        'batch' => $detail_pembelian->id_batch,
                        'ed' => $detail_pembelian->tgl_batch,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id
                    ]);
                } else {
                    $retur_pembelian_obat->jumlah = $request->total_sisa_bayar;
                    $retur_pembelian_obat->id_alasan_retur = $request->id_alasan_retur;
                    $retur_pembelian_obat->alasan_lain = $request->alasan_lain;
                    $retur_pembelian_obat->updated_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->updated_by = Auth::user()->id;
                    $retur_pembelian_obat->save();
                }

                $pembelian = TransaksiPembelian::find($detail_pembelian->id_nota);
                $pembelian->is_lunas = 1;
                $pembelian->lunas_at = date('Y-m-d H:i:s');
                $pembelian->lunas_by = Auth::user()->id;
                $pembelian->save();
            } else {
                $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->first();

                if(!empty($retur_pembelian_obat)) {
                    $retur_pembelian_obat->is_deleted = 1;
                    $retur_pembelian_obat->deleted_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->deleted_by = Auth::user()->id;
                    $retur_pembelian_obat->save();
                }
            }
    
            foreach ($pembayaran_konsinyasis as $pembayaran_konsinyasi) {
                if($pembayaran_konsinyasi['id']>0){
                    $obj = PembayaranKonsinyasi::find($pembayaran_konsinyasi['id']);
                }else{
                    $obj = new PembayaranKonsinyasi;
                }

                $obj->id_detail_nota = $id;
                $obj->tgl_bayar = $pembayaran_konsinyasi['tgl_bayar'];
                $obj->jumlah_bayar = $pembayaran_konsinyasi['jumlah_bayar'];
                $obj->id_kartu_debet_credit = $pembayaran_konsinyasi['id_kartu_debet_credit'];
                $obj->debet = $pembayaran_konsinyasi['debet'];
                $obj->biaya_admin = $pembayaran_konsinyasi['biaya_admin'];
                $obj->cash = $pembayaran_konsinyasi['cash'];
                $obj->total_bayar = $pembayaran_konsinyasi['total_bayar'];
                $obj->created_by = Auth::user()->id;
                $obj->created_at = date('Y-m-d H:i:s');
                $obj->save();
                $array_id_pembayaran[] = $obj->id;
                $status = 1;
            }

            if($request->total_sisa_bayar == 0) {
                $pembelian = TransaksiPembelian::find($detail_pembelian->id_nota);
                $pembelian->is_lunas = 1;
                $pembelian->lunas_at = date('Y-m-d H:i:s');
                $pembelian->lunas_by = Auth::user()->id;
                $pembelian->save();
            }

            if(!empty($array_id_pembayaran)){
                DB::statement("DELETE FROM tb_pembayaran_konsinyasi
                                WHERE id_detail_nota=".$id." AND 
                                        id NOT IN(".implode(',', $array_id_pembayaran).")");
            }else{
                DB::statement("DELETE FROM tb_pembayaran_konsinyasi 
                                WHERE id_detail_nota=".$id);
            }

            if($status == 1){
                DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                return redirect('pembelian/pembayaran_konsinyasi/'.$detail_pembelian->id_nota);
            }else{
                session()->flash('error', 'Gagal menyimpan data!');
                return redirect('pembelian/pembayaran_faktur/'.$detail_pembelian->id_nota);
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('pembelian');
        }
    }

    public function obat_kadaluarsa() {
        return view('pembelian.obat_kadaluarsa');
    }

    public function list_obat_kadaluarsa(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPembelianDetail::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_detail_nota_pembelian.*', 'a.nama', 'b.no_faktur', 'c.stok_akhir'])
        ->join('tb_m_obat as a', 'a.id', 'tb_detail_nota_pembelian.id_obat')
        ->join('tb_nota_pembelian as b', 'b.id', 'tb_detail_nota_pembelian.id_nota')
        ->join('tb_m_stok_harga_'.$inisial.' as c', 'c.id_obat', 'tb_detail_nota_pembelian.id_obat')
        ->where(function($query) use($request){
            $query->where('tb_detail_nota_pembelian.is_deleted','=','0');
            $query->where('b.id_apotek_nota','=',session('id_apotek_active'));
            $query->where('tb_detail_nota_pembelian.id_batch','LIKE',($request->batch > 0 ? $request->batch : '%'.$request->batch.'%'));
            $query->where('b.no_faktur','LIKE',($request->no_faktur > 0 ? $request->no_faktur : '%'.$request->no_faktur.'%'));
            
            if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                $query->where('tb_detail_nota_pembelian.tgl_batch','>=', $request->tgl_awal);
                $query->where('tb_detail_nota_pembelian.tgl_batch','<=', $request->tgl_akhir);
            } else {
                $now = date('Y-m-d');
                $query->where('tb_detail_nota_pembelian.tgl_batch','>=', $now);
                $query->where('tb_detail_nota_pembelian.tgl_batch','<=', $now);
            }

            $query->where('c.stok_akhir','>',0);
        })
        ->orderBy('b.id', 'DESC');
        
        $datatables = Datatables::of($data);
        return $datatables  
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('a.nama','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('created_at', function($data) use($request){
            return Carbon::parse($data->created_at)->format('d/m/Y H:i:s');
        })
        ->editcolumn('created_by', function($data) use($request){
            return $data->created_oleh->nama;
        })
        ->editcolumn('id_obat', function($data) {
            $info = '<small>No Faktur : '.$data->no_faktur.'</small>';
            return $data->nama.'<br>'.$info;
        })
        ->editcolumn('stok', function($data) {
            return $data->stok_akhir;
        })
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<a href="'.url('/pembelian/konfirmasi_ed/'.$data->id).'" title="Konfirmasi ED" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Konfirmasi ED"><i class="fa fa-cogs"></i> Konfirmasi ED</span></a>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'id_obat'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function konfirmasi_ed($id) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $detail_pembelian = TransaksiPembelianDetail::find($id);
        $pembelian = $detail_pembelian->nota;
        $konfirmasi_ed = KonfirmasiED::where('id_detail_nota', $detail_pembelian->id)->first();
        if(empty($konfirmasi_ed)) {
            $konfirmasi_ed = new KonfirmasiED;
        }

        $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();

        $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->first();
        if(empty($retur_pembelian_obat)) {
            $retur_pembelian_obat = new ReturPembelian;
        }

        $jenis_penanganans = MasterJenisPenanganan::where('is_deleted', 0)->pluck('nama', 'id');
        $jenis_penanganans->prepend('-- Pilih Jenis Penanganan --','');

        $alasan_returs = MasterAlasanReturPembelian::where('is_deleted',0)->pluck('alasan', 'id');
        $alasan_returs->prepend('-- Pilih Alasan Retur --','');

        return view('konfirmasi_ed._form')->with(compact('apotek', 'detail_pembelian', 'pembelian', 'konfirmasi_ed', 'stok_before', 'jenis_penanganans', 'alasan_returs', 'retur_pembelian_obat'));
    } 

    public function update_konfirmasi_ed(Request $request, $id) {
        /*DB::beginTransaction(); 
        try{*/
            $detail_pembelian = TransaksiPembelianDetail::find($id);
            $detail_pembelian->fill($request->except('_token'));
            $status = 0;
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $array_id_pembayaran = array();
            if($request->id_jenis_penanganan == 1) {
                $detail_pembelian->is_retur = 1;
                $detail_pembelian->jumlah_retur = $request->jumlah_ed;
                $detail_pembelian->retur_at = date('Y-m-d H:i:s');
                $detail_pembelian->retur_by = Auth::user()->id;
                $detail_pembelian->save();

                $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->where('id_alasan_retur', 4)
                                            ->first();

                if(empty($retur_pembelian_obat)) {
                    $retur_pembelian_obat = new ReturPembelian;
                    $retur_pembelian_obat->id_detail_nota = $detail_pembelian->id;
                    $retur_pembelian_obat->jumlah = $request->jumlah_ed;
                    $retur_pembelian_obat->id_alasan_retur = 4;
                    $retur_pembelian_obat->alasan_lain = $request->alasan_lain;
                    $retur_pembelian_obat->created_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->created_by = Auth::user()->id;
                    $retur_pembelian_obat->save();

                    // update stok 
                    $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();
                    $stok_now = $stok_before->stok_akhir-$retur_pembelian_obat->jumlah;

                    # update ke table stok harga
                    DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                    # create histori
                    DB::table('tb_histori_stok_'.$inisial)->insert([
                        'id_obat' => $detail_pembelian->id_obat,
                        'jumlah' => $retur_pembelian_obat->jumlah,
                        'stok_awal' => $stok_before->stok_akhir,
                        'stok_akhir' => $stok_now,
                        'id_jenis_transaksi' => 26, //retur pembelian
                        'id_transaksi' => $retur_pembelian_obat->id,
                        'batch' => $detail_pembelian->id_batch,
                        'ed' => $detail_pembelian->tgl_batch,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id
                    ]);

                    $status = 1;
                } else {
                    $retur_pembelian_obat->jumlah = $request->jumlah_ed;
                    $retur_pembelian_obat->id_alasan_retur = 4;
                    $retur_pembelian_obat->alasan_lain = $request->alasan_lain;
                    $retur_pembelian_obat->updated_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->updated_by = Auth::user()->id;
                    $retur_pembelian_obat->save();

                    $status = 1;
                }
            } else {
                $retur_pembelian_obat = ReturPembelian::where('is_deleted', 0)
                                            ->where('id_detail_nota', $detail_pembelian->id)
                                            ->where('id_alasan_retur', 4)
                                            ->first();

                if(!empty($retur_pembelian_obat)) {
                    $retur_pembelian_obat->is_deleted = 1;
                    $retur_pembelian_obat->deleted_at = date('Y-m-d H:i:s');
                    $retur_pembelian_obat->deleted_by = Auth::user()->id;
                    $retur_pembelian_obat->save();

                    // update stok 
                    $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();
                    $stok_now = $stok_before->stok_akhir+$retur_pembelian_obat->jumlah;

                    # update ke table stok harga
                    DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                    # create histori
                    DB::table('tb_histori_stok_'.$inisial)->insert([
                        'id_obat' => $detail_pembelian->id_obat,
                        'jumlah' => $retur_pembelian_obat->jumlah,
                        'stok_awal' => $stok_before->stok_akhir,
                        'stok_akhir' => $stok_now,
                        'id_jenis_transaksi' => 27, //retur pembelian
                        'id_transaksi' => $retur_pembelian_obat->id,
                        'batch' => $detail_pembelian->id_batch,
                        'ed' => $detail_pembelian->tgl_batch,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id
                    ]);

                    $status = 1;
                }
            }

          /*  print_r($status);
            exit();*/

            if($status == 1){
                //DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                return redirect('pembelian/konfirmasi_ed/'.$detail_pembelian->id_nota);
            }else{
                session()->flash('error', 'Gagal menyimpan data!');
                return redirect('pembelian/konfirmasi_ed/'.$detail_pembelian->id_nota);
            }
        /*}catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('pembelian');
        }*/
    }

    public function export_ed(Request $request) 
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $rekaps = TransaksiPembelianDetail::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_detail_nota_pembelian.*', 'a.nama', 'b.tgl_faktur', 'b.no_faktur', 'c.stok_akhir'])
                    ->join('tb_m_obat as a', 'a.id', 'tb_detail_nota_pembelian.id_obat')
                    ->join('tb_nota_pembelian as b', 'b.id', 'tb_detail_nota_pembelian.id_nota')
                    ->join('tb_m_stok_harga_'.$inisial.' as c', 'c.id_obat', 'tb_detail_nota_pembelian.id_obat')
                    ->where(function($query) use($request){
                        $query->where('tb_detail_nota_pembelian.is_deleted','=','0');
                        $query->where('b.id_apotek_nota','=',session('id_apotek_active'));
                        $query->where('tb_detail_nota_pembelian.id_batch','LIKE',($request->batch > 0 ? $request->batch : '%'.$request->batch.'%'));
                        $query->where('b.no_faktur','LIKE',($request->no_faktur > 0 ? $request->no_faktur : '%'.$request->no_faktur.'%'));
                        if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                            $query->where('tb_detail_nota_pembelian.tgl_batch','>=', $request->tgl_awal);
                            $query->where('tb_detail_nota_pembelian.tgl_batch','<=', $request->tgl_akhir);
                        } else {
                            $now = date('Y-m-d');
                            $query->where('tb_detail_nota_pembelian.tgl_batch','>=', $now);
                            $query->where('tb_detail_nota_pembelian.tgl_batch','<=', $now);
                        }
                        $query->where('c.stok_akhir','>',0);
                    })
                    ->orderBy('b.id', 'DESC')
                    ->get();

                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $status = '';

                    $collection[] = array(
                        $no,
                        $rekap->id_nota,
                        $rekap->tgl_faktur,
                        $rekap->no_faktur,
                        $rekap->nama,
                        $rekap->tgl_batch,
                        $rekap->id_batch,
                        $rekap->stok_akhir,
                        $status
                    );
                }


        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return [
                        'No', 'ID Nota', 'Tanggal Faktur', 'No Faktur', 'Nama Obat', 'ED', 'Batch', 'Stok', 'Status'
                        ];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 15,
                            'C' => 20,
                            'D' => 20,
                            'E' => 40,
                            'F' => 15,
                            'G' => 15,
                            'H' => 15,  
                            'I' => 18,           
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'C'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'D'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                        ];
                    }

                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Rekap Data Obat ED.xlsx");
    }

    public function reload_hb_ppn($id)
    {
        $pembelian = TransaksiPembelian::find($id);
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $detail_pembelians = TransaksiPembelianDetail::where('is_deleted', 0)->where('id_nota', $pembelian->id)->get();    
        $i = 0;
        foreach($detail_pembelians as $key => $obj) {
            $cek = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
            $harga_before = DB::table('tb_histori_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
            $harga_ppn_now = ($pembelian->ppn/100 * $obj->harga_beli) + $obj->harga_beli;
            if($harga_ppn_now != $cek->harga_beli_ppn) {
                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['harga_beli_ppn'=> $harga_ppn_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);
                $i++;
            }
        }

        echo json_encode($i);            
    }

    public function hapus_detail($id) {
        DB::beginTransaction(); 
        try{
            $detail_pembelian = TransaksiPembelianDetail::find($id);
            $detail_pembelian->is_deleted = 1;
            $detail_pembelian->deleted_at= date('Y-m-d H:i:s');
            $detail_pembelian->deleted_by = Auth::user()->id;

            $pembelian = TransaksiPembelian::find($detail_pembelian->id_nota);
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);

            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->first();
            $jumlah = $detail_pembelian->jumlah;
            $stok_now = $stok_before->stok_akhir-$jumlah;

            $total = $detail_pembelian->harga_beli*$detail_pembelian->jumlah;
            $diskon = $detail_pembelian->diskon+(($detail_pembelian->diskon_persen/100)*$total);
            $total_final = $total-$diskon;
            $detail_pembelian->total_harga = $total_final;


            # update ke table stok harga
            DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_pembelian->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_stok_'.$inisial)->insert([
                'id_obat' => $detail_pembelian->id_obat,
                'jumlah' => $jumlah,
                'stok_awal' => $stok_before->stok_akhir,
                'stok_akhir' => $stok_now,
                'id_jenis_transaksi' => 14, //hapus pembelian
                'id_transaksi' => $detail_pembelian->id,
                'batch' => $detail_pembelian->id_batch,
                'ed' => $detail_pembelian->tgl_batch,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);  

            $total = TransaksiPembelianDetail::select([
                                DB::raw('SUM(total_harga) as total_all')
                                ])
                                ->where('id', '!=', $detail_pembelian->id)
                                ->where('id_nota', $detail_pembelian->id_nota)
                                ->where('is_deleted', 0)
                                ->first();
            $y = 0;
            if($total->total_all == 0 OR $total->total_all == '') {
                $y = 0;
            } else {
                $y = $total->total_all;
            }

            if($y == 0) {
                $pembelian->is_deleted = 1;
                $pembelian->deleted_at= date('Y-m-d H:i:s');
                $pembelian->deleted_by = Auth::user()->id;
            }

            if($detail_pembelian->save()){
                $pembelian->save();
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('pembelian/'.$pembelian->id.'/edit');
        }
    }

    public function change_obat(Request $request) {
        $detail_pembelian = TransaksiPembelianDetail::find($request->id_detail_pembelian);
        $obats      = MasterObat::where('is_deleted', 0)->pluck('nama', 'id');
        $no = $request->no;

        return view('pembelian._change_obat')->with(compact('detail_pembelian', 'obats', 'no'));
    }


    public function update_obat(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $detail_pembelian = TransaksiPembelianDetail::find($id);
            $pembelian = TransaksiPembelian::find($detail_pembelian->id_nota);
            $apotek = MasterApotek::find($pembelian->id_apotek_nota);
            $inisial = strtolower($apotek->nama_singkat);

            if($request->id_obat_awal != $request->id_obat_akhir) {
                // create histori stok dengan id_obat_awal
                $stok_before_awal = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_awal)->first();
                $jumlah = $detail_pembelian->jumlah;
                $stok_now_awal = $stok_before_awal->stok_akhir-$jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_awal)->update(['stok_awal'=> $stok_before_awal->stok_akhir, 'stok_akhir'=> $stok_now_awal, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $request->id_obat_awal,
                    'jumlah' => $jumlah,
                    'stok_awal' => $stok_before_awal->stok_akhir,
                    'stok_akhir' => $stok_now_awal,
                    'id_jenis_transaksi' => 30, //hapus pembelian -> ganti obat
                    'id_transaksi' => $detail_pembelian->id,
                    'batch' => $detail_pembelian->id_batch,
                    'ed' => $detail_pembelian->tgl_batch,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);  

                // create histori stok dengan id_obat_akhir
                $stok_before_akhir = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_akhir)->first();
                $stok_now_akhir = $stok_before_akhir->stok_akhir+$jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_akhir)->update(['stok_awal'=> $stok_before_akhir->stok_akhir, 'stok_akhir'=> $stok_now_akhir, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $request->id_obat_akhir,
                    'jumlah' => $jumlah,
                    'stok_awal' => $stok_before_akhir->stok_akhir,
                    'stok_akhir' => $stok_now_akhir,
                    'id_jenis_transaksi' => 31, //penambahan item pembelian -> ganti obat
                    'id_transaksi' => $detail_pembelian->id,
                    'batch' => $detail_pembelian->id_batch,
                    'ed' => $detail_pembelian->tgl_batch,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]); 

                $detail_pembelian->id_obat = $request->id_obat_akhir;
                $detail_pembelian->updated_at= date('Y-m-d H:i:s');
                $detail_pembelian->updated_by = Auth::user()->id;

                if($detail_pembelian->save()){
                    DB::commit();
                    echo 1;
                }else{
                    echo 0;
                }
            } else {
                echo 0;
            }   
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('pembelian/'.$id.'/edit');
        }
    }
}

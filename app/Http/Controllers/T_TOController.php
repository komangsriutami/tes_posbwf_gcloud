<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\TransaksiTO;
use App\TransaksiTODetail;
use App\MasterObat;
use App\MasterApotek;
use App\User;
use App;
use Datatables;
use DB;
use Auth;
use Excel;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class T_TOController extends Controller
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
        $apoteks = MasterApotek::where('is_deleted', 0)->get();
        return view('transfer_outlet.index')->with(compact('apoteks'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 07/11/2020
        =======================================================================================
    */
    public function list_transfer_outlet(Request $request)
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
        $data = TransaksiTO::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
	            'tb_nota_transfer_outlet.*', 
        ])
        ->where(function($query) use($request, $tanggal){
            $query->where('tb_nota_transfer_outlet.is_deleted','=','0');
            $query->where('tb_nota_transfer_outlet.id_apotek_nota','=',session('id_apotek_active'));
            $query->where('tb_nota_transfer_outlet.id','LIKE',($request->id > 0 ? $request->id : '%'.$request->id.'%'));
            $query->where('tb_nota_transfer_outlet.id_apotek_tujuan','LIKE',($request->id_apotek_tujuan > 0 ? $request->id_apotek_tujuan : '%'.$request->id_apotek_tujuan.'%'));
            if($request->tgl_awal != "") {
                $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                $query->whereDate('tb_nota_transfer_outlet.created_at','>=', $tgl_awal);
            }

            if($request->tgl_akhir != "") {
                $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                $query->whereDate('tb_nota_transfer_outlet.created_at','<=', $tgl_akhir);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                //$query->orwhere('tb_nota_transfer_outlet.no_faktur','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('id_apotek_asal', function($data){
        	return $data->apotek_asal->nama_singkat;
        })
        ->editcolumn('id_apotek_tujuan', function($data){
        	return $data->apotek_tujuan->nama_singkat;
        })
        ->editcolumn('is_lunas', function($data){
            if($data->is_lunas == 0) {
                return '<span class="label label-danger" data-toggle="tooltip" data-placement="top" title="Faktur Belum Dibayar">Belum Dibayar</span>';
            } else {
                return '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Faktur Sudah Dibayar"></i> Lunas</span>';
            }
        })     
        ->editcolumn('is_status', function($data){
            if($data->is_status == 0) {
                return '<span class="label label-danger" data-toggle="tooltip" data-placement="top" title="Item obat belum diterima">Belum Diterima</span>';
            } else {
                return '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Item obat sudah diterima"></i> Sudah Diterima</span>';
            }
        })    
        ->editcolumn('total', function($data) {
            return 'Rp '.number_format($data->total, 2);
        })  
        ->addcolumn('action', function($data) use($hak_akses){
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary btn-sm" onClick="cetak_nota('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Cetak Nota"><i class="fa fa-print"></i> Cetak</span>';
            $btn .= '<a href="'.url('/transfer_outlet/'.$data->id.'/edit').'" title="Edit Data" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i> Edit</span></a>';
            if($hak_akses == 1) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="delete_transfer('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i> Hapus</span>';
            }
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'is_status', 'is_lunas', 'id_apotek_asal', 'id_apotek_tujuan', 'total'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function create() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
    	$apoteks = MasterApotek::whereNotIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $tanggal = date('Y-m-d');
        $transfer_outlet = new TransaksiTO;
        $detail_transfer_outlets = new TransaksiTODetail;
        $var = 1;
        return view('transfer_outlet.create')->with(compact('transfer_outlet', 'apoteks', 'detail_transfer_outlets', 'var', 'apotek', 'inisial'));
    }

    public function store(Request $request) {
        DB::beginTransaction(); 
        try{
            $transfer_outlet = new TransaksiTO;
            $transfer_outlet->fill($request->except('_token'));
            $detail_transfer_outlets = $request->detail_transfer_outlet;

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $apoteks = MasterApotek::whereNotIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
            $tanggal = date('Y-m-d');


            $validator = $transfer_outlet->validate();
            if($validator->fails()){
                $var = 0;
                return view('transfer_outlet.create')->with(compact('transfer_outlet', 'apoteks', 'detail_transfer_outlets', 'var', 'apotek', 'inisial'))->withErrors($validator);
            }else{
                $transfer_outlet->save_from_array($detail_transfer_outlets,1);
                DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                return redirect('transfer_outlet');
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('transfer_outlet');
        }
    }

    public function edit($id) {
        $transfer_outlet = TransaksiTO::find($id);
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $apoteks = MasterApotek::whereNotIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $tanggal = date('Y-m-d');
        $detail_transfer_outlets = $transfer_outlet->detail_transfer_outlet;

        $var = 0;
        if($transfer_outlet->id_apotek_nota != session('id_apotek_active')) {
            session()->flash('error', 'Anda tidak mempunyai hak akses pada nota ini!');
            return redirect('transfer_outlet')->with('message', 'Anda tidak mempunyai hak akses pada nota ini!');
        }
        return view('transfer_outlet.edit')->with(compact('transfer_outlet', 'apoteks', 'detail_transfer_outlets', 'var', 'apotek', 'inisial'));
    }

    public function show($id) {

    }

    public function update(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $transfer_outlet = TransaksiTO::find($id);
            $transfer_outlet->fill($request->except('_token'));
            $detail_transfer_outlets = $request->detail_transfer_outlet;

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $apoteks = MasterApotek::whereNotIn('id', [$apotek->id])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
            $tanggal = date('Y-m-d');

            $validator = $transfer_outlet->validate();
            if($validator->fails()){
                return view('transfer_outlet.edit')->with(compact('transfer_outlet', 'apoteks', 'detail_transfer_outlets', 'var', 'apotek', 'inisial'))->withErrors($validator);
            }else{
                //$transfer_outlet->save_from_array($detail_transfer_outlets, 0);
                $apotek = MasterApotek::find(session('id_apotek_active'));
                $inisial = strtolower($apotek->nama_singkat);

                /*$apotek2 = MasterApotek::find($transfer_outlet->id_apotek_tujuan);
                $inisial2 = strtolower($apotek2->nama_singkat);*/
                $total_nota = 0;
                foreach ($detail_transfer_outlets as $detail_transfer_outlet) {
                    if($detail_transfer_outlet['id'] != "") {
                        $obj = TransaksiTODetail::find($detail_transfer_outlet['id']);
                        $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                       // $stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->first();
                        $selisih = $obj->jumlah - $detail_transfer_outlet['jumlah'];
                        $selisih_format = abs($selisih);
                        if($selisih <= 0) { // plus
                            $id_jenis_revisi = 2;
                            $stok_now = $stok_before->stok_akhir-$selisih_format;
                           // $stok_now2 = $stok_before2->stok_akhir+$selisih_format;
                        } else {
                            $id_jenis_revisi = 1;
                            $stok_now = $stok_before->stok_akhir+$selisih_format;
                           // $stok_now2 = $stok_before2->stok_akhir-$selisih_format;
                        }

                        if($stok_now < 0) {
                            session()->flash('error', 'Data tidak bisa disimpan, stok minus jika data ini disimpan!');
                            return redirect('transfer_outlet')->with('message', 'Sukses menyimpan data');
                        } else {
                            # update ke table stok harga
                            DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                            # create histori
                            DB::table('tb_histori_stok_'.$inisial)->insert([
                                'id_obat' => $obj->id_obat,
                                'jumlah' => $selisih_format,
                                'stok_awal' => $stok_before->stok_akhir,
                                'stok_akhir' => $stok_now,
                                'id_jenis_transaksi' => 8, //batal transfer keluar
                                'id_transaksi' => $obj->id,
                                'batch' => null,
                                'ed' => null,
                                'created_at' => date('Y-m-d H:i:s'),
                                'created_by' => Auth::user()->id
                            ]);

                            // turn off -> because add konfirmasi transfer barang
                            # update ke table stok harga
                            /*DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                            # create histori
                            DB::table('tb_histori_stok_'.$inisial2)->insert([
                                'id_obat' => $obj->id_obat,
                                'jumlah' => $selisih_format,
                                'stok_awal' => $stok_before2->stok_akhir,
                                'stok_akhir' => $stok_now2,
                                'id_jenis_transaksi' => 7, //batal transfer masuk
                                'id_transaksi' => $obj->id,
                                'batch' => null,
                                'ed' => null,
                                'created_at' => date('Y-m-d H:i:s'),
                                'created_by' => Auth::user()->id
                            ]);*/

                            $obj->harga_outlet = $detail_transfer_outlet['harga_outlet'];
                            $obj->jumlah = $detail_transfer_outlet['jumlah'];
                            $obj->total = $detail_transfer_outlet['harga_outlet'] * $detail_transfer_outlet['jumlah'];
                            $obj->save();
                            $total_nota = $total_nota+$obj->total;
                        }
                    } else {
                        $obj = new TransaksiTODetail;
                        $obj->id_nota = $transfer_outlet->id;
                        $obj->id_obat = $detail_transfer_outlet['id_obat'];
                        $obj->harga_outlet = $detail_transfer_outlet['harga_outlet'];
                        $obj->jumlah = $detail_transfer_outlet['jumlah'];
                        $obj->total = $detail_transfer_outlet['harga_outlet'] * $detail_transfer_outlet['jumlah'];
                        $obj->created_by = Auth::user()->id;
                        $obj->created_at = date('Y-m-d H:i:s');
                        $obj->updated_at = date('Y-m-d H:i:s');
                        $obj->updated_by = '';
                        $obj->is_deleted = 0;

                        $obj->save();
                        $total_nota = $total_nota+$obj->total;

                        $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                        $stok_now = $stok_before->stok_akhir-$obj->jumlah;

                        # update ke table stok harga
                        DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                        # create histori
                        DB::table('tb_histori_stok_'.$inisial)->insert([
                            'id_obat' => $obj->id_obat,
                            'jumlah' => $obj->jumlah,
                            'stok_awal' => $stok_before->stok_akhir,
                            'stok_akhir' => $stok_now,
                            'id_jenis_transaksi' => 4, //transfer keluar
                            'id_transaksi' => $obj->id,
                            'batch' => null,
                            'ed' => null,
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => Auth::user()->id
                        ]);

                        // turn off -> because add konfirmasi transfer barang
                        /*$stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->first();
                        $stok_now2 = $stok_before2->stok_akhir+$obj->jumlah;

                        # update ke table stok harga
                        DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                        # create histori
                        DB::table('tb_histori_stok_'.$inisial2)->insert([
                            'id_obat' => $obj->id_obat,
                            'jumlah' => $obj->jumlah,
                            'stok_awal' => $stok_before2->stok_akhir,
                            'stok_akhir' => $stok_now2,
                            'id_jenis_transaksi' => 3, //transfer keluar
                            'id_transaksi' => $obj->id,
                            'batch' => null,
                            'ed' => null,
                            'created_at' => date('Y-m-d H:i:s'),
                            'created_by' => Auth::user()->id
                        ]);*/
                    }
                }

                $transfer_outlet->total = $total_nota;
                $transfer_outlet->save();

                DB::commit();
                session()->flash('success', 'Sukses memperbaharui data!');
                return redirect('transfer_outlet')->with('message', 'Sukses menyimpan data');
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('transfer_outlet');
        }
    }

    public function destroy($id) {
        DB::beginTransaction(); 
        try{
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $to = TransaksiTO::find($id);
            $to->is_deleted = 1;
            $to->deleted_at = date('Y-m-d H:i:s');
            $to->deleted_by = Auth::user()->id;
            $apotek2 = MasterApotek::find($to->id_apotek_tujuan);
            $inisial2 = strtolower($apotek2->nama_singkat);

            $detail_transfer_outlets = TransaksiTODetail::where('id_nota', $to->id)->get();
            foreach ($detail_transfer_outlets as $key => $val) {
                $detail_transfer_outlet = TransaksiTODetail::find($val->id);
                $detail_transfer_outlet->is_deleted = 1;
                $detail_transfer_outlet->deleted_at = date('Y-m-d H:i:s');
                $detail_transfer_outlet->deleted_by = Auth::user()->id;
                $detail_transfer_outlet->save();

                $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
                $jumlah = $detail_transfer_outlet->jumlah;
                $stok_now = $stok_before->stok_akhir+$jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $detail_transfer_outlet->id_obat,
                    'jumlah' => $jumlah,
                    'stok_awal' => $stok_before->stok_akhir,
                    'stok_akhir' => $stok_now,
                    'id_jenis_transaksi' => 17, //hapus tranfer keluar
                    'id_transaksi' => $detail_transfer_outlet->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);  

                // turn off -> because add konfirmasi transfer barang
                /*$stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
                $stok_now2 = $stok_before2->stok_akhir-$detail_transfer_outlet->jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial2)->insert([
                    'id_obat' => $detail_transfer_outlet->id_obat,
                    'jumlah' => $detail_transfer_outlet->jumlah,
                    'stok_awal' => $stok_before2->stok_akhir,
                    'stok_akhir' => $stok_now2,
                    'id_jenis_transaksi' => 16, //hapus transfer masuk
                    'id_transaksi' => $detail_transfer_outlet->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);*/
            }
            
            if($to->save()){
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('transfer_outlet');
        }
    }

    public function find_ketentuan_keyboard(){
        return view('transfer_outlet._form_ketentuan_keyboard');
    }

    public function edit_detail(Request $request){
        $id = $request->id;
        $no = $request->no;
        $detail = TransaksiTODetail::find($id);
        return view('
            transfer_outlet._form_edit_detail')->with(compact('detail', 'no'));
    }

    public function hapus_detail($id) {
        DB::beginTransaction(); 
        try{
            $detail_transfer_outlet = TransaksiTODetail::find($id);
            $detail_transfer_outlet->is_deleted = 1;
            $detail_transfer_outlet->deleted_at= date('Y-m-d H:i:s');
            $detail_transfer_outlet->deleted_by = Auth::user()->id;

            $transfer_outlet = TransaksiTO::find($detail_transfer_outlet->id_nota);
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $apotek2 = MasterApotek::find($transfer_outlet->id_apotek_tujuan);
            $inisial2 = strtolower($apotek2->nama_singkat);

            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
            $jumlah = $detail_transfer_outlet->jumlah;
            $stok_now = $stok_before->stok_akhir+$jumlah;

            # update ke table stok harga
            DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_stok_'.$inisial)->insert([
                'id_obat' => $detail_transfer_outlet->id_obat,
                'jumlah' => $jumlah,
                'stok_awal' => $stok_before->stok_akhir,
                'stok_akhir' => $stok_now,
                'id_jenis_transaksi' => 17, //hapus tranfer keluar
                'id_transaksi' => $detail_transfer_outlet->id,
                'batch' => null,
                'ed' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);  

            // turn off -> because add konfirmasi transfer barang
            /*$stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
            $stok_now2 = $stok_before2->stok_akhir-$detail_transfer_outlet->jumlah;

            # update ke table stok harga
            DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_stok_'.$inisial2)->insert([
                'id_obat' => $detail_transfer_outlet->id_obat,
                'jumlah' => $detail_transfer_outlet->jumlah,
                'stok_awal' => $stok_before2->stok_akhir,
                'stok_akhir' => $stok_now2,
                'id_jenis_transaksi' => 16, //hapus transfer masuk
                'id_transaksi' => $detail_transfer_outlet->id,
                'batch' => null,
                'ed' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);*/


            $total = TransaksiTODetail::select([
                                DB::raw('SUM(total) as total_all')
                                ])
                                ->where('id', '!=', $detail_transfer_outlet->id)
                                ->where('id_nota', $detail_transfer_outlet->id_nota)
                                ->where('is_deleted', 0)
                                ->first();
            $y = 0;
            if($total->total_all == 0 OR $total->total_all == '') {
                $y = 0;
            } else {
                $y = $total->total_all;
            }

            if($y == 0) {
                $transfer_outlet->total = $y;
                $transfer_outlet->is_deleted = 1;
                $transfer_outlet->deleted_at= date('Y-m-d H:i:s');
                $transfer_outlet->deleted_by = Auth::user()->id;
            }

            if($detail_transfer_outlet->save()){
                $transfer_outlet->save();
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('transfer_dokter');
        }
    }

    public function cetak_nota(Request $request)
    {   
        $transfer_outlet = TransaksiTO::where('id', $request->id)->first();
        $detail_transfer_outlets = TransaksiTODetail::select(['tb_detail_nota_transfer_outlet.*',
                                                 DB::raw('(tb_detail_nota_transfer_outlet.jumlah * tb_detail_nota_transfer_outlet.harga_outlet) as total')])
                                               ->where('tb_detail_nota_transfer_outlet.id_nota', $transfer_outlet->id)
                                               ->where('tb_detail_nota_transfer_outlet.is_deleted', 0)
                                               ->get();

        return view('transfer_outlet._form_cetak_nota')->with(compact('transfer_outlet', 'detail_transfer_outlets'));
    } 

    public function load_data_nota_print($id) {
        $no = 0;

        $nota = TransaksiTO::find($id);
        $detail_transfer_outlets = TransaksiTODetail::where('id_nota', $nota->id)->where('is_deleted', 0)->get();
        $apotek = $nota->apotek_tujuan;
        $apotek_asal = $nota->apotek_asal;

        $nama_apotek = strtoupper($apotek_asal->nama_panjang);
        $nama_apotek_singkat = strtoupper($apotek_asal->nama_singkat);

        $nama_singkat_tujuan = strtoupper($apotek->nama_singkat);

        $a = str_pad("",40," ", STR_PAD_LEFT)."\n".
             str_pad("APOTEK BWF-".$nama_apotek, 40," ", STR_PAD_BOTH)."\n".
             str_pad($apotek_asal->alamat, 40," ", STR_PAD_BOTH)."\n".
             str_pad("Telp. ". $apotek_asal->telepon, 40," ", STR_PAD_BOTH);
        $a = $a."\n".
        "----------------------------------------\n".
        "No. Nota  : ".$nama_apotek_singkat."-".$nota['id']."\n".
        "Tanggal   : ".Carbon::parse($nota['created_at'])->format('d-m-Y H:i:s')."\n".
        "AP Tujuan : ".$apotek->nama_panjang."\n".
        "----------------------------------------\n";

        $b="\n".
        "        ".$nama_apotek_singkat.",               Kurir,       \n".
        "                                        \n".
        "                                        \n".
        "                                        \n".
        "(-----------------)  (-----------------)\n";

        $b=$b."\n".
        "       Kurir,                ".$nama_singkat_tujuan.",       \n".
        "                                        \n".
        "                                        \n".
        "                                        \n".
        "(-----------------)  (-----------------)\n";

        
        $total_belanja = 0;
        foreach ($detail_transfer_outlets as $key => $val) {
            $no++;
            $total_1 = $val->jumlah * $val->harga_outlet;
            $total_belanja = $total_belanja + $total_1;
            
          /*  $printer -> setJustification( Printer::JUSTIFY_LEFT );
            $printer -> text($no.".");
            $printer -> text("(".$val['id_obat'].")");
            $printer -> text($obat['nama']."\n");
            $printer -> text("     ".$val['jumlah']."X".number_format($val['harga_jual'],0,',',',')." (-".number_format($val['diskon'],0,',',',').")"." = Rp ".number_format($total_2,0,',',',')."\n");
*/
            $a=$a.
                str_pad($no.".".$val->obat->nama, 40," ", STR_PAD_RIGHT)."\n ".                 
                //str_pad(" (diskon ".number_format($diskon, 0, '.', ',')."%)",11," ", STR_PAD_LEFT)."\n ".
                str_pad(number_format($val->harga_outlet, 0, '.', ','), 7," ", STR_PAD_LEFT).
                str_pad(" x ",3," ", STR_PAD_LEFT).
                str_pad(number_format($val->jumlah, 0, '.', ','),9," ", STR_PAD_RIGHT).
                str_pad("= ",3," ", STR_PAD_LEFT).str_pad("Rp ". number_format($total_1, 0, '.', ','),10," ", STR_PAD_LEFT)."\n";

        }

        $a=$a.
            "----------------------------------------\n".
            "Total     : Rp ".number_format($total_belanja,0,',',',')."\n".
            "----------------------------------------\n";
        $a=$a.$b."\n".
            "----------------------------------------\n";
        $a=$a.str_pad("~ Selamat bekerja ~", 40," ", STR_PAD_BOTH);
        $a=$a."\n".
            "----------------------------------------\n";


        $b=$a.str_pad("",40," ", STR_PAD_LEFT)."\n"."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT);       
            
            print_r($b) ;
    }

    public function pencarian_obat() {
        return view('transfer_outlet.pencarian_obat');
    }

    public function list_pencarian_obat(Request $request) {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiTODetail::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_detail_nota_transfer_outlet.*', 'a.nama'])
        ->join('tb_m_obat as a', 'a.id', 'tb_detail_nota_transfer_outlet.id_obat')
        ->join('tb_nota_transfer_outlet as b', 'b.id', 'tb_detail_nota_transfer_outlet.id_nota')
        ->where(function($query) use($request){
            $query->where('tb_detail_nota_transfer_outlet.is_deleted','=','0');
            $query->where('b.id_apotek_nota','=',session('id_apotek_active'));
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
            $info = '<small>AP Tujuan : '.$data->nota->apotek_tujuan->nama_panjang.'</small>';
            return $data->nama.'<br>'.$info;
        })  
        ->editcolumn('total', function($data) {
            $total = ($data->jumlah*$data->harga_outlet);
            $str_ = '';
            $str_ = $data->jumlah.' X Rp '.number_format($data->harga_outlet, 2).' = Rp '.number_format($total, 2);
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
        $start = date_create("2021-01-01");
        $end = date_create("2021-01-10");
        $rekaps = TransaksiTO::select([
                                    DB::raw('@rownum  := @rownum  + 1 AS no'),
                                    'tb_nota_transfer_outlet.*'
                                ])
                                ->where(function($query) use($request){
                                    $query->where('tb_nota_transfer_outlet.is_deleted','=','0');
                                    $query->where('tb_nota_transfer_outlet.id_apotek_nota','=',session('id_apotek_active'));
                                    $query->where('tb_nota_transfer_outlet.id','LIKE',($request->id > 0 ? $request->id : '%'.$request->id.'%'));
                                    $query->where('tb_nota_transfer_outlet.id_apotek_tujuan','LIKE',($request->id_apotek_tujuan > 0 ? $request->id_apotek_tujuan : '%'.$request->id_apotek_tujuan.'%'));
                                    if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                                        $query->where('tb_nota_transfer_outlet.created_at','>=', $request->tgl_awal);
                                        $query->where('tb_nota_transfer_outlet.created_at','<=', $request->tgl_akhir);
                                    }
                                })
                                ->groupBy('tb_nota_transfer_outlet.id')
                                ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $x = $rekap->detail_transfer_total[0];
                    $collection[] = array(
                        $no,
                        $rekap->created_at,
                        $rekap->apotek_asal->nama_singkat,
                        $rekap->apotek_tujuan->nama_singkat,
                        $x->total,
                        "Rp ".number_format($x->total,2),
                        $rekap->keterangan
                    );
                }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['No', 'Tanggal', 'AP Asal', 'AP Tujuan', 'Total', 'Total (Rp)', 'Keterangan'];
                    } 

                    /*public function columnFormats(): array
                    {
                        return [
                            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
                            'C' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
                        ];
                    }*/

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 15,
                            'D' => 15,
                            'E' => 25,
                            'F' => 25,
                            'G' => 70,            
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
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Rekap Transfer Outlet.xlsx");
    }

    public function change_apotek(Request $request) {
        $transfer_outlet = TransaksiTO::find($request->id_transfer);
        $apoteks      = MasterApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        /*$apoteks->prepend('-- Pilih Apotek --','');*/
        return view('transfer_outlet._change_apotek')->with(compact('transfer_outlet', 'apoteks'));
    }


    public function update_apotek(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $transfer_outlet = TransaksiTO::find($id);

            if($request->id_apotek_awal != $request->id_apotek_akhir) {
                $detail_transfer_outlets = $transfer_outlet->detail_transfer_outlet;
                $apotek_awal = MasterApotek::find($request->id_apotek_awal);
                $inisial_awal = strtolower($apotek_awal->nama_singkat);

                $apotek_akhir = MasterApotek::find($request->id_apotek_akhir);
                $inisial_akhir = strtolower($apotek_akhir->nama_singkat);

                foreach ($detail_transfer_outlets as $key => $detail_transfer_outlet) {
                    // create histori stok hapus data pembelian dengan id_apotek_awal
                    $stok_before_awal = DB::table('tb_m_stok_harga_'.$inisial_awal)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
                    $jumlah = $detail_transfer_outlet->jumlah;
                    $stok_now_awal = $stok_before_awal->stok_akhir-$jumlah;

                    # update ke table stok harga
                    DB::table('tb_m_stok_harga_'.$inisial_awal)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before_awal->stok_akhir, 'stok_akhir'=> $stok_now_awal, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                    # create histori
                    DB::table('tb_histori_stok_'.$inisial_awal)->insert([
                        'id_obat' => $detail_transfer_outlet->id_obat,
                        'jumlah' => $jumlah,
                        'stok_awal' => $stok_before_awal->stok_akhir,
                        'stok_akhir' => $stok_now_awal,
                        'id_jenis_transaksi' => 28, //hapus tranfer keluar -> ganti apotek
                        'id_transaksi' => $detail_transfer_outlet->id,
                        'batch' => null,
                        'ed' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id
                    ]);  

                    // turn off -> because add konfirmasi transfer barang
                    // create gistori stok yang baru dengan id_apotek_baru
                    /*$stok_before_akhir = DB::table('tb_m_stok_harga_'.$inisial_akhir)->where('id_obat', $detail_transfer_outlet->id_obat)->first();
                    $jumlah = $detail_transfer_outlet->jumlah;
                    $stok_now_akhir = $stok_before_akhir->stok_akhir+$jumlah;

                    # update ke table stok harga
                    DB::table('tb_m_stok_harga_'.$inisial_akhir)->where('id_obat', $detail_transfer_outlet->id_obat)->update(['stok_awal'=> $stok_before_akhir->stok_akhir, 'stok_akhir'=> $stok_now_akhir, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                    # create histori
                    DB::table('tb_histori_stok_'.$inisial_akhir)->insert([
                        'id_obat' => $detail_transfer_outlet->id_obat,
                        'jumlah' => $jumlah,
                        'stok_awal' => $stok_before_akhir->stok_akhir,
                        'stok_akhir' => $stok_now_akhir,
                        'id_jenis_transaksi' => 29, //penambahan tranfer keluar -> ganti apotek
                        'id_transaksi' => $detail_transfer_outlet->id,
                        'batch' => null,
                        'ed' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id
                    ]); */
                }

                $transfer_outlet->id_apotek_tujuan = $request->id_apotek_akhir;
                $transfer_outlet->updated_at= date('Y-m-d H:i:s');
                $transfer_outlet->updated_by = Auth::user()->id;

                if($transfer_outlet->save()){
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
            return redirect('transfer_outlet/'.$id.'/edit');
        }
    }

    public function change_obat(Request $request) {
        $detail_transfer_outlet = TransaksiTODetail::find($request->id_detail_transfer);
        $obats      = MasterObat::where('is_deleted', 0)->pluck('nama', 'id');
        $no = $request->no;

        return view('transfer_outlet._change_obat')->with(compact('detail_transfer_outlet', 'obats', 'no'));
    }


    public function update_obat(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $detail_transfer_outlet = TransaksiTODetail::find($id);
            $transfer_outlet = TransaksiTO::find($detail_transfer_outlet->id_nota);
            $apotek = MasterApotek::find($transfer_outlet->id_apotek_nota);
            $inisial = strtolower($apotek->nama_singkat);

            if($request->id_obat_awal != $request->id_obat_akhir) {
                // create histori stok dengan id_obat_awal
                $stok_before_awal = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_awal)->first();
                $jumlah = $detail_transfer_outlet->jumlah;
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
                    'id_transaksi' => $detail_transfer_outlet->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);  

                // turn off -> because add konfirmasi transfer barang
                // create histori stok dengan id_obat_akhir
                /*$stok_before_akhir = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $request->id_obat_akhir)->first();
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
                    'id_transaksi' => $detail_transfer_outlet->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]); */

                $detail_transfer_outlet->id_obat = $request->id_obat_akhir;
                $detail_transfer_outlet->updated_at= date('Y-m-d H:i:s');
                $detail_transfer_outlet->updated_by = Auth::user()->id;

                if($detail_transfer_outlet->save()){
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
            return redirect('transfer_outlet/'.$id.'/edit');
        }
    }

    public function open_list_harga(Request $request) {
        $id_obat = $request->id_obat;
        $obat = MasterObat::find($id_obat);
        return view('transfer_outlet._dialog_open_list_harga')->with(compact('id_obat', 'obat'));
    }

    public function list_data_harga_obat(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_histori_harga_'.$inisial.'')->select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_histori_harga_'.$inisial.'.*', 'users.nama as oleh'])
                ->join('users', 'users.id', '=', 'tb_histori_harga_'.$inisial.'.created_by')
                ->where('tb_histori_harga_'.$inisial.'.id_obat', $request->id_obat);
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request, $barcode){
            $query->where(function($query) use($request, $barcode){
                $query->orwhere('b.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('b.barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('harga_beli_ppn', function($data){
            return 'Rp '.number_format($data->harga_beli_ppn, 2, '.', ','); 
        }) 
        ->editcolumn('harga_beli', function($data){
            return 'Rp '.number_format($data->harga_beli, 2, '.', ','); 
        }) 
        ->editcolumn('harga_jual', function($data){
            return 'Rp '.number_format($data->harga_jual, 2, '.', ','); 
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="add_harga_item('.$data->id_obat.', '.$data->harga_beli_ppn.')" data-toggle="tooltip" data-placement="top" title="Tambah Item"><i class="fa fa-plus"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['harga_beli_ppn', 'action'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function konfirmasi_barang() {
        $apoteks = MasterApotek::where('is_deleted', 0)->get();
        return view('transfer_outlet.konfirmasi_barang')->with(compact('apoteks'));
    }

    public function list_konfirmasi_barang(Request $request)
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
        $data = TransaksiTO::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_transfer_outlet.*', 
        ])
        ->where(function($query) use($request, $tanggal){
            $query->where('tb_nota_transfer_outlet.is_deleted','=','0');
            $query->where('tb_nota_transfer_outlet.is_status', 0);
            $query->where('tb_nota_transfer_outlet.id_apotek_tujuan','=',session('id_apotek_active'));
            $query->where('tb_nota_transfer_outlet.id','LIKE',($request->id > 0 ? $request->id : '%'.$request->id.'%'));
            if($request->tgl_awal != "") {
                $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                $query->whereDate('tb_nota_transfer_outlet.created_at','>=', $tgl_awal);
            }

            if($request->tgl_akhir != "") {
                $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                $query->whereDate('tb_nota_transfer_outlet.created_at','<=', $tgl_akhir);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                //$query->orwhere('tb_nota_transfer_outlet.no_faktur','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('id_apotek_asal', function($data){
            return $data->apotek_asal->nama_singkat;
        })
        ->editcolumn('id_apotek_tujuan', function($data){
            return $data->apotek_tujuan->nama_singkat;
        })
        ->editcolumn('is_lunas', function($data){
            if($data->is_lunas == 0) {
                return '<span class="text-danger" data-toggle="tooltip" data-placement="top" title="Faktur Belum Dibayar">Belum Dibayar</span>';
            } else {
                return '<span class="text-success" data-toggle="tooltip" data-placement="top" title="Faktur Sudah Dibayar"></i> Lunas</span>';
            }
        })     
        ->editcolumn('is_status', function($data){
            if($data->is_status == 0) {
                return '<span class="text-danger" data-toggle="tooltip" data-placement="top" title="Item obat belum diterima">Belum Diterima</span>';
            } else {
                return '<span class="text-success" data-toggle="tooltip" data-placement="top" title="Item obat sudah diterima"></i> Sudah Diterima</span>';
            }
        })    
        ->editcolumn('total', function($data) {
            if($data->total == null OR $data->total == 0) {
                $total = $data->detail_transfer_total[0]->total;
            } else {
                $total = $data->total;
            }

            return 'Rp '.number_format($total, 2);
        })  
        ->addcolumn('action', function($data) use($hak_akses){
            $btn = '<div class="btn-group">';
            $btn .= '<a href="'.url('/transfer_outlet/konfirm/'.$data->id).'" title="Konfirmasi Barang" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Konfirmasi Barang"><i class="fa fa-check"></i> Konfirmasi</span></a>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'is_status', 'is_lunas', 'id_apotek_asal', 'id_apotek_tujuan', 'total'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function konfirm($id) {
        $transfer_outlet = TransaksiTO::find($id);
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $apotek_asal = MasterApotek::find($transfer_outlet->id_apotek_asal);
        $inisial_asal = strtolower($apotek_asal->nama_singkat);
        $apoteks = MasterApotek::whereNotIn('id', [$transfer_outlet->id_apotek_asal])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $tanggal = date('Y-m-d');
        $detail_transfer_outlets = $transfer_outlet->detail_transfer_outlet;

        $var = 0;
        if($transfer_outlet->id_apotek_tujuan != session('id_apotek_active')) {
            session()->flash('error', 'Anda tidak mempunyai hak akses untuk melakukan konfirmasi pada nota ini!');
            return redirect('transfer_outlet/konfirmasi_barang')->with('message', 'Anda tidak mempunyai hak akses untuk melakukan konfirmasi pada nota ini!');
        }
        return view('transfer_outlet.konfirm')->with(compact('transfer_outlet', 'apoteks', 'detail_transfer_outlets', 'var', 'apotek', 'inisial', 'apotek_asal', 'inisial_asal'));
    }

    public function konfirm_update(Request $request, $id) {
        DB::beginTransaction(); 
        try{
            $transfer_outlet = TransaksiTO::find($id);
            $detail_transfer_outlets = $request->detail_transfer_outlet;
            $is_status = $request->is_status;
            $apotek1 = MasterApotek::find($transfer_outlet->id_apotek_asal);
            $inisial1 = strtolower($apotek1->nama_singkat);

            $apotek2 = MasterApotek::find($transfer_outlet->id_apotek_tujuan);
            $inisial2 = strtolower($apotek2->nama_singkat);
            $i = 0;
            foreach ($detail_transfer_outlets as $key => $detail_transfer_outlet) { 
                if(isset($detail_transfer_outlet['record'])) {
                    $obj = TransaksiTODetail::find($detail_transfer_outlet['id']);
                    $obj->is_status = $is_status;
                    $obj->konfirm_at = date('Y-m-d H:i:s');
                    $obj->konfirm_by = Auth::user()->id;
                    $obj->save();

                    // jika barang diterima buat histori
                    if($obj->is_status == 1) {
                        // turn off -> because add konfirmasi transfer barang
                        $stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->first();
                        $outlet1 = DB::table('tb_m_stok_harga_'.$inisial1)->where('id_obat', $obj->id_obat)->first();

                        $stok_now2 = $stok_before2->stok_akhir+$obj->jumlah;

                        # update ke table stok harga
                        DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                        # create histori
                        DB::table('tb_histori_stok_'.$inisial2)->insert([
                            'id_obat' => $obj->id_obat,
                            'jumlah' => $obj->jumlah,
                            'stok_awal' => $stok_before2->stok_akhir,
                            'stok_akhir' => $stok_now2,
                            'id_jenis_transaksi' => 3, //transfer masuk
                            'id_transaksi' => $obj->id,
                            'batch' => null,
                            'ed' => null,
                            'created_at' => $transfer_outlet->created_at,
                            'created_by' => $transfer_outlet->created_by
                        ]);

                        if($stok_before2->harga_beli_ppn != $outlet1->harga_beli_ppn) {
                            $data_histori_ = array('id_obat' => $obj->id_obat, 'harga_beli_awal' => $stok_before2->harga_beli, 'harga_beli_akhir' => $outlet1->harga_beli, 'harga_jual_awal' => $stok_before2->harga_jual, 'harga_jual_akhir' => $stok_before2->harga_jual, 'created_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'));

                            // update harga obat
                            DB::table('tb_histori_harga_'.$inisial2.'')->insert($data_histori_);
                            DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->update(['updated_at' => date('Y-m-d H:i:s'), 'harga_beli' => $outlet1->harga_beli, 'harga_beli_ppn' => $outlet1->harga_beli_ppn, 'updated_by' => Auth::user()->id]);
                        }
                    }
                    $i++;
                }
            }

            if($i > 0) {
                $total_to = TransaksiTODetail::where('id_nota', $id)->where('is_deleted', 0)->count();
                $check_to_diterima = TransaksiTODetail::where('id_nota', $id)->where('is_deleted', 0)->where('is_status', 1)->count();
                if($total_to == $check_to_diterima) {
                    $transfer_outlet->is_status = 1;
                    $transfer_outlet->complete_at = date('Y-m-d H:i:s');
                    $transfer_outlet->complete_by = Auth::user()->id;
                    $transfer_outlet->save();
                }

                DB::commit();
                session()->flash('success', 'Sukses mengkonfirmasi data transfer masuk!');
                return redirect('transfer_outlet/konfirm/'.$id)->with('message', 'Sukses mengkonfirmasi data transfer masuk!');
            } else {
                session()->flash('error', 'Gagal mengkonfirmasi data transfer masuk!');
                return redirect('transfer_outlet/konfirm/'.$id)->with('message', 'Gagal mengkonfirmasi data transfer masuk!');
            }

        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('transfer_outlet/konfirm/'.$id);
        }
    }
}

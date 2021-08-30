<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\MasterApotek;
use App\MasterObat;
use App\DefectaOutlet;
use App\MasterSatuan;
use App\MasterSuplier;
use App\MasterStatusOrder;

use App;
use Datatables;
use DB;
use Auth;
class T_DefectaController extends Controller
{
    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 
        =======================================================================================
    */
    public function index()
    {
        return view('defecta.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 
        =======================================================================================
    */
    public function list_defecta(Request $request)
    {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        DB::statement(DB::raw('set @rownum = 0'));
        $data = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode'
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->where(function($query) use($request){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
        });

        $btn_set = '';
        if ($request->input('s_is_kirim')=='1') {
            $data->where('tb_defecta_outlet.is_kirim', 1);
            $btn_set = '
                <button type="submit" class="btn btn-warning w-md m-b-5 pull-right animated fadeInLeft" onclick="send_multi_defecta(0)"><i class="fa fa-fw fa-undo"></i> UnSend defecta</button>';
           
        }
        else if ($request->input('s_is_kirim')=='2') {
            $data->where('tb_defecta_outlet.is_kirim', '!=', 1);
            $btn_set = '
                <button type="submit" class="btn btn-primary w-md m-b-5 pull-right animated fadeInLeft" onclick="send_multi_defecta(1)"><i class="fa fa-fw fa-paper-plane"></i> Send defecta</button>';
        }
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->addColumn('checkList', function ($data) {
            if($data->id_status == 0) {
                return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" value="'.$data->id.'"/>';
            }
        })
        ->editcolumn('total_stok', function($data) use($request, $inisial){
            return $data->total_stok;
        })
        ->editcolumn('total_buffer', function($data) use($request){
            return $data->total_buffer;
        })
        ->editcolumn('forcasting', function($data) use ($apotek){
            return $data->forcasting;
        })
        ->editcolumn('status', function($data) use ($apotek){
            return 'status';
        })
        ->addcolumn('action', function($data) use ($apotek){
           // $d_ = DefectaOutlet::where('id_stok_harga', $data->id)->where('id_apotek', $apotek->id)->first();
            $btn = '<div class="btn-group">';
            if ($data->is_kirim == 0){
                if($data->is_disabled == 1) {
                    $btn .= '<span class="text-info"><i class="fa fa-fw fa-info"></i>obat tidak aktif</span>';
                } else {
                    $btn .= '<span class="btn btn-primary btn-sm" onClick="send_defecta('.$data->id.', 1)" data-toggle="tooltip" data-placement="top" title="Kirim defecta ke purchasing"><i class="fa fa-paper-plane"></i></span>';
                }
            } else {
                if($data->id_status == 0) {
                     $btn .= '<span class="btn btn-warning btn-sm" onClick="send_defecta('.$data->id.', 0)" data-toggle="tooltip" data-placement="top" title="Batal kirim defecta ke purchasing"><i class="fa fa-undo"></i></span>';
                } else {
                    $btn .= '<span class="text-info"><i class="fa fa-fw fa-info"></i>sudah diproses oleh purchasing</span>';
                }
            }
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['checkList', 'total_stok', 'total_buffer', 'forcasting', 'action', 'DT_RowIndex', 'status'])
        ->addIndexColumn()
        ->with([
                'btn_set' => $btn_set,
            ])
        ->make(true);  
    }

    public function send_defecta(Request $request)
    {
        $i = 0;
        foreach ($request->input('id_defecta') as $key => $value) {
            DB::table('tb_defecta_outlet')->where('id', $value)->update(['is_kirim'=> $request->input('act')]);
            $i++;
        }


        if($i> 0){
            return response()->json(array(
                'submit' => 1,
                'success' => 'Kirim data berhasil dilakukan',
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
                'error' => 'Kirim data gagal dilakukan'
            ));
        }
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
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
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 22/02/2020
        =======================================================================================
    */
    public function edit($id)
    {
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 22/02/2020
        =======================================================================================
    */
    public function update(Request $request, $id)
    {
        $defecta = new DefectaOutlet;
        $defecta->fill($request->except('_token'));
        $defecta->id_obat = $request->id_obat;
        $defecta->total_stok = $request->stok;
        $defecta->total_buffer = $request->buffer;
        $defecta->forcasting = $request->forcasting;
        $defecta->id_apotek = $request->id_apotek;
        $defecta->jumlah_order = $defecta->jumlah_diajukan;
        $defecta->created_at = date('Y-m-d H:i:s');
        $defecta->created_by = Auth::id();

        $apotek = MasterApotek::find($request->id_apotek);
        $inisial = strtolower($apotek->nama_singkat);

        $validator = $defecta->validate();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            if($defecta->save()) {
                DB::table('tb_m_stok_harga_'.$inisial)->where('id', $request->id_stok_harga)->update(['is_defecta'=> 1]);
                echo json_encode(array('status' => 1));
            } else {
                echo json_encode(array('status' => 0));
            }
            
        }
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 22/02/2020
        =======================================================================================
    */
    public function destroy($id)
    {
    }

    public function input() {
    	$active_defecta = session('active_defecta');
        if(empty($active_defecta)) {
            $active_defecta = null;
        }

        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $data_ = DB::table('tb_m_stok_harga_'.$inisial)->orderBy('last_hitung', 'DESC')->first();
        $last_hitung = date('d-m-Y H:i:s', strtotime($data_->last_hitung));

        return view('defecta.input')->with(compact('last_hitung'));
    }

    public function list_defecta_input(Request $request) {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_m_stok_harga_'.$inisial)
                            ->select([
                                DB::raw('@rownum  := @rownum  + 1 AS no'),
                                'tb_m_stok_harga_'.$inisial.'.id',
                                'a.id as id_obat',
                                'a.nama',
                                'a.barcode',
                                'tb_m_stok_harga_'.$inisial.'.stok_akhir',
                                'tb_m_stok_harga_'.$inisial.'.total_buffer',
                                'tb_m_stok_harga_'.$inisial.'.forcasting'
                            ])
        ->leftjoin('tb_m_obat as a', 'a.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
        ->where(function($query) use($request, $inisial){
            $query->where('a.is_deleted','=','0');
            $query->where('tb_m_stok_harga_'.$inisial.'.is_disabled','=','0');
            $query->where('tb_m_stok_harga_'.$inisial.'.is_defecta','=','0');
            $query->where('tb_m_stok_harga_'.$inisial.'.stok_akhir', '<=', 'tb_m_stok_harga_'.$inisial.'.total_buffer');
        });
        $data->orderBy('tb_m_stok_harga_'.$inisial.'.id');
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('a.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('a.barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('total_stok', function($data) use($request, $inisial){
            return $data->stok_akhir;
        })
        ->editcolumn('total_buffer', function($data) use($request){
            return $data->total_buffer;
        })
        ->editcolumn('forcasting', function($data) use ($apotek){
            return $data->forcasting;
        })
        ->addcolumn('action', function($data) use ($apotek){
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary btn-sm" onClick="add_defecta('.$data->id.', 0)" data-toggle="tooltip" data-placement="top" title="Tambahkan ke defecta"><i class="fa fa-plus"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['total_stok', 'total_buffer', 'forcasting', 'action', 'DT_RowIndex'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function hitung() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $j = 0;
        $obats = DB::table('tb_m_stok_harga_'.$inisial)->get();
        foreach ($obats as $key => $obj) {
            $total_buffer = 0;
            $y1 = 0; 
            $y2 = 0;
            $y3 = 0;
            for ($i=1; $i <=3 ; $i++) { 
                $data_ = DB::table('tb_detail_nota_penjualan')
                ->select([
                            DB::raw('SUM(tb_detail_nota_penjualan.jumlah) AS jumlah')
                            ])
                ->leftJoin('tb_nota_penjualan','tb_nota_penjualan.id','=','tb_detail_nota_penjualan.id_nota')
                ->where(function ($query) use ($apotek, $i, $obj) {
                    $bulan_aktif = date('m') - $i;
                    $query->whereRaw('tb_detail_nota_penjualan.is_deleted = 0');
                    $query->whereRaw('tb_detail_nota_penjualan.id_obat = '.$obj->id_obat.'');
                    $query->whereRaw('tb_nota_penjualan.id_apotek_nota = '.$apotek->id.'');
                    $query->whereRaw('MONTH(tb_detail_nota_penjualan.created_at) ='.$bulan_aktif.'');
                })
                ->first();

                if($i==1) {
                    if($data_->jumlah != '' OR $data_->jumlah != null) {
                        $total_buffer = $data_->jumlah;
                    }
                }

                if($data_->jumlah != '' OR $data_->jumlah != null) {
                    if($i == 1) {
                        $y1 = $data_->jumlah;
                    } else if($i == 2) {
                        $y2 = $data_->jumlah;
                    } else if($i == 3) {
                        $y3 = $data_->jumlah;
                    }
                }
            }

            $x  = 3; //jumlah periode bulan yang digunakan
            $x1 = 1; $x2 = 2; $x3 = 3;

            $jum_x = 6; // jumlah dari bulan1 = 1, bulan2 = 2, bulan3 = 3
            $jum_x_kuadrat = 14; // jumlah x kuadrat dati tiap bulan
            $x_rata_rata = $jum_x/ $x;

            $jum_y = $y1 + $y2 + $y3; // ini jumlah penjualan selama 3 bulan terakhir
            $y_rata_rata =  $jum_y/$x;

            $jum_x_y = ($x1 * $y1) + ($x2 * $y2) + ($x3 * $y3); // jumlah pengalian diantara x dan y

            $b = ($jum_x_y - ($x * ($x_rata_rata * $y_rata_rata))) / ($jum_x_kuadrat - ($x * ($x_rata_rata * $x_rata_rata)));
            $a = $y_rata_rata - ($b * $x_rata_rata); // ini tuntuk mencari nilai dari a
            $y = $a + $b * 4; // a + bx;
            $abc = ceil($y);

            DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['total_buffer'=> $total_buffer, 'forcasting'=>$abc, 'last_hitung' => date('Y-m-d H:i:s')]);
            $j++;
        }

        session()->flash('success', 'Data yang dihitung sebanyak '.$j.' data!');
        return redirect('defecta');
    }

    public function add_defecta(Request $request){
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $id_stok_harga = $request->id_stok_harga;
        $id_defecta = $request->id_defecta;
        $data_ = DB::table('tb_m_stok_harga_'.$inisial)->where('id', $id_stok_harga)->first();
        $obat = MasterObat::find($data_->id_obat);

        if(!empty($id_defecta)) {
            $defecta = DefectaOutlet::where('id', $id_keputusan_order)->first();
        } else {
            $defecta = new DefectaOutlet;
        }

        $supliers      = MasterSuplier::where('is_deleted', 0)->pluck('nama', 'id');
        $supliers->prepend('-- Pilih Suplier --','');
    
        return view('defecta._form_defecta')->with(compact('defecta', 'apotek', 'obat', 'satuans', 'data_', 'supliers'));
    }

    // START PURCHASING
    public function data_masuk() {
        $statuss = MasterStatusOrder::pluck('nama', 'id');
        $statuss->prepend('-- Pilih Status --','');

        $apoteks = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');
        $cek_ = session('status_purchasing_aktif');
        $cek2_ = session('apotek_purchasing_aktif');

        if($cek_ == null) {
            session(['status_purchasing_aktif'=> 0]);
        }

        if($cek2_ == null) {
            session(['apotek_purchasing_aktif'=> '']);
        }

        $apotek_purchasing_aktif = session('apotek_purchasing_aktif');
        $status_purchasing_aktif = session('status_purchasing_aktif');
        return view('defecta.data_masuk')->with(compact('statuss', 'apoteks', 'apotek_purchasing_aktif', 'status_purchasing_aktif'));
    }

    public function list_defecta_masuk(Request $request)
    {
        $id_apotek = session('apotek_purchasing_aktif');
        $id_status = session('status_purchasing_aktif');
        $apoteks = MasterApotek::where('is_deleted', 0)->whereNotIn('id', [$id_apotek])->get();

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode',
                'c.nama_singkat',
                'd.nama as status',
                'd.icon',
                'd.class'
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->leftJoin('tb_m_apotek as c', 'c.id', '=', 'tb_defecta_outlet.id_apotek')
        ->leftjoin('tb_m_status_order as d', 'd.id', '=', 'tb_defecta_outlet.id_status')
        ->where(function($query) use($request, $id_apotek, $id_status){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
            if($id_apotek != '') {
                $query->where('tb_defecta_outlet.id_apotek','=', $id_apotek);
            }

            if($id_status != '') {
                $query->where('tb_defecta_outlet.id_status','=', $id_status);
            }
        });

        $btn_set = '';
        if ($request->input('id_status')=='0') {
            $data->where('tb_defecta_outlet.id_status', $id_status);
            $btn_set .= '
                <button type="submit" class="btn btn-info w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(1)"><i class="fa fa-fw fa-shopping-cart"></i> Order</button>';
            $btn_set .= '
                <button type="submit" class="btn btn-primary w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(2)"><i class="fa fa-fw fa-exchange-alt"></i> Transfer</button>';
            $btn_set .= '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(3)"><i class="fa fa-fw fa-times"></i> Tolak</button>';
        } else if ($request->input('id_status')=='1') {
            $data->where('tb_defecta_outlet.id_status', $id_status);
            $btn_set = '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta_draft(0)"><i class="fa fa-fw fa-undo"></i> Batal Order</button>';
        } else if ($request->input('id_status')=='2') {
            $data->where('tb_defecta_outlet.id_status', $id_status);
            $btn_set = '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta_draft(0)"><i class="fa fa-fw fa-undo"></i> Batal Transfer</button>';
        } else if ($request->input('id_status')=='3') {
            $data->where('tb_defecta_outlet.id_status', $id_status);
            $btn_set = '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta_draft(0)"><i class="fa fa-fw fa-undo"></i> Batal Tolak</button>';
        }
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('b.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('b.barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->addColumn('checkList', function ($data) {
            return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" data-id_apotek="'.$data->id_apotek.'" value="'.$data->id.'"/>';
        })
        ->editcolumn('total_stok', function($data) use($request){
            return $data->total_stok;
        })
        ->editcolumn('total_buffer', function($data) use($request){
            return $data->total_buffer;
        })
        ->editcolumn('forcasting', function($data) {
            return $data->forcasting;
        })
        ->editcolumn('apotek', function($data) {
            $status = '<small style="font-size:8pt;" class="badge bg-'.$data->class.'"><i class="fa '.$data->icon.'"></i> '.$data->status.'</small>';
            return $data->nama_singkat.'<br>'.$status;
        })
        ->editcolumn('suplier', function($data) {
            $cek_ = $data->data_pembelians;
            $jum = count($cek_);
            if($jum > 0) {
                $suplier = '';
                $i = 0;
                
                foreach ($cek_ as $key => $value) {
                    $i++;
                    $suplier .= $i.'. '.$value->nama;
                    if($i != $jum) {
                        $suplier .= '<br>';
                    }
                }
            } else {
                $suplier = '<small style="font-size:9pt;" class="text-red"><cite>Tidak ditemukan record pembelian</cite></small>';
            }
            return '<small style="font-size:9pt;">'.$suplier.'</small>';
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
        ->addcolumn('action', function($data) {
           // $d_ = DefectaOutlet::where('id_stok_harga', $data->id)->where('id_apotek', $apotek->id)->first();
            $btn = '<div class="btn-group">';
            if ($data->id_status == 0){
                $btn .= '<span class="btn btn-info btn-sm" onClick="set_status_defecta('.$data->id.', '.$data->id_apotek.', 1)" data-toggle="tooltip" data-placement="top" title="Order">Order</span>';
                $btn .= '<span class="btn btn-primary btn-sm" onClick="set_status_defecta('.$data->id.', '.$data->id_apotek.', 2)" data-toggle="tooltip" data-placement="top" title="Transfer">Transfer</span>';
            } else if($data->id_status == 1){
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', '.$data->id_apotek.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Order">Batal Order</span>';
            } else if($data->id_status == 2) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', '.$data->id_apotek.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Transfer">Batal Transfer</span>';
            } else if($data->id_status == 3) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', '.$data->id_apotek.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Tolak">Batal Tolak</span>';
            } else {
                $btn .= '<span class="text-info"><i class="fa fa-fw fa-info"></i>-</span>';
            }
            $btn .='</div>';
            return $btn;
        })    
        ->setRowClass(function ($data) use ($apoteks) {
            $ada_ = 0;
            $i = 0;
            foreach($apoteks as $obj) {
                $i++;
                $inisial = strtolower($obj->nama_singkat);
                $cek_ = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $data->id_obat)->first();
                if($cek_->stok_akhir > $data->total_stok) {
                    $ada_ = 1;
                }
            }

            if($ada_ == 1){
                return 'bg-secondary disabled color-palette';
            } else {
                return '';
            }
        })  
        ->rawColumns(['checkList', 'total_stok', 'total_buffer', 'forcasting', 'action', 'DT_RowIndex', 'status', 'apotek', 'nama', 'suplier'])
        ->addIndexColumn()
        ->with([
                'btn_set' => $btn_set,
            ])
        ->make(true);  
    }

    public function set_apotek_purchasing_aktif(Request $request) {
        session(['apotek_purchasing_aktif'=>$request->id_apotek]);
        echo 1;
    }

    public function set_status_purchasing_aktif(Request $request) {
        session(['status_purchasing_aktif'=>$request->id_status]);
        echo 1;
    }

    public function set_status_defecta(Request $request){
        $act = $request->input('act');
        $id_apotek = $request->input('id_apotek');
        $id_defecta = $request->input('id_defecta');

        $defectas = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode',
                'c.nama_singkat',
                'd.nama as status',
                'd.icon',
                'd.class'
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->leftJoin('tb_m_apotek as c', 'c.id', '=', 'tb_defecta_outlet.id_apotek')
        ->leftjoin('tb_m_status_order as d', 'd.id', '=', 'tb_defecta_outlet.id_status')
        ->where(function($query) use($request, $id_defecta){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
            $query->whereIn('tb_defecta_outlet.id', $id_defecta);
        })->get();

        $status = MasterStatusOrder::find($act);

        if($act == 0) {
            return view('konfirmasi_defecta._form_konfirmasi_draft')->with(compact('defectas', 'status'));
        } else if($act == 1) {
            $supliers = MasterSuplier::where('is_deleted', 0)->pluck('nama', 'id');
            $supliers->prepend('-- Pilih Suplier --','');

            return view('konfirmasi_defecta._form_konfirmasi_order')->with(compact('defectas', 'status', 'supliers'));
        } else if($act == 2){

            $apoteks = MasterApotek::whereNotIn('id', [session('id_apotek_active')])->where('is_deleted', 0)->pluck('nama_panjang', 'id');
            $apoteks->prepend('-- Pilih Apotek --','');
            return view('konfirmasi_defecta._form_konfirmasi_transfer')->with(compact('defectas', 'status', 'apoteks'));
        } else if($act == 3) {
            return view('konfirmasi_defecta._form_konfirmasi_tolak')->with(compact('defectas', 'status'));
        } else {
            echo "action yang anda pilih tidak sesuai.";
        }
    }

    public function set_status_defecta_back(Request $request)
    {
        $i = 0;
        foreach ($request->input('id_defecta') as $key => $value) {
            DB::table('tb_defecta_outlet')->where('id', $value)->update(['id_status'=> $request->input('act')]);
            $i++;
        }

        if($i> 0){
            if($request->input('act') == 0) {
                $message = 'obat yang telah dipilih berhasil dikembali ke status draft (belum ada keputusan).';
            } else if($request->input('act') == 1) {
                $message = 'obat yang telah dipilih berhasil diset ke status order.';
            } else if($request->input('act') == 2) {
                $message = 'obat yang telah dipilih berhasil diset ke status transfer.';
            } else if($request->input('act') == 3) {
                $message = 'obat yang telah dipilih berhasil diset ke status tolak.';
            }
            return response()->json(array(
                'submit' => 1,
                'message' => $message,
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
                'message' => 'Setting status gagal'
            ));
        }
    }

    public function konfirmasi_order(Request $request) {
        $defectas = $request->defecta;
        $i = 0;
        foreach ($defectas as $key => $val) {
            DB::table('tb_defecta_outlet')->where('id', $val)->update([
                'id_suplier_order'=> $request->id_suplier_order, 
                'id_status' => $request->id_status, 
                'last_update_status' => date('Y-m-d H:i:s')
            ]);
            $i++;
        }

        if($i > 0) {
            return response()->json(array(
                'submit' => 1,
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
            ));
        }
    }

    public function konfirmasi_transfer(Request $request) {
        $defectas = $request->defecta;
        $i = 0;
        foreach ($defectas as $key => $val) {
            DB::table('tb_defecta_outlet')->where('id', $val)->update([
                'id_apotek_transfer'=> $request->id_apotek_transfer, 
                'id_status' => $request->id_status, 
                'last_update_status' => date('Y-m-d H:i:s')
            ]);
            $i++;
        }

        if($i > 0) {
            return response()->json(array(
                'submit' => 1,
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
            ));
        }
    }

    public function konfirmasi_tolak(Request $request) {
        $defectas = $request->defecta;
        $i = 0;
        foreach ($defectas as $key => $val) {
            DB::table('tb_defecta_outlet')->where('id', $val)->update([
                'alasan_tolak'=> $request->alasan_tolak, 
                'id_status' => $request->id_status, 
                'last_update_status' => date('Y-m-d H:i:s')
            ]);
            $i++;
        }

        if($i > 0) {
            return response()->json(array(
                'submit' => 1,
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
            ));
        }
    }

    public function konfirmasi_draft(Request $request) {
        $defectas = $request->id_defecta;
        $i = 0;
        foreach ($defectas as $key => $val) {
            DB::table('tb_defecta_outlet')->where('id', $val)->update([
                'id_status' => $request->act, 
                'last_update_status' => date('Y-m-d H:i:s')
            ]);
            $i++;
        }

        if($i > 0) {
            return response()->json(array(
                'submit' => 1,
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
            ));
        }
    }
}

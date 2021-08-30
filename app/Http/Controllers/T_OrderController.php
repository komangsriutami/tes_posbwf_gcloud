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
use App\TransaksiOrder;
use App\TransaksiOrderDetail;

use App;
use Datatables;
use DB;
use Auth;
class T_OrderController extends Controller
{
    public function index() {
        $apoteks = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');

        $id_suplier = DefectaOutlet::select(['id_suplier_order'])->where('id_process', 0)->where('id_status', 1)->get();

        $supliers = MasterSuplier::whereIn('id', $id_suplier)->where('is_deleted', 0)->pluck('nama', 'id');
        $supliers->prepend('-- Pilih Suplier --','');

        $cek2_ = session('apotek_order_aktif');
        if($cek2_ == null) {
            session(['apotek_order_aktif'=> '']);
        }

        $cek_ = session('suplier_order_aktif');
        if($cek_ == null) {
            session(['suplier_order_aktif'=> '']);
        }

        $cek3_ = session('status_order_aktif');
        if($cek3_ == null) {
            session(['status_order_aktif'=> 0]);
        }

        $apotek_order_aktif = session('apotek_order_aktif');
        $suplier_order_aktif = session('suplier_order_aktif');
        $status_order_aktif = session('status_order_aktif');
        return view('order.index')->with(compact('apoteks', 'supliers', 'apotek_order_aktif', 'suplier_order_aktif', 'status_order_aktif'));
    }

    public function list_order(Request $request)
    {
        $id_apotek = session('apotek_order_aktif');
        $id_suplier = session('suplier_order_aktif');
        $id_process = session('status_order_aktif');

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode',
                'c.nama_singkat',
                'd.nama as suplier'
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->leftJoin('tb_m_apotek as c', 'c.id', '=', 'tb_defecta_outlet.id_apotek')
        ->leftJoin('tb_m_suplier as d', 'd.id', '=', 'tb_defecta_outlet.id_suplier_order')
        ->where(function($query) use($request, $id_apotek, $id_suplier){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
            $query->where('tb_defecta_outlet.id_status','=', 1);
            if($id_apotek != '') {
                $query->where('tb_defecta_outlet.id_apotek','=', $id_apotek);
            }
            if($id_suplier != '') {
                $query->where('tb_defecta_outlet.id_suplier_order','=', $id_suplier);
            }
        });

        $btn_set = ''; // 0 = belum ada proses, 1 = proses, 2 = complete

        if ($id_process=='0') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <button type="submit" class="btn btn-info w-md m-b-5 pull-right animated fadeInLeft" onclick="set_nota_order()"><i class="fa fa-fw fa-plus"></i> Nota Order</a>';
        } else if ($id_process=='1') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <a class="btn btn-secondary w-md m-b-5 pull-right animated fadeInLeft text-white" style="text-decoration: none;" href="'.url('/order/data_order').'"><i class="fa fa-fw fa-list"></i> List Data Order</a>';
        } else if ($id_process=='2') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <a class="btn btn-secondary w-md m-b-5 pull-right animated fadeInLeft text-white" style="text-decoration: none;" href="'.url('/order/data_order').'"><i class="fa fa-fw fa-list"></i> List Data Order</a>';
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
            return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" data-id_apotek="'.$data->id_apotek.'" data-id_suplier="'.$data->id_suplier_order.'" value="'.$data->id.'"/>';
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
            if($data->id_process == 0) {
                $status = '<small style="font-size:8pt;" class="badge bg-secondary"><i class="fa fa-question"></i></small>';
            } else if($data->id_process == 1) {
                $status = '<small style="font-size:8pt;" class="badge bg-info">Proses</small>';
            } else {
                $status = '<small style="font-size:8pt;" class="badge bg-primary">Complete</small>';
            }
            return $data->nama_singkat.'<br>'.$status;
    
        })
        ->editcolumn('id_suplier_order', function($data) {
            return $data->suplier;
        })
        ->rawColumns(['checkList', 'total_stok', 'total_buffer', 'forcasting', 'id_suplier_order', 'DT_RowIndex', 'apotek'])
        ->addIndexColumn()
        ->with([
                'btn_set' => $btn_set,
            ])
        ->make(true);  
    }

    public function create() {

    }

    public function store(Request $request) {
        $order = new TransaksiOrder;
        $order->fill($request->except('_token'));
        $order->id_suplier = $request->id_suplier;
        $order->id_apotek = $request->id_apotek;
        $detail_orders = $request->detail_order;
        $validator = $order->validate();
        if($validator->fails()){
            session()->flash('error', 'Gagal menyimpan data order!');
            return redirect('order');
        }else{
            $order->save_from_array($detail_orders,1);
            session()->flash('success', 'Sukses menyimpan data order!');
            return redirect('order');
        }
    }

    public function show($id) {

    }

    public function edit($id) {
        $order = TransaksiOrder::find($id);
        $supliers = MasterSuplier::whereIn('id', [$order->id_suplier])->where('is_deleted', 0)->pluck('nama', 'id');
        $apoteks = MasterApotek::whereIn('id', [$order->id_apotek])->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $tanggal = date('Y-m-d');
        $var = 2;

        return view('order.edit')->with(compact('order', 'supliers','apoteks', 'tanggal', 'var'));
    }

    public function update(Request $request, $id) {
        $order = TransaksiOrder::find($id);
        $order->fill($request->except('_token'));
        $detail_orders = $request->detail_order;
        $validator = $order->validate();
        if($validator->fails()){
            session()->flash('error', 'Gagal menyimpan data order!');
            return redirect('order/data_order');
        }else{
            $order->save_from_array($detail_orders,2);
            session()->flash('success', 'Sukses menyimpan data order!');
            return redirect('order/data_order');
        }
    }

    public function destroy($id)
    {
        $order = TransaksiOrder::find($id);
        $order->is_deleted = 1;
        $order->deleted_at = date('Y-m-d H:i:s');
        $order->deleted_by = Auth::user()->id;

        $detail_orders = $order->detail_order;

        foreach ($detail_orders as $key => $val) {
            $val->is_deleted = 1;
            $val->deleted_at = date('Y-m-d H:i:s');
            $val->deleted_by = Auth::user()->id;
            $val->save();
        }
        if($order->save()){
            echo 1;
        }else{
            echo 0;
        }
    }

    public function set_apotek_order_aktif(Request $request) {
        session(['apotek_order_aktif'=> $request->id_apotek]);
        echo $request->id_apotek;
    }

    public function set_suplier_order_aktif(Request $request) {
        session(['suplier_order_aktif'=> $request->id_suplier]);
        echo $request->id_suplier;
    }

    public function set_status_order_aktif(Request $request) {
        session(['status_order_aktif'=> $request->id_status]);
        echo $request->id_status;
    }

    public function list_order_defecta(Request $request)
    {
        $id_apotek = session('apotek_order_aktif');

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode',
                'c.nama_singkat',
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->leftJoin('tb_m_apotek as c', 'c.id', '=', 'tb_defecta_outlet.id_apotek')
        ->where(function($query) use($request, $id_apotek){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
            $query->where('tb_defecta_outlet.id_status','=', 1);
            if($id_apotek != '') {
                $query->where('tb_defecta_outlet.id_apotek','=', $id_apotek);
            }
        });

        $btn_set = '';
        if ($request->input('id_process')=='0') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <button type="submit" class="btn btn-info w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(1)"><i class="fa fa-fw fa-shopping-cart"></i> Order</button>';
            $btn_set .= '
                <button type="submit" class="btn btn-secondary w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(2)"><i class="fa fa-fw fa-exchange-alt"></i> Transfer</button>';
        } else if ($request->input('id_process')=='1') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set = '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(0)"><i class="fa fa-fw fa-undo"></i> Batal Order</button>';
        } else if ($request->input('id_process')=='2') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set = '
                <button type="submit" class="btn btn-danger w-md m-b-5 pull-right animated fadeInLeft" onclick="set_multi_status_defecta(0)"><i class="fa fa-fw fa-undo"></i> Batal Transfer</button>';
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
            if($data->id_status == 0) {
                return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" value="'.$data->id.'"/>';
            }
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
            return $data->nama_singkat;
        })
        ->editcolumn('status', function($data) {
            return '<span class="badge bg-"><i class="fa "></i> Process</span>';
        })
        ->addcolumn('action', function($data) {
           // $d_ = DefectaOutlet::where('id_stok_harga', $data->id)->where('id_apotek', $apotek->id)->first();
            $btn = '<div class="btn-group">';
            if ($data->id_status == 0){
                $btn .= '<span class="btn btn-info btn-sm" onClick="set_status_defecta('.$data->id.', 1)" data-toggle="tooltip" data-placement="top" title="Order">Order</span>';
                $btn .= '<span class="btn btn-secondary btn-sm" onClick="set_status_defecta('.$data->id.', 2)" data-toggle="tooltip" data-placement="top" title="Transfer">Transfer</span>';
            } else if($data->id_status == 1){
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Order">Batal Order</span>';
            } else if($data->id_status == 2) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Transfer">Batal Transfer</span>';
            } else if($data->id_status == 3) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="set_status_defecta('.$data->id.', 0)" data-toggle="tooltip" data-placement="top" title="Batal Tolak">Batal Tolak</span>';
            } else {
                $btn .= '<span class="text-info"><i class="fa fa-fw fa-info"></i>-</span>';
            }
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['checkList', 'total_stok', 'total_buffer', 'forcasting', 'action', 'DT_RowIndex', 'status', 'apotek'])
        ->addIndexColumn()
        ->with([
                'btn_set' => $btn_set,
            ])
        ->make(true);  
    }

    public function set_nota_order(Request $request){
        $id_apotek = explode(",", $request->input('id_apotek'));
        $id_suplier = explode(",", $request->input('id_suplier'));
        $id_defecta = explode(",", $request->input('id_defecta'));

        $supliers = MasterSuplier::whereIn('id', $id_suplier)->where('is_deleted', 0)->pluck('nama', 'id');
        $jum_suplier = count($supliers);
        if($jum_suplier > 1) {
            session()->flash('error', 'Data yang dipilih terdiri dari '.$jum_suplier.' suplier, pastikan data yang dipilih dari suplier yang sama!');
            return redirect('order');
        }

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

        $apoteks = MasterApotek::whereIn('id', $id_apotek)->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $order = new TransaksiOrder;
        $detail_orders = new TransaksiOrderDetail;
        $tanggal = date('Y-m-d');
        $var = 1;

        return view('order.create')->with(compact('defectas', 'supliers', 'order', 'detail_orders', 'tanggal', 'var', 'apoteks'));
    }

    public function cari_obat(Request $request) {
        $obat = MasterObat::where('barcode', $request->barcode)->first();

        $cek_ = 0;
        
        if(!empty($obat)) {
            $cek_ = 1;
        } 

        $data = array('is_data' => $cek_, 'obat'=> $obat);
        return json_encode($data);
    }

    public function open_data_obat(Request $request) {
        $id_apotek = $request->id_apotek;
        return view('order._dialog_open_obat')->with(compact('id_apotek'));
    }

    public function list_data_obat(Request $request)
    {
        $apotek = MasterApotek::find($request->id_apotek);
        $inisial = strtolower($apotek->nama_singkat);

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DB::table('tb_m_stok_harga_'.$inisial.' as a')
        ->select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'a.*',
                'b.nama',
                'b.barcode',
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'a.id_obat')
        ->where(function($query) use($request){
            $query->where('b.is_deleted','=','0');
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('b.nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('b.barcode','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->editcolumn('stok_akhir', function($data){
            return $data->stok_akhir; 
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="add_item_dialog('.$data->id_obat.')" data-toggle="tooltip" data-placement="top" title="Tambah Item"><i class="fa fa-plus"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['stok_akhir', 'action'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function cari_obat_dialog(Request $request) {
        $obat = MasterObat::find($request->id_obat);

        return json_encode($obat);
    }

    public function edit_detail(Request $request){
        $id = $request->id;
        $no = $request->no;
        $defecta = DefectaOutlet::select(['tb_defecta_outlet.*', 'a.nama'])
                        ->leftjoin('tb_m_obat as a', 'a.id', 'tb_defecta_outlet.id_obat')
                        ->where('tb_defecta_outlet.id', $id)
                        ->first();
        $apotek = MasterApotek::find($defecta->id_apotek);
        return view('order._form_edit_detail')->with(compact('defecta', 'no', 'apotek'));
    }

    public function update_defecta(Request $request, $id) {
        $defecta = DefectaOutlet::find($id);
        $defecta->jumlah_order = $request->jumlah_order;
        $defecta->komentar = $request->komentar;

        if($defecta->save()){

            return response()->json(array(
                'submit' => 1,
                'success' => 'Kirim data berhasil dilakukan'
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
                'error' => 'Kirim data gagal dilakukan'
            ));
        }
    }

    public function data_order() {
        $apoteks = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');

        $supliers = MasterSuplier::where('is_deleted', 0)->pluck('nama', 'id');
        $supliers->prepend('-- Pilih Suplier --','');

        return view('order.data_order')->with(compact('apoteks', 'supliers'));
    }

    public function list_data_order(Request $request) {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiOrder::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_nota_order.*',
                'a.nama_panjang as apotek',
                'b.nama as suplier'
        ])
        ->leftJoin('tb_m_apotek as a', 'a.id', '=', 'tb_nota_order.id_apotek')
        ->leftJoin('tb_m_suplier as b', 'b.id', '=', 'tb_nota_order.id_suplier')
        ->where(function($query) use($request){
            $query->where('tb_nota_order.is_deleted','=','0');
            if($request->id_apotek != '') {
                $query->where('tb_m_suplier.id_apotek','=', $request->id_apotek);
            }
            if($request->id_suplier != '') {
                $query->where('tb_m_suplier.id_suplier','=', $request->id_suplier);
            }
        });

        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
                $query->orwhere('a.nama_singkat','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('b.nama','LIKE','%'.$request->get('search')['value'].'%');
            });
        })   
        ->addColumn('checkList', function ($data) {
            return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" value="'.$data->id.'"/>';
        })
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<a href="'.url('/order/'.$data->id.'/edit').'" title="Edit Data" class="btn btn-primary btn-sm"><span data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i> Edit</span></a>';
            $btn .= '<span class="btn btn-danger btn-sm" onClick="delete_order('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-trash-alt"></i> Hapus</span>';
            $btn .='</div>';
            return $btn;
        })   
        ->rawColumns(['checkList', 'DT_RowIndex', 'apotek', 'action'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function edit_order(Request $request){
        $id = $request->id;
        $no = $request->no;
        $detail = TransaksiOrderDetail::select(['tb_detail_nota_order.*', 'a.nama'])
                        ->leftjoin('tb_m_obat as a', 'a.id', 'tb_detail_nota_order.id_obat')
                        ->where('tb_detail_nota_order.id', $id)
                        ->first();
        $order = TransaksiOrder::find($detail->id_nota);
        $apotek = MasterApotek::find($order->id_apotek);
        return view('order._form_edit_order')->with(compact('detail', 'no', 'apotek'));
    }

    public function update_order_detail(Request $request, $id) {
        $detail = TransaksiOrderDetail::find($id);
        $detail->jumlah = $request->jumlah;
        $detail->keterangan = $request->keterangan;

        if($detail->save()){
            return response()->json(array(
                'submit' => 1,
                'success' => 'Data berhasil disimpan'
            ));
        }
        else{
            return response()->json(array(
                'submit' => 0,
                'error' => 'Data gagal disimpan'
            ));
        }
    }
}

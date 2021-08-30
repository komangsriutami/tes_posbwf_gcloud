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
use App\TransaksiTransfer;
use App\TransaksiTransferDetail;

use App;
use Datatables;
use DB;
use Auth;
class T_TransferController extends Controller
{
    public function index() {
        $apoteks = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');

        $id_apotek_transfer = DefectaOutlet::select(['id_apotek_transfer'])->where('id_process', 0)->where('id_status', 2)->get();

        $apotek_transfers = MasterApotek::whereIn('id', $id_apotek_transfer)->where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apotek_transfers->prepend('-- Pilih Apotek Tujuan --','');

        $cek2_ = session('apotek_transfer_aktif');
        if($cek2_ == null) {
            session(['apotek_transfer_aktif'=> '']);
        }

        $cek_ = session('apotektrans_transfer_aktif');
        if($cek_ == null) {
            session(['apotektrans_transfer_aktif'=> '']);
        }

        $cek3_ = session('status_transfer_aktif');
        if($cek3_ == null) {
            session(['status_transfer_aktif'=> 0]);
        }

        $apotek_transfer_aktif = session('apotek_transfer_aktif');
        $apotektrans_transfer_aktif = session('apotektrans_transfer_aktif');
        $status_status_aktif = session('status_status_aktif');
        return view('transfer.index')->with(compact('apoteks', 'apotek_transfers', 'apotek_transfer_aktif', 'apotektrans_transfer_aktif', 'status_status_aktif'));
    }

    public function create() {

    }

    public function store(Request $request) {

    }

    public function edit($id) {

    }

    public function update(Request $request, $id) {

    }

    public function destroy($id) {
    	
    }

    public function set_apotek_transfer_aktif(Request $request) {
        session(['apotek_transfer_aktif'=> $request->id_apotek]);
        echo $request->id_apotek;
    }

    public function set_apotektrans_transfer_aktif(Request $request) {
        session(['apotektrans_transfer_aktif'=> $request->id_apotek_transfer]);
        echo $request->id_apotek_transfer;
    }

    public function set_status_transfer_aktif(Request $request) {
        session(['status_transfer_aktif'=> $request->id_status]);
        echo $request->id_status;
    }

    public function list_transfer(Request $request)
    {
        $id_apotek = session('apotek_transfer_aktif');
        $id_apotek_transfer = session('apotektrans_transfer_aktif');
        $id_process = session('status_status_aktif');

        DB::statement(DB::raw('set @rownum = 0'));
        $data = DefectaOutlet::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
                'tb_defecta_outlet.*',
                'b.nama',
                'b.barcode',
                'c.nama_singkat',
                'd.nama_singkat as apotek_transfer'
        ])
        ->leftjoin('tb_m_obat as b', 'b.id', '=', 'tb_defecta_outlet.id_obat')
        ->leftJoin('tb_m_apotek as c', 'c.id', '=', 'tb_defecta_outlet.id_apotek')
        ->leftJoin('tb_m_apotek as d', 'd.id', '=', 'tb_defecta_outlet.id_apotek_transfer')
        ->where(function($query) use($request, $id_apotek, $id_apotek_transfer){
            $query->where('tb_defecta_outlet.is_deleted','=','0');
            $query->where('tb_defecta_outlet.id_status','=', 2);
            if($id_apotek != '') {
                $query->where('tb_defecta_outlet.id_apotek','=', $id_apotek);
            }
            if($id_apotek_transfer != '') {
                $query->where('tb_defecta_outlet.id_apotek_transfer','=', $id_apotek_transfer);
            }
        });

        $btn_set = ''; // 0 = belum ada proses, 1 = proses, 2 = complete

        if ($id_process=='0') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <button type="submit" class="btn btn-info w-md m-b-5 pull-right animated fadeInLeft" onclick="set_nota_transfer()"><i class="fa fa-fw fa-plus"></i> Nota Transfer</a>';
        } else if ($id_process=='1') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <a class="btn btn-secondary w-md m-b-5 pull-right animated fadeInLeft text-white" style="text-decoration: none;" href="'.url('/transfer/data_transfer').'"><i class="fa fa-fw fa-list"></i> List Data Transfer</a>';
        } else if ($id_process=='2') {
            $data->where('tb_defecta_outlet.id_process', $id_process);
            $btn_set .= '
                <a class="btn btn-secondary w-md m-b-5 pull-right animated fadeInLeft text-white" style="text-decoration: none;" href="'.url('/transfer/data_transfer').'"><i class="fa fa-fw fa-list"></i> List Data Transfer</a>';
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
            return '<input type="checkbox" name="check_list" data-id="'.$data->id.'" data-id_apotek="'.$data->id_apotek.'" data-id_apotek_transfer="'.$data->id_apotek_transfer.'" value="'.$data->id.'"/>';
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
        ->editcolumn('id_apotek_transfer', function($data) {
            return $data->apotek_transfer;
        })
        ->rawColumns(['checkList', 'total_stok', 'total_buffer', 'forcasting', 'id_apotek_transfer', 'DT_RowIndex', 'apotek'])
        ->addIndexColumn()
        ->with([
                'btn_set' => $btn_set,
            ])
        ->make(true);  
    }

    public function set_nota_transfer(Request $request){
        $id_apotek = explode(",", $request->input('id_apotek'));
        $id_apotek_transfer = explode(",", $request->input('id_apotek_transfer'));
        $id_defecta = explode(",", $request->input('id_defecta'));

        $apotek_transfers = MasterApotek::whereIn('id', $id_apotek_transfer)->where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $jum_ = count($apotek_transfers);
        if($jum_ > 1) {
            session()->flash('error', 'Data yang dipilih terdiri dari '.$jum_suplier.' apotek tujuan, pastikan data yang dipilih dari apotek tujuan yang sama!');
            return redirect('transfer');
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
        $transfer = new TransaksiTransfer;
        $detail_transfers = new TransaksiTransferDetail;
        $tanggal = date('Y-m-d');
        $var = 1;

        return view('transfer.create')->with(compact('defectas', 'apotek_transfers', 'transfer', 'detail_transfers', 'tanggal', 'var', 'apoteks'));
    }

    public function permintaan_transfer() {
        echo "coming soon";
    }
}

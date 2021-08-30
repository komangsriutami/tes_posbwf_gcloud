<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\JurnalUmum;
use App\MasterKodeAkun;
use App\MasterKodeAkunSub;
use App;
use Datatables;
use DB;

class JurnalUmumController extends Controller
{
    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function index()
    {
        return view('jurnal_umum.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function list_jurnal_umum(Request $request)
    {
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = JurnalUmum::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_jurnal_umum.*'])
        ->where(function($query) use($request){
            $query->orwhere('tb_jurnal_umum.is_deleted','=','0');
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->where('nama','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('id_kode_akun', function($data){
            return $data->kode_akun->nama; 
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="edit_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
            $btn .= '<span class="btn btn-danger" onClick="delete_kartu('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action'])
        ->addIndexColumn()
        ->make(true);  
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function create()
    {
    	$jurnal_umum = new JurnalUmum;

    	$kode_akuns = MasterKodeAkun::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_akuns->prepend('-- Pilih Akun --','');

        $kode_sub_akuns = MasterKodeAkunSub::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_sub_akuns->prepend('-- Pilih Sub Akun --','');

        return view('jurnal_umum.create')->with(compact('jurnal_umum', 'kode_akuns', 'kode_sub_akuns'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function store(Request $request)
    {
        $jurnal_umum = new JurnalUmum;
        $jurnal_umum->fill($request->except('_token'));

        $kode_akuns = MasterKodeAkun::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_akuns->prepend('-- Pilih Akun --','');

        $kode_sub_akuns = MasterKodeAkunSub::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_sub_akuns->prepend('-- Pilih Sub Akun --','');

        $validator = $jurnal_umum->validate_kode_akun();
        if($validator->fails()){
            return view('jurnal_umum.create')->with(compact('jurnal_umum', 'kode_akuns', 'kode_sub_akuns'))->withErrors($validator);
        }else{
            $kartu->save_plus();
            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('jurnal_umum');
        }
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function show($id)
    {
        //
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function edit($id)
    {
        $jurnal_umum = JurnalUmum::find($id);

        $kode_akuns = MasterKodeAkun::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_akuns->prepend('-- Pilih Akun --','');

        $kode_sub_akuns = MasterKodeAkunSub::where('is_deleted', 0)->pluck('nama', 'id');
        $kode_sub_akuns->prepend('-- Pilih Sub Akun --','');

        return view('jurnal_umum.edit')->with(compact('jurnal_umum', 'kode_akuns', 'kode_sub_akuns'));
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function update(Request $request, $id)
    {
        $jurnal_umum = JurnalUmum::find($id);
        $jurnal_umum->fill($request->except('_token'));

        $validator = $jurnal_umum->validate_kode_akun();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            $jurnal_umum->save_edit();
            echo json_encode(array('status' => 1));
        }
    }

    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function destroy($id)
    {
        $jurnal_umum = JurnalUmum::find($id);
        $jurnal_umum->is_deleted = 1;
        $jurnal_umum->deleted_at = date('Y-m-d H:i:s');
        $jurnal_umum->deleted_by = Auth::user()->id;
        if($jurnal_umum->save()){
            echo 1;
        }else{
            echo 0;
        }
    }
}

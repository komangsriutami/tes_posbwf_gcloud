<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\MasterDokter;
use App\MasterGroupApotek;
use Auth;
use App;
use Datatables;
use DB;

class M_DokterController extends Controller
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
        return view('dokter.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function list_dokter(Request $request)
    {
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        $super_admin = session('super_admin');
        DB::statement(DB::raw('set @rownum = 0'));
        $data = MasterDokter::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_m_dokter.*'])
        ->where(function($query) use($request, $super_admin){
            $query->where('tb_m_dokter.is_deleted','=','0');
            if($super_admin == 0) {
                $query->where('tb_m_dokter.id_group_apotek', Auth::user()->id_group_apotek);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('telepon','LIKE','%'.$request->get('search')['value'].'%');
            });
        }) 
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="edit_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
            $btn .= '<span class="btn btn-danger" onClick="delete_dokter('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action'])
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
    	$dokter = new MasterDokter;

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        return view('dokter.create')->with(compact('dokter', 'group_apoteks'));
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
        $dokter = new MasterDokter;
        $dokter->fill($request->except('_token'));

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        $validator = $dokter->validate();
        if($validator->fails()){
            return view('dokter.create')->with(compact('dokter', 'group_apoteks'))->withErrors($validator);
        }else{
            $dokter->save_plus();
            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('dokter');
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
        $dokter 		= MasterDokter::find($id);

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        return view('dokter.edit')->with(compact('dokter', 'group_apoteks'));
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
        $dokter = MasterDokter::find($id);
        $dokter->fill($request->except('_token'));

        $validator = $dokter->validate();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            $dokter->save_edit();
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
        $dokter = MasterDokter::find($id);
        $dokter->is_deleted = 1;
        if($dokter->save()){
            echo 1;
        } else {
            echo 0;
        }
    }
}

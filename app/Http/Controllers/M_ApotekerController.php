<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\MasterApoteker;
use App\MasterAgama;
use App\MasterGolonganDarah;
use App\MasterJenisKelamin;
use App\MasterKewarganegaraan;
use App\MasterGroupApotek;
use App;
use Datatables;
use DB;
use Excel;
use Auth;

class M_ApotekerController extends Controller
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
        return view('apoteker.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function list_apoteker(Request $request)
    {
    	$order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        $super_admin = session('super_admin');
        DB::statement(DB::raw('set @rownum = 0'));
        $data = MasterApoteker::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_m_apoteker.*'])
        ->where(function($query) use($request, $super_admin){
            $query->where('tb_m_apoteker.is_deleted','=','0');
            if($super_admin == 0) {
                $query->where('tb_m_apoteker.id_group_apotek', Auth::user()->id_group_apotek);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables  
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('nama','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('nostra','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('telepon','LIKE','%'.$request->get('search')['value'].'%');
                $query->orwhere('email','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
                $btn .= '<span class="btn btn-primary" onClick="edit_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
                $btn .= '<span class="btn btn-danger" onClick="delete_apoteker('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i></span>';
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
        $apoteker = new MasterApoteker;

        $jenis_kelamins = MasterJenisKelamin::where('is_deleted', 0)->pluck('jenis_kelamin', 'id');
        $jenis_kelamins->prepend('-- Pilih Jenis Kelamin --','');

        $kewarganegaraans = MasterKewarganegaraan::where('is_deleted', 0)->pluck('kewarganegaraan', 'id');
        $kewarganegaraans->prepend('-- Pilih Kewarganegaraan --','');

        $agamas = MasterAgama::where('is_deleted', 0)->pluck('agama', 'id');
        $agamas->prepend('-- Pilih Agama --','');

        $golongan_darahs = MasterGolonganDarah::where('is_deleted', 0)->pluck('golongan_darah', 'id');
        $golongan_darahs->prepend('-- Pilih Golongan Darah --','');

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        return view('apoteker.create')->with(compact('apoteker', 'jenis_kelamins', 'agamas', 'kewarganegaraans', 'golongan_darahs', 'group_apoteks'));
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
        $apoteker = new MasterApoteker;
        $apoteker->fill($request->except('_token'));

        $jenis_kelamins = MasterJenisKelamin::where('is_deleted', 0)->pluck('jenis_kelamin', 'id');
        $jenis_kelamins->prepend('-- Pilih Jenis Kelamin --','');

        $kewarganegaraans = MasterKewarganegaraan::where('is_deleted', 0)->pluck('kewarganegaraan', 'id');
        $kewarganegaraans->prepend('-- Pilih Kewarganegaraan --','');

        $agamas = MasterAgama::where('is_deleted', 0)->pluck('agama', 'id');
        $agamas->prepend('-- Pilih Agama --','');

        $golongan_darahs = MasterGolonganDarah::where('is_deleted', 0)->pluck('golongan_darah', 'id');
        $golongan_darahs->prepend('-- Pilih Golongan Darah --','');

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        $validator = $apoteker->validate();
        if($validator->fails()){
            return view('apoteker.create')->with(compact('apoteker', 'jenis_kelamins', 'kewarganegaraans', 'agamas', 'golongan_darahs', 'group_apoteks'))->withErrors($validator);
        }else{
            $apoteker->tgl_lahir = date('Y-m-d', strtotime($apoteker->tgl_lahir));
            $apoteker->created_by = Auth::user()->id;
            $apoteker->save();
            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('apoteker');
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
        $apoteker        = MasterApoteker::find($id);

        $jenis_kelamins = MasterJenisKelamin::where('is_deleted', 0)->pluck('jenis_kelamin', 'id');
        $jenis_kelamins->prepend('-- Pilih Jenis Kelamin --','');

        $kewarganegaraans = MasterKewarganegaraan::where('is_deleted', 0)->pluck('kewarganegaraan', 'id');
        $kewarganegaraans->prepend('-- Pilih Kewarganegaraan --','');

        $agamas = MasterAgama::where('is_deleted', 0)->pluck('agama', 'id');
        $agamas->prepend('-- Pilih Agama --','');

        $golongan_darahs = MasterGolonganDarah::where('is_deleted', 0)->pluck('golongan_darah', 'id');
        $golongan_darahs->prepend('-- Pilih Golongan Darah --','');

        $group_apoteks      = MasterGroupApotek::where('is_deleted', 0)->pluck('nama_singkat', 'id');
        $group_apoteks->prepend('-- Pilih Group Apotek --','');

        return view('apoteker.edit')->with(compact('apoteker', 'jenis_kelamins', 'kewarganegaraans', 'agamas', 'golongan_darahs', 'group_apoteks'));
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
        $apoteker = MasterApoteker::find($id);
        $apoteker->fill($request->except('_token'));

        $validator = $apoteker->validate();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            $apoteker->tgl_lahir = date('Y-m-d', strtotime($apoteker->tgl_lahir));
            $apoteker->updated_by = Auth::user()->id;
            $apoteker->save();
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
        $apoteker = MasterApoteker::find($id);
        $apoteker->is_deleted = 1;
        if($apoteker->save()){
            echo 1;
        }else{
            echo 0;
        }
    }
}

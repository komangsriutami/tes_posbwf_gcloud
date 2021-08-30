<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\PenyesuaianStok;
use App\MasterObat;
use App\MasterApotek;
use Auth;
use App;
use Datatables;
use DB;

class PenyesuaianStokController extends Controller
{
	public function index() {

	}

    public function create($id) {
    	$penyesuaian_stok = new PenyesuaianStok;
    	$apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $id)->first();
        $obat = MasterObat::find($id);

        return view('penyesuaian_stok.create')->with(compact('penyesuaian_stok', 'obat', 'stok_harga'));
    }

    public function store(Request $request) {
    	$penyesuaian_stok = new PenyesuaianStok;
        $penyesuaian_stok->fill($request->except('_token'));

        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $stok_harga = DB::table('tb_m_stok_harga_'.$inisial.'')->where('id_obat', $request->id_obat)->first();
        $obat = MasterObat::find($request->id_obat);

        $id_jenis_transaksi = 0;
        if($penyesuaian_stok->stok_awal == $penyesuaian_stok->stok_akhir) {
        	session()->flash('error', 'Stok awal dan stok akhir jumlahnya sama, tidak dapat disesuikan!');
            return redirect('data_obat/penyesuaian_stok/'.$obat->id);
        } else {
	        if($penyesuaian_stok->stok_awal > $penyesuaian_stok->stok_akhir) {
	        	$penyesuaian_stok->id_jenis_penyesuaian = 2;
	        	$id_jenis_transaksi = 10;
	        } else {
	        	$penyesuaian_stok->id_jenis_penyesuaian = 1;
	        	$id_jenis_transaksi = 9;
	        }
        }

        $penyesuaian_stok->id_apotek_nota = session('id_apotek_active');
        $penyesuaian_stok->created_at = date('Y-m-d H:i:s');
        $penyesuaian_stok->created_by = Auth::user()->id;

        $validator = $penyesuaian_stok->validate();
        if($validator->fails()){
        	session()->flash('error', 'Data yang diinputkan tidak sesuai!');
            return redirect('penyesuaian_stok/create/'.$obat->id);
        }else{
            $penyesuaian_stok->save();
	        $stok_now = $penyesuaian_stok->stok_akhir;
	        $jumlah = $penyesuaian_stok->stok_akhir-$penyesuaian_stok->stok_awal;

	        # update ke table stok harga
	        DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obat->id)->update(['stok_awal'=> $stok_harga->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

	        # create histori
	        DB::table('tb_histori_stok_'.$inisial)->insert([
	            'id_obat' => $obat->id,
	            'jumlah' => $jumlah,
	            'stok_awal' => $stok_harga->stok_akhir,
	            'stok_akhir' => $stok_now,
	            'id_jenis_transaksi' => $id_jenis_transaksi,
	            'id_transaksi' => $penyesuaian_stok->id,
	            'batch' => null,
	            'ed' => null,
	            'created_at' => date('Y-m-d H:i:s'),
	            'created_by' => Auth::user()->id
	        ]);

            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('data_obat/penyesuaian_stok/'.$obat->id);
        }
    }  

    public function edit($id) {

    }

    public function update(Request $request, $id) {

    }

    public function destroy($id) {

    }
}

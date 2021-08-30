<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\SettingStokOpnam;
use App\MasterApotek;
use App\MasterObat;
use App;
use Datatables;
use DB;
use Excel;
use Auth;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SettingSOController extends Controller
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
        return view('setting_so.index');
    }


    /*
        =======================================================================================
        For     : 
        Author  : Surya Adiputra
        Date    : 4/03/2020
        =======================================================================================
    */
    public function list_setting_so(Request $request)
    {
    	$order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = SettingStokOpnam::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_setting_stok_opnam.*'])
        ->where(function($query) use($request){
            $query->where('tb_setting_stok_opnam.is_deleted','=','0');
        })->orderBy('id', 'ASC');
        
        $datatables = Datatables::of($data);
        return $datatables  
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('tgl_so','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->editcolumn('id_apotek', function($data) use($request){
            return $data->apotek->nama_panjang;
        })
        ->editcolumn('step', function($data) use($request){
            $now = $data->step+1;
            $btn = '';
            if($now == 1) {
                $btn .= '<span class="btn btn-info btn-sm" onClick="step_satu('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Step Satu"><i class="fa fa-reload"></i> Step 1 : Set Awal</span>';
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Dua"><i class="fa fa-reload"></i> Step 2 : Set Akhir</span>';
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Tiga"><i class="fa fa-reload"></i> Step 3 : Download Data</span>';
            } else if($now == 2) {
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Satu"><i class="fa fa-reload"></i> Step 1 : Set Awal</span>';
                $btn .= '<span class="btn btn-info btn-sm" onClick="step_dua('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Step Dua"><i class="fa fa-reload"></i> Step 2 : Set Akhir</span>';
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Tiga"><i class="fa fa-reload"></i> Step 3 : Download Data</span>';
            } else if($now == 3) {
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Satu"><i class="fa fa-reload"></i> Step 1 : Set Awal</span>';
                $btn .= '<span class="btn btn-default btn-sm" onClick="alert_check()" data-toggle="tooltip" data-placement="top" title="Step Dua"><i class="fa fa-reload"></i> Step 2 : Set Akhir</span>';
                $btn .= '<span class="btn btn-info btn-sm" onClick="download_akhir('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Step Tiga"><i class="fa fa-reload"></i> Step 3 : Download Data</span>';
            }
            
            return $btn;
        })
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary btn-sm" onClick="edit_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
            $btn .= '<span class="btn btn-danger btn-sm" onClick="delete_setting_so('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'step'])
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
        $setting_so = new SettingStokOpnam;
        $apoteks      = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');

        return view('setting_so.create')->with(compact('setting_so', 'apoteks'));
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
        $setting_so = new SettingStokOpnam;
        $setting_so->fill($request->except('_token'));

        $validator = $setting_so->validate();
        if($validator->fails()){
            $apoteks      = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
            $apoteks->prepend('-- Pilih Apotek --','');

            return view('setting_so.create')->with(compact('setting_so', 'apoteks'))->withErrors($validator);
        }else{
            $setting_so->created_by = Auth::user()->id;
            $setting_so->created_at = date('Y-m-d H:i:s');
            $setting_so->save();
            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('setting_so');
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
        $setting_so = SettingStokOpnam::find($id);
        $apoteks      = MasterApotek::where('is_deleted', 0)->pluck('nama_panjang', 'id');
        $apoteks->prepend('-- Pilih Apotek --','');

        return view('setting_so.edit')->with(compact('setting_so', 'apoteks'));
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
        $setting_so = SettingStokOpnam::find($id);
        $setting_so->fill($request->except('_token'));

        $validator = $setting_so->validate();
        if($validator->fails()){
            echo json_encode(array('status' => 0));
        }else{
            $setting_so->updated_by = Auth::user()->id;
            $setting_so->updated_at = date('Y-m-d H:i:s');
            $setting_so->save();
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
        $setting_so = SettingStokOpnam::find($id);
        $setting_so->is_deleted = 1;
        $setting_so->deleted_by = Auth::user()->id;
        $setting_so->deleted_at = date('Y-m-d H:i:s');

        if($setting_so->save()){
            echo 1;
        }else{
            echo 0;
        }
    }

    public function reload_data_awal(Request $request) {
        DB::beginTransaction(); 
        try{
            $setting_so = SettingStokOpnam::find($request->id);
            $apotek = MasterApotek::find($setting_so->id_apotek);
            $inisial = strtolower($apotek->nama_singkat);
            # set awal
            //DB::table('tb_m_stok_harga_'.$inisial)->update(['stok_awal_so'=> DB::raw('stok_akhir'), 'stok_akhir_so'=> 0, 'selisih' => 0]);
            $data = DB::table('tb_m_stok_harga_'.$inisial)->select(['id', 'id_obat', 'stok_akhir'])->get();
            
            foreach ($data as $key => $obj) {
                if($obj->id_obat != null) {
                $obat = MasterObat::find($obj->id_obat);
                    DB::table('tb_m_stok_harga_'.$inisial)->where('id', $obj->id)->update(['nama' => $obat->nama, 'barcode' => $obat->barcode, 'stok_awal_so'=> $obj->stok_akhir, 'stok_akhir_so'=> 0, 'selisih' => 0, 'total_penjualan_so' => 0, 'id_so' => $setting_so->id, 'is_so' => 0, 'so_at' => null, 'so_by' => null, 'so_by_nama' => null]);
                }
            }
            
            # update step
            $setting_so->step = $setting_so->step+1;
            $setting_so->updated_by = Auth::user()->id;
            $setting_so->updated_at = date('Y-m-d H:i:s');
            if($setting_so->save()){
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('setting_so');
        }
    }

    public function reload_data_akhir(Request $request) {
        DB::beginTransaction(); 
        try{
            $now = date('Y-m-d');
            $setting_so = SettingStokOpnam::find($request->id);
                if($now = $setting_so) {
                $apotek = MasterApotek::find($setting_so->id_apotek);
                $inisial = strtolower($apotek->nama_singkat);
                # buat histori
                $data = DB::table('tb_m_stok_harga_'.$inisial)->where('is_so', 0)->get();
                
                foreach ($data as $key => $obj) {
                    DB::table('tb_m_stok_harga_'.$inisial)->where('id', $obj->id)->update(['stok_awal'=> $obj->stok_awal_so, 'stok_akhir' => 0, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id, 'is_so' => 1, 'so_at' => date('Y-m-d H:i:s'), 'so_by' => Auth::user()->id, 'so_by_nama' => 'by sistem']);

                    DB::table('tb_histori_stok_'.$inisial)->insert([
                        'id_obat' => $obj->id_obat,
                        'jumlah' => $obj->stok_akhir_so,
                        'stok_awal' => $obj->stok_awal_so,
                        'stok_akhir' => 0,
                        'id_jenis_transaksi' => 11, //stok opnam
                        'id_transaksi' => $setting_so->id,
                        'batch' => null,
                        'ed' => null,
                        'created_at' => $obj->so_at,
                        'created_by' => $obj->so_by
                    ]);
                }

                # update step
                $setting_so->step = $setting_so->step+1;
                $setting_so->updated_by = Auth::user()->id;
                $setting_so->updated_at = date('Y-m-d H:i:s');
                if($setting_so->save()){
                    DB::commit();
                    echo 1;
                }else{
                    echo 0;
                }
            } else {
                echo 2;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('setting_so');
        }
    }

    public function export(Request $request) 
    {
        $setting_so = SettingStokOpnam::find($request->id);
        $apotek = MasterApotek::find($setting_so->id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
       
        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->select([
                            'tb_m_stok_harga_'.$inisial.'.*'
                    ])
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0)
                    ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;

                    $so = 'Tidak';
                    $stok_awal = $rekap->stok_awal_so;
                    $stok_akhir = $rekap->stok_akhir;
                    $selisih = $stok_awal-$stok_akhir;
                    if($rekap->is_so == 1) {
                        $so = 'Ya';
                        $stok_awal = $rekap->stok_awal_so;
                        $stok_akhir = $rekap->stok_akhir_so;
                        $selisih = $rekap->selisih;
                    }

                    if($stok_awal == 0) {
                        $stok_awal = '0';
                    }
                    if($stok_akhir == 0) {
                        $stok_akhir = '0';
                    }
                    if($selisih == 0) {
                        $selisih = '0';
                    }
                    
                    $collection[] = array(
                        $no,
                        $rekap->barcode,
                        $rekap->nama,
                        $rekap->harga_beli,
                        $rekap->harga_beli_ppn,
                        $rekap->harga_jual,
                        $stok_awal,
                        $stok_akhir,
                        $selisih,
                        $so,
                        $rekap->so_by_nama,
                        $rekap->so_at
                    );
                }

        return Excel::download(new class($collection) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {

                    public function __construct($collection)
                    {
                        $this->collection = $collection;
                    }

                    public function headings(): array
                    {
                        return ['No', 'Barcode', 'Nama Obat', 'Harga Beli', 'Harga Beli+PPN', 'Harga Jual', 'Stok Awal', 'Stok Akhir', 'Selisih', 'SO?', 'Update By', 'Update at'];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 40,
                            'D' => 20,
                            'E' => 20,
                            'F' => 20,
                            'G' => 10, 
                            'H' => 10, 
                            'I' => 10,  
                            'J' => 10,  
                            'K' => 30,   
                            'L' => 20,
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'D'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'E'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'I'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'J'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'L'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Data SO Apotek ".$apotek->nama_singkat.".xlsx");
    }
}

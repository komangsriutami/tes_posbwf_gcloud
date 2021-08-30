<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\TransaksiPO;
use App\TransaksiPODetail;
use App\MasterObat;
use App\MasterApotek;
use App\s;
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
class T_POController extends Controller
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
        return view('obat_operasional.index');
    }

    /*
        =======================================================================================
        For     : 
        Author  : Sri U.
        Date    : 07/11/2020
        =======================================================================================
    */
    public function list_obat_operasional(Request $request)
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
        $data = TransaksiPO::select([
                DB::raw('@rownum  := @rownum  + 1 AS no'),
	            'tb_nota_po.*', 
        ])
        ->where(function($query) use($request, $tanggal){
            $query->where('tb_nota_po.is_deleted','=','0');
            $query->where('tb_nota_po.id_apotek_nota','=',session('id_apotek_active'));
            $query->where('tb_nota_po.id','LIKE',($request->id > 0 ? $request->id : '%'.$request->id.'%'));
            if($request->tgl_awal != "") {
                $tgl_awal       = date('Y-m-d H:i:s',strtotime($request->tgl_awal));
                $query->whereDate('tb_nota_po.created_at','>=', $tgl_awal);
            }

            if($request->tgl_akhir != "") {
                $tgl_akhir      = date('Y-m-d H:i:s',strtotime($request->tgl_akhir));
                $query->whereDate('tb_nota_po.created_at','<=', $tgl_akhir);
            }
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request){
            $query->where(function($query) use($request){
            });
        })
        ->editcolumn('created_by', function($data) {
            return '<small>'.$data->created_oleh->nama.'</small>';
        }) 
        ->addcolumn('action', function($data) use($hak_akses){
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary btn-sm" onClick="cetak_nota('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Cetak Nota"><i class="fa fa-print"></i> Cetak</span>';
            $btn .= '<a href="'.url('/obat_operasional/'.$data->id.'/edit').'" title="Edit Data" class="btn btn-info btn-sm"><span data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i> Edit</span></a>';
            if($hak_akses == 1) {
                $btn .= '<span class="btn btn-danger btn-sm" onClick="delete_obat_operasional('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i> Hapus</span>';
            }
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action', 'created_by'])
        ->addIndexColumn()
        ->make(true);  
    }

    public function create() {
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $tanggal = date('Y-m-d');
        $obat_operasional = new TransaksiPO;
        $detail_obat_operasionals = new TransaksiPODetail;
        $var = 1;
        return view('obat_operasional.create')->with(compact('obat_operasional', 'detail_obat_operasionals', 'var', 'apotek', 'inisial'));
    }

    public function store(Request $request) {
        DB::beginTransaction(); 
        try{
            $obat_operasional = new TransaksiPO;
            $obat_operasional->fill($request->except('_token'));
            $obat_operasional->id_apotek_nota = session('id_apotek_active');
            $obat_operasional->tgl_nota = date('Y-m-d');
            $detail_obat_operasionals = $request->detail_obat_operasional;

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $tanggal = date('Y-m-d');

            $validator = $obat_operasional->validate();
            if($validator->fails()){
                $var = 0;
                return view('obat_operasional.create')->with(compact('obat_operasional', 'detail_obat_operasionals', 'var', 'apotek', 'inisial'))->withErrors($validator);
            }else{
                $obat_operasional->save_from_array($detail_obat_operasionals,1);
                DB::commit();
                session()->flash('success', 'Sukses menyimpan data!');
                return redirect('obat_operasional');
            } 
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('obat_operasional');
        }
    }

    public function edit($id) {
        $obat_operasional = TransaksiPO::find($id);
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);
        $tanggal = date('Y-m-d');

        $detail_obat_operasionals = $obat_operasional->detail_obat_operasional;

        $var = 0;
        return view('obat_operasional.edit')->with(compact('obat_operasional', 'detail_obat_operasionals', 'var', 'apotek', 'inisial'));
    }

    public function show($id) {

    }

    public function update(Request $request, $id) {
    	DB::beginTransaction(); 
        try{
	        $obat_operasional = TransaksiPO::find($id);
	        $obat_operasional->fill($request->except('_token'));
	        $detail_obat_operasionals = $request->detail_obat_operasional;

	        $apotek = MasterApotek::find(session('id_apotek_active'));
	        $inisial = strtolower($apotek->nama_singkat);
	        $tanggal = date('Y-m-d');

	        $validator = $obat_operasional->validate();
	        if($validator->fails()){
	            $var = 1;
	            return view('obat_operasional.edit')->with(compact('obat_operasional', 'detail_obat_operasionals', 'var', 'apotek', 'inisial'))->withErrors($validator);
	        }else{
	            $apotek = MasterApotek::find(session('id_apotek_active'));
	            $inisial = strtolower($apotek->nama_singkat);
	            $total_nota = 0;
	            foreach ($detail_obat_operasionals as $detail_obat_operasional) {
	                $obj = TransaksiPODetail::find($detail_obat_operasional['id']);
	                $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
	                $selisih = $obj->jumlah - $detail_obat_operasional['jumlah'];

                    $selisih_format = abs($selisih);
	                if($selisih <= 0) { //plus
	                    $id_jenis_transaksi = 19;
	                    $stok_now = $stok_before->stok_akhir-$selisih_format;
	                } else {
	                    $id_jenis_transaksi = 20;
	                    $stok_now = $stok_before->stok_akhir+$selisih_format;
	                }

	                if($stok_now < 0) {
	                    session()->flash('error', 'Data tidak bisa disimpan, stok minus jika data ini disimpan!');
	                    return redirect('obat_operasional')->with('message', 'Sukses menyimpan data');
	                } else {
	                    # update ke table stok harga
	                    DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

	                    # create histori
	                    DB::table('tb_histori_stok_'.$inisial)->insert([
	                        'id_obat' => $obj->id_obat,
	                        'jumlah' => $selisih_format,
	                        'stok_awal' => $stok_before->stok_akhir,
	                        'stok_akhir' => $stok_now,
	                        'id_jenis_transaksi' => $id_jenis_transaksi, 
	                        'id_transaksi' => $obj->id,
	                        'batch' => null,
	                        'ed' => null,
	                        'created_at' => date('Y-m-d H:i:s'),
	                        'created_by' => Auth::user()->id
	                    ]);

	                    $obj->harga_jual = $detail_obat_operasional['harga_jual'];
	                    $obj->jumlah = $detail_obat_operasional['jumlah'];
	                    $obj->total = $detail_obat_operasional['harga_jual'] * $detail_obat_operasional['jumlah'];
	                    $obj->save();
	                    $total_nota = $total_nota+$obj->total;
	                }
	            }

	            $obat_operasional->grand_total = $total_nota;
	            $obat_operasional->save();
	            DB::commit();
	            session()->flash('success', 'Sukses memperbaharui data!');
	            return redirect('obat_operasional')->with('message', 'Sukses menyimpan data');
	        }
	   	}catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('obat_operasional');
        }
    }

    public function destroy($id) {
        DB::beginTransaction(); 
        try{
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $to = TransaksiPO::find($id);
            $to->is_deleted = 1;
            $to->deleted_at = date('Y-m-d H:i:s');
            $to->deleted_by = Auth::user()->id;
            $to->grand_total = 0;
            
            $detail_obat_operasionals = TransaksiPODetail::where('id_nota', $to->id)->get();
            foreach ($detail_obat_operasionals as $key => $val) {
                $detail_obat_operasional = TransaksiPODetail::find($val->id);
                $detail_obat_operasional->is_deleted = 1;
                $detail_obat_operasional->deleted_at = date('Y-m-d H:i:s');
                $detail_obat_operasional->deleted_by = Auth::user()->id;
                $detail_obat_operasional->save();

                $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_obat_operasional->id_obat)->first();
                $selisih = $detail_obat_operasional->jumlah;

                $id_jenis_transaksi = 21;
                $stok_now = $stok_before->stok_akhir+$selisih;
                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_obat_operasional->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $detail_obat_operasional->id_obat,
                    'jumlah' => $selisih,
                    'stok_awal' => $stok_before->stok_akhir,
                    'stok_akhir' => $stok_now,
                    'id_jenis_transaksi' => $id_jenis_transaksi, 
                    'id_transaksi' => $detail_obat_operasional->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);
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
            return redirect('obat_operasional');
        }
    }

    public function find_ketentuan_keyboard(){
        return view('obat_operasional._form_ketentuan_keyboard');
    }

    public function edit_detail(Request $request){
        $id = $request->id;
        $no = $request->no;
        $detail = TransaksiPODetail::find($id);
        return view('obat_operasional._form_edit_detail')->with(compact('detail', 'no'));
    }

    public function hapus_detail($id) {
        DB::beginTransaction(); 
        try{
            $detail_obat_operasional = TransaksiPODetail::find($id);
            $detail_obat_operasional->is_deleted = 1;
            $detail_obat_operasional->deleted_at= date('Y-m-d H:i:s');
            $detail_obat_operasional->deleted_by = Auth::user()->id;
            $detail_obat_operasional->save();

            $apotek = MasterApotek::find(session('id_apotek_active'));
            $inisial = strtolower($apotek->nama_singkat);
            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_obat_operasional->id_obat)->first();
            $selisih = $detail_obat_operasional->jumlah;

            $id_jenis_transaksi = 21;
            $stok_now = $stok_before->stok_akhir+$selisih;
           
            # update ke table stok harga
            DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $detail_obat_operasional->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_stok_'.$inisial)->insert([
                'id_obat' => $detail_obat_operasional->id_obat,
                'jumlah' => $selisih,
                'stok_awal' => $stok_before->stok_akhir,
                'stok_akhir' => $stok_now,
                'id_jenis_transaksi' => $id_jenis_transaksi, 
                'id_transaksi' => $detail_obat_operasional->id,
                'batch' => null,
                'ed' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);

            $total = TransaksiPODetail::select([
                                DB::raw('SUM(total) as total_all')
                                ])
                                ->where('id', '!=', $detail_obat_operasional->id)
                                ->where('id_nota', $detail_obat_operasional->id_nota)
                                ->where('is_deleted', 0)
                                ->first();

            $y = 0;
            if($total->total_all == 0 OR $total->total_all == '') {
                $y = 0;
            } else {
                $y = $total->total_all;
            }

            $obat_operasional = TransaksiPO::find($detail_obat_operasional->id_nota);
            if($y == 0) {
                $obat_operasional->grand_total = $y;
                $obat_operasional->is_deleted = 1;
                $obat_operasional->deleted_at= date('Y-m-d H:i:s');
                $obat_operasional->deleted_by = Auth::user()->id;
            }   

            if($obat_operasional->save()){
                DB::commit();
                echo 1;
            }else{
                echo 0;
            }
        }catch(\Exception $e){
            DB::rollback();
            session()->flash('error', 'Error!');
            return redirect('obat_operasional');
        }
    }

    public function cetak_nota(Request $request)
    {   
        $obat_operasional = TransaksiPO::where('id', $request->id)->first();
        $detail_obat_operasionals = TransaksiPODetail::select(['tb_detail_nota_po.*'])
                                               ->where('tb_detail_nota_po.id_nota', $obat_operasional->id)
                                               ->get();

        return view('obat_operasional._form_cetak_nota')->with(compact('obat_operasional', 'detail_obat_operasionals'));
    } 

    public function load_data_nota_print($id) {
        $no = 0;

        $nota = TransaksiPO::find($id);
        $detail_obat_operasionals = TransaksiPODetail::where('id_nota', $nota->id)->get();
        $apotek = MasterApotek::find(session('id_apotek_active'));
	    $inisial = strtolower($apotek->nama_singkat);
        $nama_apotek = strtoupper($apotek->nama_panjang);
        $nama_apotek_singkat = strtoupper($apotek->nama_singkat);

        $a = str_pad("",40," ", STR_PAD_LEFT)."\n".
             str_pad("APOTEK BWF-".$nama_apotek, 40," ", STR_PAD_BOTH)."\n".
             str_pad($apotek->alamat, 40," ", STR_PAD_BOTH)."\n".
             str_pad("Telp. ". $apotek->telepon, 40," ", STR_PAD_BOTH);
        $a = $a."\n".
        "----------------------------------------\n".
        "No. Nota  : ".$nama_apotek_singkat."-".$nota['id']."\n".
        "Tanggal   : ".date_format($nota['created_at'],'d-m-Y H:i:s')."\n".
        "Kasir     : ".$nota->created_oleh->nama."\n".
        "Keterangan: ".$nota->keterangan."\n".
        "----------------------------------------\n";
/*
        $b=$b."\n".
        "       Kasir,                ".$nota->dokter->nama.",       \n".
        "                                        \n".
        "                                        \n".
        "                                        \n".
        "(-----------------)  (-----------------)\n";*/

        
        $total_belanja = 0;
        foreach ($detail_obat_operasionals as $key => $val) {
            $no++;
            $total_belanja = $total_belanja + $val->total;
            
            $a=$a.
                str_pad($no.".".$val->obat->nama, 40," ", STR_PAD_RIGHT)."\n ".                 
                //str_pad(" (diskon ".number_format($diskon, 0, '.', ',')."%)",11," ", STR_PAD_LEFT)."\n ".
                str_pad(number_format($val->harga_jual, 0, '.', ','), 7," ", STR_PAD_LEFT).
                str_pad(" x ",3," ", STR_PAD_LEFT).
                str_pad(number_format($val->jumlah, 0, '.', ','),9," ", STR_PAD_RIGHT).
                str_pad("= ",3," ", STR_PAD_LEFT).str_pad("Rp ". number_format($val->total, 0, '.', ','),10," ", STR_PAD_LEFT)."\n";

        }

        $a=$a.
            "----------------------------------------\n".
            "Total     : Rp ".number_format($total_belanja,0,',',',')."\n".
            "----------------------------------------\n";
        /*$a=$a.$b."\n".
            "----------------------------------------\n";*/
        $a=$a.str_pad("~ Selamat bekerja ~", 40," ", STR_PAD_BOTH);
        $a=$a."\n".
            "----------------------------------------\n";


        $b=$a.str_pad("",40," ", STR_PAD_LEFT)."\n"."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT)."\n".str_pad("",40," ", STR_PAD_LEFT);       
            
            print_r($b) ;
    }

    public function pencarian_obat() {
        return view('obat_operasional.pencarian_obat');
    }

    public function list_pencarian_obat(Request $request) {
        DB::statement(DB::raw('set @rownum = 0'));
        $data = TransaksiPODetail::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_detail_nota_po.*', 'a.nama'])
        ->join('tb_m_obat as a', 'a.id', 'tb_detail_nota_po.id_obat')
        ->join('tb_nota_po as b', 'b.id', 'tb_detail_nota_po.id_nota')
        ->where(function($query) use($request){
            $query->where('tb_detail_nota_po.is_deleted','=','0');
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
            $info = '<small>Keterangan : '.$data->keterangan.'</small>';
            return $data->nama.'<br>'.$info;
        })  
        ->editcolumn('total', function($data) {
            $str_ = '';
            $str_ = $data->jumlah.' X Rp '.number_format($data->harga_jual, 2).' = Rp '.number_format($data->total, 2);
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
        $rekaps = TransaksiPO::select([
                                    DB::raw('@rownum  := @rownum  + 1 AS no'),
                                    'tb_nota_po.*'
                                ])
                                ->where(function($query) use($request){
                                    $query->where('tb_nota_po.is_deleted','=','0');
                                    $query->where('tb_nota_po.id_apotek_nota','=',session('id_apotek_active'));
                                    $query->where('tb_nota_po.id','LIKE',($request->id > 0 ? $request->id : '%'.$request->id.'%'));
                                    $query->where('tb_nota_po.keterangan','LIKE',($request->keterangan > 0 ? $request->keterangan : '%'.$request->keterangan.'%'));
                                    if (!empty($request->tgl_awal) && !empty($request->tgl_akhir)) {
                                        $query->where('tb_nota_po.created_at','>=', $request->tgl_awal);
                                        $query->where('tb_nota_po.created_at','<=', $request->tgl_akhir);
                                    }
                                })
                                ->groupBy('tb_nota_po.id')
                                ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    $collection[] = array(
                        $no,
                        $rekap->created_at,
                        $rekap->created_oleh->nama,
                        $rekap->grand_total,
                        "Rp ".number_format($rekap->grand_total,2),
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
                        return ['No', 'Tanggal', 'Dokter', 'Total', 'Total (Rp)', 'Keterangan'];
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
                            'C' => 30,
                            'D' => 18,
                            'E' => 18,
                            'F' => 50,            
                        ];
                    }

                    public function styles(Worksheet $sheet)
                    {
                        return [
                            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'A'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'B'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            //'C'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            //'D'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'E'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Rekap Obat Operasional.xlsx");
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\StokLVDataTable;
use App\DataTables\StokLVDataTableEditor;
use App\MasterApotek;
use App\SettingStokOpnam;
use Datatables;
use DB;
use Excel;
use Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class S_LVController extends Controller
{
    public function index(StokLVDataTable $dataTable)
    {
        $nama_apotek_singkat_active = session('nama_apotek_singkat_active');
    	$id_apotek = session('id_apotek_active');
    	$now = date('Y-m-d');
    	$cek = SettingStokOpnam::where('id_apotek', $id_apotek)->where('tgl_so', $now)->first();
    	if($id_apotek != 1) {
    		$apotek = MasterApotek::find(1);
    		return view('so.page_not_select_apotek')->with(compact('apotek'));
    	} else {
    		if($cek == null) {
    			return view('so.page_not_setting_so');
    		} else {
                $cek_ = session('so_status_aktif');

                if($cek_ == null) {
                    session(['so_status_aktif'=> 1]);
                }
                
                session(['id_so'=> $cek->id]);
    			return $dataTable->render('so.lv');
    		}
    	}
    }

    public function set_so_status_aktif(Request $request) {
        session(['so_status_aktif'=>$request->so_status_aktif]);
        echo 1;
    }

    public function store(StokLVDataTableEditor $editor)
    {
        return $editor->process(request());
    }

    public function export(Request $request) 
    {
        $id_apotek = session('id_apotek_active');
        $apotek = MasterApotek::find($id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
       
        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->select([
                            'tb_m_stok_harga_'.$inisial.'.*', 
                            'tb_m_obat.nama', 
                            'tb_m_obat.barcode', 
                            'users.nama as so_by_name'
                    ])
                    ->leftJoin('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->leftJoin('users', 'users.id', '=', 'tb_m_stok_harga_'.$inisial.'.so_by')
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0)
                    ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    
                    $cek = DB::table('tb_histori_stok_'.$inisial.'')->where('id_obat', $rekap->id_obat)->where('id_jenis_transaksi', 11)->orderBy('created_at', 'DESC')->first();
                    $so = 'Tidak';
                    if(!empty($cek)) {
                        $tgl_cek = date('Y-m-d', strtotime($cek->created_at));
                        $tgl_rekap = date('Y-m-d', strtotime($rekap->updated_at));
                        if($tgl_rekap == $tgl_cek) {
                            $so = 'Ya';
                        }
                    }
                    
                    $collection[] = array(
                        $no,
                        $rekap->barcode,
                        $rekap->nama,
                        "Rp ".number_format($rekap->harga_beli,2),
                        "Rp ".number_format($rekap->harga_jual,2),
                        $rekap->stok_awal_so,
                        $rekap->stok_akhir_so,
                        $rekap->selisih,
                        $so,
                        $rekap->so_by_name,
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
                        return ['No', 'Barcode', 'Nama Obat', 'Harga Beli', 'Harga Jual', 'Stok Awal', 'Stok Akhir', 'Selisih', 'SO?', 'Update By', 'Update at'];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 40,
                            'D' => 20,
                            'E' => 20,
                            'F' => 10, 
                            'G' => 10, 
                            'H' => 10,  
                            'I' => 10,  
                            'J' => 30,   
                            'K' => 20,
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
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'I'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'K'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Data SO Apotek ".$apotek->nama_singkat.".xlsx");
    }


    public function export_awal(Request $request) 
    {
        $id_apotek = session('id_apotek_active');
        $apotek = MasterApotek::find($id_apotek);
        $inisial = strtolower($apotek->nama_singkat);
       
        $rekaps = DB::table('tb_m_stok_harga_'.$inisial.'')
                    ->select([
                            'tb_m_stok_harga_'.$inisial.'.*', 
                            'tb_m_obat.nama', 
                            'tb_m_obat.barcode', 
                            'users.nama as so_by_name'
                    ])
                    ->leftJoin('tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_'.$inisial.'.id_obat')
                    ->leftJoin('users', 'users.id', '=', 'tb_m_stok_harga_'.$inisial.'.so_by')
                    ->where('tb_m_stok_harga_'.$inisial.'.is_deleted', 0)
                    ->get();


                $collection = collect();
                $no = 0;
                $total_excel=0;
                foreach($rekaps as $rekap) {
                    $no++;
                    
                    $so = 'Tidak';
                    if($rekap->is_so ==1) {
                        if($tgl_rekap == $tgl_cek) {
                            $so = 'Ya';
                        }
                    }
                    
                    $collection[] = array(
                        $no,
                        $rekap->barcode,
                        $rekap->nama,
                        "Rp ".number_format($rekap->harga_beli,2),
                        "Rp ".number_format($rekap->harga_jual,2),
                        $rekap->stok_awal_so,
                        $rekap->stok_akhir_so,
                        $rekap->selisih,
                        $so,
                        $rekap->so_by_name,
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
                        return ['No', 'Barcode', 'Nama Obat', 'Harga Beli', 'Harga Jual', 'Stok Awal', 'Stok Akhir', 'Selisih', 'SO?', 'Update By', 'Update at'];
                    } 

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 8,
                            'B' => 20,
                            'C' => 40,
                            'D' => 20,
                            'E' => 20,
                            'F' => 10, 
                            'G' => 10, 
                            'H' => 10,  
                            'I' => 10,  
                            'J' => 30,   
                            'K' => 20,
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
                            'F'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'G'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'H'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'I'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                            'K'  => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
                        ];
                    }


                    public function collection()
                    {
                        return $this->collection;
                    }
        },"Data SO Apotek Awal".$apotek->nama_singkat.".xlsx");
    }
}

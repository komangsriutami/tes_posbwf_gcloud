<?php

namespace App\Exports;

use App\TransaksiPembelian;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use DB;

class PembelianExport implements FromQuery
//, WithHeadings, ShouldAutoSize, WithEvents
{
	use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
   public function __construct(string $id_apotek, string $id_suplier, string $start_date, string $end_date){
	    $this->id_apotek = $id_apotek;
	    $this->id_suplier = $id_suplier;
	    $this->start_date = $start_date;
	    $this->end_date = $end_date;
	}    

	/*public function headings(): array
	{
	    return [
	    'Date Created',
	    'MSISDN',
	    'game_code',
	    'Answer',
	    'Channel'
	    ];
	} */


    /*public function collection()
    {
    	$rekaps = TransaksiPembelian::select([
                            DB::raw('@rownum  := @rownum  + 1 AS no'),
                            'tb_nota_pembelian.*'])
                            ->where(function($query){
                                $query->where('tb_nota_pembelian.is_deleted','=','0');
                                $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                                $query->where('tb_nota_pembelian.id_apotek','LIKE',($this->id_apotek > 0 ? $this->id_apotek : '%'.$this->id_apotek.'%'));
                                $query->where('tb_nota_pembelian.id_suplier','LIKE',($this->id_suplier > 0 ? $this->id_suplier : '%'.$this->id_suplier.'%'));
                                $query->where('tb_nota_pembelian.is_lunas',0);
                                if (!empty($this->tgl_awal) && !empty($this->tgl_akhir)) {
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $this->tgl_awal);
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $this->tgl_akhir);
                                }
                            })
                            ->orderBy('tgl_jatuh_tempo','asc')
                            ->orderBy('id_suplier')
                            ->groupBy('tb_nota_pembelian.id')
                            ->get();

        return $rekaps;//TransaksiPembelian::all();
    }*/

    public function query()
	{
		$id_suplier = $this->id_suplier;
		$id_apotek = $this->id_apotek;
		$start_date = $this->start_date;
		$end_date = $this->end_date;
		$rekaps = TransaksiPembelian::select([
                            DB::raw('@rownum  := @rownum  + 1 AS no'),
                            'tb_nota_pembelian.*'])
                            ->where(function($query) use($id_suplier, $id_apotek, $start_date, $end_date){
                                $query->where('tb_nota_pembelian.is_deleted','=','0');
                                $query->where('tb_nota_pembelian.is_tanda_terima','=','1');
                                $query->where('tb_nota_pembelian.id_apotek','LIKE',($id_apotek > 0 ? $id_apotek : '%'.$id_apotek.'%'));
                                $query->where('tb_nota_pembelian.id_suplier','LIKE',($id_suplier > 0 ? $id_suplier : '%'.$id_suplier.'%'));
                                $query->where('tb_nota_pembelian.is_lunas',0);
                                if (!empty($start_date) && !empty($end_date)) {
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','>=', $start_date);
                                    $query->where('tb_nota_pembelian.tgl_jatuh_tempo','<=', $end_date);
                                }
                            })
                            ->orderBy('tgl_jatuh_tempo','asc')
                            ->orderBy('id_suplier')
                            //->groupBy('tb_nota_pembelian.id')
                            ->get();
                            
	    return $rekaps;
	}    
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;

class DefectaOutlet extends Model
{
    /* 
		Model 	: Untuk Defecta Outlet
		Author 	: 
		Date 	: 
	*/
    protected $table = 'tb_defecta_outlet';
    public $primaryKey = 'id';
    protected $fillable = ['id_obat', 'id_apotek', 'jumlah_diajukan', 'jumlah_order', 'komentar', 'total_stok', 'total_buffer', 'forcasting'];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'id_obat' => 'required',
            'id_apotek' => 'required|max:255',
            'jumlah_diajukan' => 'required',
            'total_stok' => 'required',
            'total_buffer' => 'required',
            'forcasting' => 'required',
        ]);
    }

    public function data_pembelians(){
        return $this->hasMany('App\TransaksiPembelianDetail', 'id_obat', 'id_obat')
                    ->select(['b.nama', 'tb_detail_nota_pembelian.id'])
                    ->join('tb_nota_pembelian as a', 'a.id', 'tb_detail_nota_pembelian.id_nota')
                    ->join('tb_m_suplier as b', 'b.id', 'a.id_suplier')
                    ->where('a.is_deleted', 0)
                    ->orderBy('a.id', 'desc')->limit(3);
    }
}

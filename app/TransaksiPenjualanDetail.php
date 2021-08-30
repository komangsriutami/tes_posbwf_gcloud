<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;

class TransaksiPenjualanDetail extends Model
{
    /* 
		Model 	: Untuk Transaksi Penjualan Detail
		Author 	: Sri Utami
		Date 	: 7/11/2020
	*/
    protected $table = 'tb_detail_nota_penjualan';
    public $primaryKey = 'id';
    protected $fillable = ['id_nota',
    						'id_obat',
    						'harga_jual',
    						'jumlah',
    						'diskon',
                            'jumlah_cn'
    						];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'id_nota' => 'required',
            'id_obat' => 'required',
            'harga_jual' => 'required',
            'jumlah' => 'required',
            'diskon' => 'required',
            'jumlah_cn' => 'required',
        ]);
    }

    public function save_plus(){
        $this->created_by = Auth::user()->id;
        $this->save();
    }

    public function save_edit(){
        $this->updated_by = Auth::user()->id;
        $this->save();
    }

    public function obat(){
        return $this->hasOne('App\MasterObat', 'id', 'id_obat');
    }

    public function retur(){
        return $this->hasOne('App\ReturPenjualan', 'id', 'id_retur_penjualan');
    }

    public function nota(){
        return $this->hasOne('App\TransaksiPenjualan', 'id', 'id_nota');
    }

    public function created_oleh(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function cn_oleh(){
        return $this->hasOne('App\User', 'id', 'cn_by');
    }

}

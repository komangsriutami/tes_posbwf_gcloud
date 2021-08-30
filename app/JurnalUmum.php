<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
class JurnalUmum extends Model
{
    protected $table = 'tb_jurnal_umum';
    public $primaryKey = 'id';
    protected $fillable = ['tgl_transaksi', 'no_transaksi', 'id_kode_akun', 'id_sub_kode_akun', 'jumlah', 'keterangan'];

    public function validate_kode_akun(){
    	return Validator::make((array)$this->attributes, [
    		'tgl_transaksi' => 'required',
            'no_transaksi' => 'required|max:255',
            'id_kode_akun' => 'required',
            'jumlah' => 'required'
        ]);
    }

    public function validate_kode_sub_akun(){
    	return Validator::make((array)$this->attributes, [
    		'tgl_transaksi' => 'required',
            'no_transaksi' => 'required|max:255',
            'id_kode_akun' => 'required',
            'id_sub_kode_akun' => 'required',
            'jumlah' => 'required'
        ]);
    }

    public function save_plus(){
        $this->created_by = Auth::user()->id;
        $this->created_at = date('Y-m-d H:i:s');
        $this->save();
    }

    public function save_edit(){
        $this->updated_by = Auth::user()->id;
        $this->updated_at = date('Y-m-d H:i:s');
        $this->save();
    }


    public function kode_akun(){
        return $this->hasOne('App\MasterKodeAkun', 'id', 'id_kode_akun');
    }

    public function kode_sub_akun(){
        return $this->hasOne('App\MasterKodeAkunSub', 'id', 'id_sub_kode_akun');
    }
}

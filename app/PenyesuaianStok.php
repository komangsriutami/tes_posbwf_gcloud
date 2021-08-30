<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;

class PenyesuaianStok extends Model
{
    protected $table = 'tb_penyesuaian_stok_obat';
    public $primaryKey = 'id';
    protected $fillable = ['id_obat',
                            'id_jenis_penyesuaian',
                            'stok_awal',
                            'stok_akhir'
    						];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'id_obat' => 'required',
            'id_jenis_penyesuaian' => 'required',
            'stok_awal' => 'required',
            'stok_akhir' => 'required',
        ]);
    }

    public function created_oleh(){
        return $this->hasOne('App\Users', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\Users', 'id', 'updated_by');
    }
}

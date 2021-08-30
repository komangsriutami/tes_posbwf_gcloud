<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
class SkemaGaji extends Model
{
    protected $table = 'tb_skema_gaji';
    public $primaryKey = 'id';
    public  $timestamps = false;
    protected $fillable = ['id_group_apotek', 'id_jabatan', 'id_posisi', 'id_status_karwayan', 'gaji_pokok', 'tunjangan_profesi', 'tunjangan_jabatan', 'tunjangan_ijin', 'tunjangan_makan_transportasi', 'lembur', 'pph', 'potongan_keterlambatan'];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
    		'id_group_apotek' => 'required',
            'id_jabatan' => 'required|max:255',
            'id_posisi' => 'required',
            'id_status_karwayan' => 'required',
            'gaji_pokok' => 'required',
        ]);
    }
}

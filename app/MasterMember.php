<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;

class MasterMember extends Model
{
    /* 
		Model 	: Untuk Master Member 
		Author 	: Sri U.
		Date 	: 27/02/2020
	*/
		
	protected $table = 'tb_m_member';
    public $primaryKey = 'id';
    protected $fillable = ['username', 
                            'password',
                            'nama', 
                            'tempat_lahir', 
                            'tgl_lahir', 
                            'id_jenis_kelamin', 
                            'alamat',
                            'id_kewarganegaraan',
                            'id_agama',
                            'id_gol_darah',
                            'id_tipe_member',
                            'telepon',
                            'email',
                            'activated',
                            'id_group_apotek'
    						];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'nama' => 'required',
            'password' => 'required',
            'username' => 'required',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required',
            'id_jenis_kelamin' => 'required',
            'alamat' => 'required',
            'id_kewarganegaraan' => 'required',
            'id_agama' => 'required',
            'id_gol_darah' => 'required',
            'id_tipe_member' => 'required',
            'telepon' => 'required',
            'email' => 'required',
            'id_group_apotek' => 'required'
        ]);
    }

    public function save_plus(){
        $this->created_by = Auth::user()->id;
        $this->tgl_lahir = date('Y-m-d', strtotime($this->tgl_lahir));
        $this->save();
    }

    public function save_edit(){
        $this->updated_by = Auth::user()->id;
        $this->tgl_lahir = date('Y-m-d', strtotime($this->tgl_lahir));
        $this->save();
    }

    public function jenis_kelamin(){
        return $this->hasOne('App\MasterJenisKelamin', 'id', 'id_jenis_kelamin');
    }

    public function kewarganegaraan(){
        return $this->hasOne('App\MasterKewarganegaraan', 'id', 'id_kewarganegaraan');
    }

    public function agama(){
        return $this->hasOne('App\MasterAgama', 'id', 'id_agama');
    }

    public function golongan_darah(){
        return $this->hasOne('App\MasterGolonganDarah', 'id', 'id_gol_darah');
    }

    public function group_apotek(){
        return $this->hasOne('App\MasterGroupApotek', 'id', 'id_group_apotek');
    }

    public function created_oleh(){
        return $this->hasOne('App\Users', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\Users', 'id', 'updated_by');
    }
}

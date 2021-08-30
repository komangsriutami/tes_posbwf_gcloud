<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
class MasterDokter extends Model
{
    /* 
		Model 	: Untuk Master Apotek 
		Author 	: Surya Adiputra
		Date 	: 3/04/2020
	*/

	protected $table = 'tb_m_dokter';
    public $primaryKey = 'id';
    protected $fillable = ['id_group_apotek',
                            'nama',
                            'sib',
    						'alamat',
    						'telepon',
                            'fee'
    						];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'id_group_apotek' => 'required',
            'nama' => 'required',
            'sib' => 'required|max:255',
            'alamat' => 'required',
            'telepon' => 'required|max:25',
            'fee' => 'required',
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

    public function created_oleh(){
        return $this->hasOne('App\Users', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\Users', 'id', 'updated_by');
    }
}

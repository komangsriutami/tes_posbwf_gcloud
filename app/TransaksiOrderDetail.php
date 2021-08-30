<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
class TransaksiOrderDetail extends Model
{
     // ini tabel detail order
    protected $table = 'tb_detail_nota_order';
    public $primaryKey = 'id';
    protected $fillable = ['id_nota', 'id_obat', 'jumlah', 'status', 'id_defecta', 'is_titip_order'];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
    		'id_obat' => 'required',
            'id_defecta' => 'required',
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

    public function nota(){
        return $this->hasOne('App\TransaksiOrder', 'id', 'id_nota');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;
class TransaksiOrder extends Model
{
     // ini tabel order
    protected $table = 'tb_nota_order';
    public $primaryKey = 'id';
    protected $fillable = ['tgl_nota', 'id_suplier', 'id_apotek', 'is_status'];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
    		'id_suplier' => 'required',
            'id_apotek' => 'required',
        ]);
    }

    public function save_from_array($detail_orders, $val){
        if($val==1) {
            $this->tgl_nota = date('Y-m-d');
            $this->created_at = date('Y-m-d H:i:s');
            $this->created_by = Auth::user()->id;
            $id_nota = $this->save();
        }else{
            $this->updated_at = date('Y-m-d H:i:s');
            $this->updated_by = Auth::user()->id;
            $id_nota = $this->save();
        }

        $status = true;
        $array_id_obat = array();
        foreach ($detail_orders as $detail_order) {
            if($detail_order['id']>0){
                $obj = TransaksiOrderDetail::find($detail_order['id']);
            }else{
                $obj = new TransaksiOrderDetail;
            }

            $is_titip_order = 0;
            if($this->id_apotek != $detail_order['id_apotek']) {
                $is_titip_order = 1;
            }
            $obj->id_nota = $this->id;
            $obj->id_obat = $detail_order['id_obat'];
            $obj->jumlah = $detail_order['jumlah'];
            $obj->is_titip_order = $is_titip_order;
            $obj->is_purchasing_add = $detail_order['is_purchasing_add'];
            $obj->keterangan = $detail_order['keterangan'];
            $obj->created_by = Auth::user()->id;
            $obj->created_at = date('Y-m-d H:i:s');
            $obj->updated_at = date('Y-m-d H:i:s');
            $obj->updated_by = '';
            $obj->is_deleted = 0;

            if($detail_order['id_defecta'] != '') {
                $obj->id_defecta = $detail_order['id_defecta'];
                $defecta = DefectaOutlet::find($detail_order['id_defecta']);
                //setelah itu, update tabel temp order
                $defecta->id_process = 1;
                $defecta->save();
            }
            
            $obj->save();
            $array_id_obat[] = $obj->id;            
        }

        if(!empty($array_id_obat)){
            DB::statement("DELETE FROM tb_detail_nota_order
                            WHERE id_nota=".$this->id." AND 
                                    id NOT IN(".implode(',', $array_id_obat).")");
        }else{
            DB::statement("DELETE FROM tb_detail_nota_order 
                            WHERE id_nota=".$this->id);
        }
    }

    public function save_plus(){
        $this->created_by = Auth::user()->id;
        $this->save();
    }

    public function save_edit(){
        $this->updated_by = Auth::user()->id;
        $this->save();
    }

    public function detail_order(){
        return $this->hasMany('App\TransaksiOrderDetail', 'id_nota', 'id');
    }
}

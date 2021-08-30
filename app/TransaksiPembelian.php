<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;
use App\Events\PembelianCreate;

class TransaksiPembelian extends Model
{
    protected $table = 'tb_nota_pembelian';
    public $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['id_apotek_nota',
                            'id_jenis_pembayaran',
                            'tgl_nota',
    						'no_faktur',
                            'tgl_faktur',
    						'tgl_jatuh_tempo',
    						'id_suplier',
    						'id_apotek',
    						'diskon1',
    						'diskon2',
    						'ppn',
    						'id_jenis_pembelian',
                            'is_tanda_terima'
    						];
    						
    public function validate(){
        return Validator::make((array)$this->attributes, [
            'no_faktur' => 'required',
            //'tgl_faktur' => 'required',
           // 'tgl_jatuh_tempo' => 'required',
            'id_suplier' => 'required',
            //'id_apotek' => 'required',
            'diskon1' => 'required',
            'diskon2' => 'required',
            'ppn' => 'required',
            //'id_jenis_pembelian' => 'required',
        ]);
    }

    public function save_from_array($detail_pembelians, $val){
        if($val==1)
        {
            $this->tgl_nota = date('Y-m-d H:i:s');
            $this->id_apotek_nota = session('id_apotek_active');
            $this->created_by = Auth::user()->id;
            $this->created_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }else{
            $this->tgl_nota = date('Y-m-d H:i:s');
            $this->id_apotek_nota = session('id_apotek_active');
            $this->updated_by = Auth::user()->id;
            $this->updated_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }

        $status = true;
        $str_array_id = array();
        $array_id_obat = array();

        foreach ($detail_pembelians as $detail_pembelian) {
            if(!in_array($detail_pembelian['id_obat'], $array_id_obat)){
                if($detail_pembelian['id']>0){
                    $obj = TransaksiPembelianDetail::find($detail_pembelian['id']);
                }else{
                    $obj = new TransaksiPembelianDetail;
                }
                
                $obj->id_nota = $this->id;
                $obj->id_obat = $detail_pembelian['id_obat'];
                $obj->total_harga = $detail_pembelian['total_harga'];
                $obj->harga_beli = $detail_pembelian['harga_beli'];
                $obj->harga_beli_ppn = $detail_pembelian['harga_beli']+($this->ppn/100*$detail_pembelian['harga_beli']);
                $obj->jumlah = $detail_pembelian['jumlah'];
                $obj->diskon = $detail_pembelian['diskon'];
                $obj->diskon_persen = $detail_pembelian['diskon_persen'];
                $obj->id_batch = $detail_pembelian['id_batch'];
                $obj->tgl_batch = $detail_pembelian['tgl_batch'];
                $obj->created_by = Auth::user()->id;
                $obj->created_at = date('Y-m-d H:i:s');
                $obj->updated_at = date('Y-m-d H:i:s');
                $obj->updated_by = '';
                $obj->is_deleted = 0;

                $obj->save();
                $array_id_obat[] = $obj->id;

                # crete histori stok barang
               // tb_histori_stok_lv
                # update stok ke 
                PembelianCreate::dispatch($obj);
                //DB::table('tb_m_stok_harga_'.$inisial)->where('id', $request->id_stok_harga)->update(['is_defecta'=> 1]);
            }
        }

        if(!empty($array_id_obat)){
            DB::statement("DELETE FROM tb_detail_nota_pembelian
                            WHERE id_nota=".$this->id." AND 
                                    id NOT IN(".implode(',', $array_id_obat).")");
        }else{
            DB::statement("DELETE FROM tb_detail_nota_pembelian 
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

    public function detail_pembalian(){
        return $this->hasMany('App\TransaksiPembelianDetail', 'id_nota', 'id')->where('is_deleted', 0);
    }

    public function detail_pembelian_total(){
        return $this->hasMany('App\TransaksiPembelianDetail', 'id_nota', 'id')
                    ->select([
                        DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                        DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                        DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah')
                    ])
                    ->where('tb_detail_nota_pembelian.is_deleted', 0)->limit(1);
    }

    public function created_oleh(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function apotek(){
        return $this->hasOne('App\MasterApotek', 'id', 'id_apotek');
    }

    public function suplier(){
        return $this->hasOne('App\MasterSuplier', 'id', 'id_suplier');
    }

    public function jenis_pembelian(){
        return $this->hasOne('App\MasterJenisPembelian', 'id', 'id_jenis_pembelian');
    }
}

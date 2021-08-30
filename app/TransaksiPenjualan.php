<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;
use App\Events\PenjualanCreate;

class TransaksiPenjualan extends Model
{
    /* 
        Model   : Untuk Transaksi Penjualan
        Author  : Sri Utami
        Date    : 7/11/2020
    */
    protected $table = 'tb_nota_penjualan';
    public $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['id_apotek_nota', 'id_pasien', 'tgl_nota', 'keterangan', 'diskon_persen', 'diskon_rp', 'id_karyawan', 'cash', 'kembalian', 'id_kartu_debet_credit', 'debet', 'no_kartu', 'surcharge', 'id_dokter', 'biaya_jasa_dokter', 'id_jasa_resep', 'is_penjualan_tanpa_item', 'is_kredit', 'is_lunas_pembayaran_kredit', 'id_vendor', 'diskon_vendor', 'biaya_resep', 'total_belanja', 'total_bayar', 'id_paket_wd', 'harga_wd', 'nama_lab', 'biaya_lab', 'keterangan_lab', 'biaya_apd'];

    public function validate(){
        return Validator::make((array)$this->attributes, [
            'cash' => 'required',
            'kembalian' => 'required'
        ]);
    }

    public function save_from_array($detail_penjualans, $val){
        if($val==1)
        {
            $this->id_apotek_nota = session('id_apotek_active');
            $this->created_by = Auth::user()->id;
            $this->tgl_nota = date('Y-m-d');
            $this->created_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }else{
            $this->id_apotek_nota = session('id_apotek_active');
            $this->updated_by = Auth::user()->id;
            $this->tgl_nota = date('Y-m-d');
            $this->updated_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }

        $status = true;
        $str_array_id = array();
        $array_id_obat = array();

        if ($this->is_penjualan_tanpa_item == 0) {
            foreach ($detail_penjualans as $detail_penjualan) {
                if(!in_array($detail_penjualan['id_obat'], $array_id_obat)){
                    if($detail_penjualan['id']>0){
                        $obj = TransaksiPenjualanDetail::find($detail_penjualan['id']);
                    }else{
                        $obj = new TransaksiPenjualanDetail;
                    }

                    $obj->id_nota = $this->id;
                    $obj->id_obat = $detail_penjualan['id_obat'];
                    $obj->harga_jual = $detail_penjualan['harga_jual'];
                    $obj->jumlah = $detail_penjualan['jumlah'];
                    $obj->diskon = $detail_penjualan['diskon'];
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
                    PenjualanCreate::dispatch($obj);
                    //DB::table('tb_m_stok_harga_'.$inisial)->where('id', $request->id_stok_harga)->update(['is_defecta'=> 1]);
                }
            }

            if(!empty($array_id_obat)){
                DB::statement("DELETE FROM tb_detail_nota_penjualan
                                WHERE id_nota=".$this->id." AND 
                                        id NOT IN(".implode(',', $array_id_obat).")");
            }else{
                DB::statement("DELETE FROM tb_detail_nota_penjualan 
                                WHERE id_nota=".$this->id);
            }
        }
        
    }

    public function detail_penjualan(){
        return $this->hasMany('App\TransaksiPenjualanDetail', 'id_nota', 'id')->where('is_deleted', 0);
    }

    public function detail_penjualan_total(){
        return $this->hasMany('App\TransaksiPenjualanDetail', 'id_nota', 'id')
                    ->select([
                        DB::raw('SUM(tb_detail_nota_penjualan.jumlah * tb_detail_nota_penjualan.harga_jual) AS total'),
                        DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon')
                    ])
                    ->where('tb_detail_nota_penjualan.is_deleted', 0)->limit(1);
    }

    public function cek_retur(){
        return $this->hasMany('App\TransaksiPenjualanDetail', 'id_nota', 'id')
                    ->select([
                        DB::raw('COUNT(tb_detail_nota_penjualan.id) AS total_cn')
                    ])
                    ->where('tb_detail_nota_penjualan.is_cn', 1)
                    ->limit(1);
    }

    public function created_oleh(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function dokter(){
        return $this->hasOne('App\MasterDokter', 'id', 'id_dokter');
    }

    public function jasa_resep(){
        return $this->hasOne('App\MasterJasaResep', 'id', 'id_jasa_resep');
    }

    public function karyawan(){
        return $this->hasOne('App\User', 'id', 'id_karyawan');
    }

    public function vendor(){
        return $this->hasOne('App\MasterVendor', 'id', 'id_vendor');
    }

    public function pasien(){
        return $this->hasOne('App\MasterMember', 'id', 'id_pasien');
    }

    public function paket_wd(){
        return $this->hasOne('App\MasterPaketWD', 'id', 'id_paket_wd');
    }

    public function lunas_oleh(){
        return $this->hasOne('App\User', 'id', 'is_lunas_pembayaran_kredit_by');
    }

    public function kartu(){
        return $this->hasOne('App\MasterKartu', 'id', 'id_kartu_debet_credit');
    }
}
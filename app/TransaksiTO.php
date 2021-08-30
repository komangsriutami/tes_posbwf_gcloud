<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use DB;
class TransaksiTO extends Model
{
    // ini tabel nota penjualan
    protected $table = 'tb_nota_transfer_outlet';
    public $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['id_apotek_nota',
                            'tgl_nota',
    						'id_apotek_asal',
    						'id_apotek_tujuan',
    						'keterangan',
                            'is_deleted',
                            'deleted_at',
                            'deleted_by'
    						];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'id_apotek_tujuan' => 'required'
        ]);
    }

    public function save_from_array($detail_transfer_outlets, $val){
        if($val==1)
        {
            $this->tgl_nota = date('Y-m-d H:i:s');
            $this->id_apotek_nota = session('id_apotek_active');
            $this->id_apotek_asal = session('id_apotek_active');
            $this->created_by = Auth::user()->id;
            $this->created_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }else{
            $this->tgl_nota = date('Y-m-d H:i:s');
            $this->id_apotek_nota = session('id_apotek_active');
            $this->id_apotek_asal = session('id_apotek_active');
            $this->updated_by = Auth::user()->id;
            $this->updated_at = date('Y-m-d H:i:s');
            $id_nota = $this->save();
        }

        $status = true;
        $str_array_id = array();
        $array_id_obat = array();
        $total_nota = 0;
        $apotek = MasterApotek::find(session('id_apotek_active'));
        $inisial = strtolower($apotek->nama_singkat);

        $apotek2 = MasterApotek::find($this->id_apotek_tujuan);
        $inisial2 = strtolower($apotek2->nama_singkat);
        foreach ($detail_transfer_outlets as $detail_transfer_outlet) {
            if(!in_array($detail_transfer_outlet['id_obat'], $array_id_obat)){
                if($detail_transfer_outlet['id']>0){
                    $obj = TransaksiTODetail::find($detail_transfer_outlet['id']);
                }else{
                    $obj = new TransaksiTODetail;
                }

                $obj->id_nota = $this->id;
                $obj->id_obat = $detail_transfer_outlet['id_obat'];
                $obj->harga_outlet = $detail_transfer_outlet['harga_outlet'];
                $obj->jumlah = $detail_transfer_outlet['jumlah'];
                $obj->total = $detail_transfer_outlet['harga_outlet'] * $detail_transfer_outlet['jumlah'];
                $obj->created_by = Auth::user()->id;
                $obj->created_at = date('Y-m-d H:i:s');
                $obj->updated_at = date('Y-m-d H:i:s');
                $obj->updated_by = '';
                $obj->is_deleted = 0;

                $obj->save();
                $array_id_obat[] = $obj->id;
                $total_nota = $total_nota+$obj->total;


                $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->first();
                $stok_now = $stok_before->stok_akhir-$obj->jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before->stok_akhir, 'stok_akhir'=> $stok_now, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial)->insert([
                    'id_obat' => $obj->id_obat,
                    'jumlah' => $obj->jumlah,
                    'stok_awal' => $stok_before->stok_akhir,
                    'stok_akhir' => $stok_now,
                    'id_jenis_transaksi' => 4, //transfer keluar
                    'id_transaksi' => $obj->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);

                // turn off -> because add konfirmasi transfer barang
                /*$stok_before2 = DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->first();
                $stok_now2 = $stok_before2->stok_akhir+$obj->jumlah;

                # update ke table stok harga
                DB::table('tb_m_stok_harga_'.$inisial2)->where('id_obat', $obj->id_obat)->update(['stok_awal'=> $stok_before2->stok_akhir, 'stok_akhir'=> $stok_now2, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

                # create histori
                DB::table('tb_histori_stok_'.$inisial2)->insert([
                    'id_obat' => $obj->id_obat,
                    'jumlah' => $obj->jumlah,
                    'stok_awal' => $stok_before2->stok_akhir,
                    'stok_akhir' => $stok_now2,
                    'id_jenis_transaksi' => 3, //transfer keluar
                    'id_transaksi' => $obj->id,
                    'batch' => null,
                    'ed' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id
                ]);*/
            }
        }

        $this->total = $total_nota;
        $this->save();

        if(!empty($array_id_obat)){
            DB::statement("DELETE FROM tb_detail_nota_transfer_outlet
                            WHERE id_nota=".$this->id." AND 
                                    id NOT IN(".implode(',', $array_id_obat).")");
        }else{
            DB::statement("DELETE FROM tb_detail_nota_transfer_outlet 
                            WHERE id_nota=".$this->id);
        }
    }

    public function detail_transfer_outlet(){
        return $this->hasMany('App\TransaksiTODetail', 'id_nota', 'id')->where('tb_detail_nota_transfer_outlet.is_deleted', 0);
    }

    public function detail_transfer_total(){
        return $this->hasMany('App\TransaksiTODetail', 'id_nota', 'id')
                    ->select([
                        DB::raw('SUM(tb_detail_nota_transfer_outlet.jumlah * tb_detail_nota_transfer_outlet.harga_outlet) AS total')
                    ])
                    ->where('tb_detail_nota_transfer_outlet.is_deleted', 0)->limit(1);
    }

    public function apotek_asal(){
        return $this->hasOne('App\MasterApotek', 'id', 'id_apotek_asal');
    }

    public function apotek_tujuan(){
        return $this->hasOne('App\MasterApotek', 'id', 'id_apotek_tujuan');
    }

    public function created_oleh(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updated_oleh(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
}

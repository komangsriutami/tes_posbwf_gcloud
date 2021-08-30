<?php

namespace App\DataTables;

use App\SO\StokHargaTL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTablesEditor;
use Auth;
use DB;
use App\MasterObat;
class StokTLDataTableEditor extends DataTablesEditor
{
    protected $model = StokHargaTL::class;

    /**
     * Get create action validation rules.
     *
     * @return array
     */
    public function createRules()
    {
        return [
            'id_obat'  => 'required',
            'stok_akhir_so'  => 'required',
        ];
    }

    /**
     * Get edit action validation rules.
     *
     * @param Model $model
     * @return array
     */
    public function editRules(Model $model)
    {
        return [
            //'email' => 'sometimes|required|email|' . Rule::unique($model->getTable())->ignore($model->getKey()),
            'id_obat'  => 'sometimes|required',
            'stok_akhir_so'  => 'sometimes|required',
        ];
    }

    /**
     * Get remove action validation rules.
     *
     * @param Model $model
     * @return array
     */
    public function removeRules(Model $model)
    {
        return [];
    }

    /**
    * Pre-create action event hook.
    *
    * @param Model $model
    * @return array
    */
    public function creating(Model $model, array $data)
    {
        return $data;
    }

    /**
    * Pre-update action event hook.
    *
    * @param Model $model
    * @return array
    */
    public function updating(Model $model, array $data)
    {
        $inisial = 'tl';
        $id_so = session('id_so');
        if(isset($data['stok_akhir_so'])) {
            #echo "update stok";
            $selisih = $model->stok_awal_so-$data['stok_akhir_so'];
            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->first();
            DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->update(['stok_akhir_so'=> $data['stok_akhir_so'], 'selisih' => $selisih, 'id_so' => $id_so, 'is_so' => 1, 'so_at' => date('Y-m-d H:i:s'), 'so_by' => Auth::user()->id, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            /*DB::table('tb_histori_stok_'.$inisial)->insert([
                'id_obat' => $model->id_obat,
                'jumlah' => $data['stok_akhir'],
                'stok_awal' => $stok_before->stok_akhir,
                'stok_akhir' => $data['stok_akhir'],
                'id_jenis_transaksi' => 11, //stok opnam
                'id_transaksi' => $id_so,
                'batch' => null,
                'ed' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);   */
        } else if(isset($data['nama'])) {
            # echo "update nama";
            $obat = MasterObat::find($model->id_obat);
            $obat->nama = $data['nama'];
            $obat->updated_at = date('Y-m-d H:i:s');
            $obat->updated_by = Auth::user()->id;
            $obat->save();
        } else if(isset($data['barcode'])) {
            # echo "update barcode";
            $obat = MasterObat::find($model->id_obat);
            $obat->barcode = $data['barcode'];
            $obat->updated_at = date('Y-m-d H:i:s');
            $obat->updated_by = Auth::user()->id;
            $obat->save();
        } else if(isset($data['harga_jual'])) {
            #echo "update harga jual";
            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->first();
            DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->update(['harga_jual'=> $data['harga_jual'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_harga_'.$inisial)->insert([
                'id_obat' => $model->id_obat,
                'harga_beli_awal' => $stok_before->harga_beli,
                'harga_beli_akhir' => $stok_before->harga_beli,
                'harga_jual_awal' => $stok_before->harga_jual,
                'harga_jual_akhir' => $data['harga_jual'], //stok opnam
                'is_asal' => 2,
                'id_asal' => $id_so,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);   
        } else if(isset($data['harga_beli'])) {
            # echo "update harga beli";
            $stok_before = DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->first();
            DB::table('tb_m_stok_harga_'.$inisial)->where('id', $model->id)->update(['harga_beli'=> $data['harga_beli'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id]);

            # create histori
            DB::table('tb_histori_harga_'.$inisial)->insert([
                'id_obat' => $model->id_obat,
                'harga_beli_awal' => $stok_before->harga_beli,
                'harga_beli_akhir' => $data['harga_beli'],
                'harga_jual_awal' => $stok_before->harga_jual,
                'harga_jual_akhir' => $stok_before->harga_jual, //stok opnam
                'is_asal' => 2,
                'id_asal' => $id_so,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id
            ]);  
        }
        
        return $data;
    }
}

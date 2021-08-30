<?php

namespace App\DataTables;

use App\SO\StokHargaSG;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\MasterApotek;
use App\SettingStokOpnam;
use DB;
class StokSGDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables($query)->setRowId('id')
                ->addColumn('nama', function($query){
                    return $query->nama; 
                })
                ->addColumn('barcode', function($query){
                    return $query->barcode; 
                });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\StokHargaSG $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(StokHargaSG $model)
    {
        $so_status_aktif = session('so_status_aktif');
        return $model->newQuery()
                    ->select(DB::raw('IFNULL(users.username, null) as so_by'), 'tb_m_stok_harga_sg.id', 'tb_m_stok_harga_sg.id_obat', 'tb_m_stok_harga_sg.stok_awal', 'tb_m_stok_harga_sg.stok_akhir_so', 'tb_m_stok_harga_sg.harga_beli', 'tb_m_stok_harga_sg.harga_jual', 'tb_m_obat.nama', 'tb_m_obat.barcode', 'tb_m_stok_harga_sg.is_so', 'tb_m_stok_harga_sg.stok_awal_so', 'tb_m_stok_harga_sg.selisih')
                    ->leftJoin( 'tb_m_obat', 'tb_m_obat.id', '=', 'tb_m_stok_harga_sg.id_obat' )
                    ->leftJoin( 'users', 'users.id', '=', 'tb_m_stok_harga_sg.so_by' )
                    ->where(function($query) use($so_status_aktif){
                        if($so_status_aktif == 2) {
                            $query->where('tb_m_stok_harga_sg.selisih','!=','0');
                        }
                    })
                    ->orderBy('tb_m_stok_harga_sg.id', 'asc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('tb_m_stok_harga_sg-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('Bfrtip')
                    ->orderBy(1, 'asc')
                    ->buttons(
                        Button::make('create'),
                        Button::make('export'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [

            Column::make('id'),
            [
                'data' => 'barcode',
                'editField' => 'barcode',
                'name' => 'tb_m_obat.barcode',
                'title' => 'Barcode',
                'orderable' => true,
                'searchable' => true,
                //'className' => 'editable'
            ],
            [
                'data' => 'nama',
                'editField' => 'nama',
                'name' => 'tb_m_obat.nama',
                'title' => 'Barcode',
                'orderable' => true,
                'searchable' => true,
                //'className' => 'editable'
            ],
            [
                'data' => 'harga_beli',
                'editField' => 'harga_beli',
                'name' => 'tb_m_stok_harga_sg.harga_beli',
                'title' => 'Harga Beli',
                'orderable' => true,
                //'searchable' => true,
                //'className' => 'editable'
            ],
            [
                'data' => 'harga_jual',
                'editField' => 'harga_jual',
                'name' => 'tb_m_stok_harga_sg.harga_jual',
                'title' => 'Harga Jual',
                'orderable' => true,
               // 'searchable' => true,
                //'className' => 'editable'
            ],
            //Column::make('username'),
            [
                'data' => 'so_by',
                'title' => 'Oleh',
                'orderable' => true,
                //'searchable' => true
            ],
            [
                'data' => 'selisih',
                'title' => 'Selisih',
                'orderable' => true,
                //'searchable' => true
            ],
            [
                'data' => 'stok_awal_so',
                'title' => 'Stok Awal',
                'orderable' => true,
                //'searchable' => true
            ],
            [
                'data' => 'stok_akhir_so',
                'editField' => 'stok_akhir_so',
                'name' => 'stok_akhir_so',
                'title' => 'Stok Akhir',
                'orderable' => true,
                //'searchable' => true,
                'className' => 'editable'
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'tb_m_stok_harga_sg_' . time();
    }
}

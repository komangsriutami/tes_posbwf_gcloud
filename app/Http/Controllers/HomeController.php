<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\RbacUserRole;
use App\RbacRolePermission;
use App\RbacPermission;
use App\RbacMenu;
use App\MasterApotek;
use App\MasterTahun;
use App\TransaksiPenjualanClosing;
use App\TransaksiPembelian;
use App\TransaksiPembelianDetail;
use App\TransaksiTO;
use App\TransaksiTODetail;
use App\User;
use App\MasterVendor;
use App\MasterSuplier;
use App;
use Datatables;
use DB;
use Auth;
use Mail;

use App\TransaksiPenjualan;
use App\TransaksiPenjualanDetail;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $id_apotek = session('id_apotek_active');
        if(!empty($id_apotek)) {
            $apotek = MasterApotek::find(session('id_apotek_active'));
            $apoteker = User::find($apotek->id_apoteker);
            $id_user = Auth::user()->id;

            $hak_akses = 0;
            if($apoteker->id == $id_user) {
                $hak_akses = 1;
            }

            if($id_user == 1 || $id_user == 2 || $id_user == 16) {
                $hak_akses = 1;
            }

            $tanggal = date('Y-m-d');
            $tgl_awal_baru = $tanggal.' 00:00:00';
            $tgl_akhir_baru = $tanggal.' 23:59:59';
            $detail_penjualan_kredit = array();
            $penjualan_kredit = array();
            $detail_penjualan = array();
            $penjualan2 = array();
            $detail_penjualan_kredit_terbayar = array();
            $penjualan_kredit_terbayar = array();
            $detail_tf_masuk = array();
            $detail_tf_keluar = array();
            $detail_pembelian = array();
            $detail_pembelian_terbayar = array();
            $detail_pembelian_blm_terbayar = array();
            $detail_pembelian_jatuh_tempo = array();
            if($hak_akses == 1) {
                $detail_penjualan_kredit = DB::table('tb_detail_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                    DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                    DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                            ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal_baru)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_kredit', 1)
                            ->where('tb_detail_nota_penjualan.is_cn', 0)
                            ->first();

                $penjualan_kredit =  DB::table('tb_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                    DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                    DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'),
                                    DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'))
                            ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                            ->whereDate('tb_nota_penjualan.tgl_nota','>=', $tgl_awal_baru)
                            ->whereDate('tb_nota_penjualan.tgl_nota','<=', $tgl_akhir_baru)
                            ->where('tb_nota_penjualan.id_apotek_nota','=',$apotek->id)
                            ->where('tb_nota_penjualan.is_deleted', 0)
                            ->where('tb_nota_penjualan.is_kredit', 1)
                            ->first();

                $detail_penjualan = DB::table('tb_detail_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                    DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                    DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                            ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                            ->whereDate('b.created_at','>=', $tgl_awal_baru)
                            ->whereDate('b.created_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_kredit', 0)
                            ->first();

                $penjualan2 =  DB::table('tb_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                    DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                    DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'),
                                    DB::raw('SUM(tb_nota_penjualan.harga_wd) AS total_paket_wd'),
                                    DB::raw('SUM(tb_nota_penjualan.biaya_lab) AS total_lab'),
                                    DB::raw('SUM(tb_nota_penjualan.biaya_apd) AS total_apd'),
                                    DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'))
                            ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                            ->whereDate('tb_nota_penjualan.created_at','>=', $tgl_awal_baru)
                            ->whereDate('tb_nota_penjualan.created_at','<=', $tgl_akhir_baru)
                            ->where('tb_nota_penjualan.id_apotek_nota','=',$apotek->id)
                            ->where('tb_nota_penjualan.is_deleted', 0)
                            ->where('tb_nota_penjualan.is_kredit', 0)
                            ->first();

                $detail_penjualan_kredit_terbayar = DB::table('tb_detail_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                    DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                    DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'),
                                    DB::raw('SUM(b.diskon_vendor/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_vendor')
                                )
                            ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                            ->whereDate('b.is_lunas_pembayaran_kredit_at','>=', $tgl_awal_baru)
                            ->whereDate('b.is_lunas_pembayaran_kredit_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_kredit', 1)
                            ->where('b.is_lunas_pembayaran_kredit', 1)
                            ->where('tb_detail_nota_penjualan.is_cn', 0)
                            ->first();
            
                $penjualan_kredit_terbayar =  DB::table('tb_nota_penjualan')
                            ->select(
                                    DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                    DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                    DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'),
                                    DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'))
                            ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                            ->whereDate('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','>=', $tgl_awal_baru)
                            ->whereDate('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','<=', $tgl_akhir_baru)
                            ->where('tb_nota_penjualan.id_apotek_nota','=',$apotek->id)
                            ->where('tb_nota_penjualan.is_deleted', 0)
                            ->where('tb_nota_penjualan.is_kredit', 1)
                            ->where('tb_nota_penjualan.is_lunas_pembayaran_kredit', 1)
                            //->groupBy('tb_nota_penjualan.id')
                            ->first();

                $detail_tf_masuk = DB::table('tb_detail_nota_transfer_outlet')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_transfer_outlet.harga_outlet * tb_detail_nota_transfer_outlet.jumlah) AS total'))
                            ->join('tb_nota_transfer_outlet as b','b.id','=','tb_detail_nota_transfer_outlet.id_nota')
                            ->whereDate('b.created_at','>=', $tgl_awal_baru)
                            ->whereDate('b.created_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_tujuan','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_tf_keluar = DB::table('tb_detail_nota_transfer_outlet')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_transfer_outlet.harga_outlet * tb_detail_nota_transfer_outlet.jumlah) AS total'))
                            ->join('tb_nota_transfer_outlet as b','b.id','=','tb_detail_nota_transfer_outlet.id_nota')
                            ->whereDate('b.created_at','>=', $tgl_awal_baru)
                            ->whereDate('b.created_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->first();


                $detail_pembelian = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah'),
                                    DB::raw('SUM((b.ppn/100)*(tb_detail_nota_pembelian.total_harga-(b.diskon1+b.diskon2))) AS total'))
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.created_at','>=', $tgl_awal_baru)
                            ->whereDate('b.created_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah'),
                                    DB::raw('SUM((b.ppn/100)*(tb_detail_nota_pembelian.total_harga-(b.diskon1+b.diskon2))) AS total'))
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.created_at','>=', $tgl_awal_baru)
                            ->whereDate('b.created_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah'),
                                    DB::raw('SUM((b.ppn/100)*(tb_detail_nota_pembelian.total_harga-(b.diskon1+b.diskon2))) AS total'))
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.lunas_at','>=', $tgl_awal_baru)
                            ->whereDate('b.lunas_at','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();

                $detail_pembelian_blm_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah'),
                                    DB::raw('SUM((b.ppn/100)*(tb_detail_nota_pembelian.total_harga-(b.diskon1+b.diskon2))) AS total'))
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 0)
                            ->first();

                $detail_pembelian_jatuh_tempo = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon_persen * tb_detail_nota_pembelian.total_harga/100) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS jumlah'),
                                    DB::raw('SUM((b.ppn/100)*(tb_detail_nota_pembelian.total_harga-(b.diskon1+b.diskon2))) AS total'))
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_jatuh_tempo','>=', $tgl_awal_baru)
                            ->whereDate('b.tgl_jatuh_tempo','<=', $tgl_akhir_baru)
                            ->where('b.id_apotek_nota','=',$apotek->id)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 0)
                            ->first();
            }

            return view('home')->with(compact('hak_akses', 'detail_penjualan_kredit', 'penjualan_kredit', 'detail_penjualan', 'penjualan2', 'detail_penjualan_kredit_terbayar', 'penjualan_kredit_terbayar', 'detail_tf_masuk', 'detail_tf_keluar', 'detail_pembelian', 'detail_pembelian_terbayar', 'detail_pembelian_blm_terbayar', 'detail_pembelian_jatuh_tempo'));
        } else {
            return view('home2');
        }        
    }

    public function recap_all() {
        $tahun = date('Y');
        $bulan = date('m');

        return view('recap')->with(compact('tahun', 'bulan'));
    }

    public function recap_all_load_view(Request $request) {
        $id_apotek = session('id_apotek_active');
        $data = '';
        $data .= '<table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%" colspan="14" class="text-center text-white" style="background-color:#455a64;">PENJUALAN</th>
                        </tr>
                        <tr>
                            <th width="10%" rowspan="2" class="text-center">KERJASAMA</th>
                            <th width="20%" colspan="3" class="text-center text-white" style="background-color:#00bcd4;">KREDIT</th>
                            <th width="20%" colspan="4" class="text-center text-white" style="background-color:#00acc1;">NON KREDIT</th>
                            <th width="20%" colspan="6" class="text-center text-white" style="background-color:#0097a7;">RINCIAN NON KREDIT</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Total Penjualan</th>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Belum Terbayar</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Total Penjualan</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Cash</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Non Cash</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">TT</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Penjualan</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Jasa Dokter</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Jasa Resep</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Paket WT</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Lab</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">APD</th>
                        </tr>
                    </thead>
                    <tbody>';
        $penjualan = array();

        $detail_penjualan = DB::table('tb_detail_nota_penjualan')
                    ->select(
                            DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                            DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                            DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                            DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                    ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                    ->whereMonth('b.tgl_nota','=',$request->bulan)
                    ->whereYear('b.tgl_nota','=', $request->tahun)
                    ->where('b.id_apotek_nota','=',$id_apotek)
                    ->where('b.is_deleted', 0)
                    ->where('b.is_kredit', 0)
                    ->first();

        $penjualan2 =  DB::table('tb_nota_penjualan')
                    ->select(
                            DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                            DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                            DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'),
                            DB::raw('SUM(tb_nota_penjualan.harga_wd) AS total_paket_wd'),
                            DB::raw('SUM(tb_nota_penjualan.biaya_lab) AS total_lab'),
                            DB::raw('SUM(tb_nota_penjualan.biaya_apd) AS total_apd'),
                            DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'))
                    ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                    ->whereMonth('tgl_nota','=', $request->bulan)
                    ->whereYear('tgl_nota','=', $request->tahun)
                    ->where('id_apotek_nota','=',$id_apotek)
                    ->where('tb_nota_penjualan.is_deleted', 0)
                    ->where('tb_nota_penjualan.is_kredit', 0)
                    ->first();

        $penjualan_closing = TransaksiPenjualanClosing::select([

                                    DB::raw('SUM(total_jasa_dokter) as total_jasa_dokter_a'),
                                    DB::raw('SUM(total_jasa_resep) as total_jasa_resep_a'),
                                    DB::raw('SUM(total_paket_wd) as total_paket_wd_a'),
                                    DB::raw('SUM(total_penjualan) as total_penjualan_a'),
                                    DB::raw('SUM(total_debet) as total_debet_a'),
                                    DB::raw('SUM(total_penjualan_cash) as total_penjualan_cash_a'),
                                    DB::raw('SUM(total_penjualan_cn) as total_penjualan_cn_a'),
                                    DB::raw('SUM(total_penjualan_kredit) as total_penjualan_kredit_a'),
                                    DB::raw('SUM(total_penjualan_kredit_terbayar) as total_penjualan_kredit_terbayar_a'),
                                    DB::raw('SUM(total_diskon) as total_diskon_a'),
                                    DB::raw('SUM(uang_seharusnya) as uang_seharusnya_a'),
                                    DB::raw('SUM(total_akhir) as total_akhir_a'),
                                    DB::raw('SUM(jumlah_tt) as jumlah_tt_a')
                                ])
                                ->whereMonth('tanggal','=', $request->bulan)
                                ->whereYear('tanggal','=', $request->tahun)
                                ->where('id_apotek_nota','=',$id_apotek)
                                ->first();

        $detail_penjualan_cn = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereMonth('tb_detail_nota_penjualan.cn_at','=', $request->bulan)
                        ->whereYear('tb_detail_nota_penjualan.cn_at','=', $request->tahun)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.is_deleted', 0)
                        ->where('tb_detail_nota_penjualan.is_cn', 1)
                        ->where('b.is_kredit', 0)
                        ->first();

        $penjualan_cn_cash = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) AS total_penjualan')
                            )
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereMonth('tb_detail_nota_penjualan.cn_at','=', $request->bulan)
                        ->whereYear('tb_detail_nota_penjualan.cn_at','=', $request->tahun)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.is_deleted', 0)
                        ->where('b.debet', 0)
                        ->where('tb_detail_nota_penjualan.is_cn', 1)
                        ->where('b.is_kredit', 0)
                        ->first();

        $new_total_total_kredit = 0;
        $new_total_total_kredit_terbayar = 0;
        $new_total_total_kredit_blm_terbayar = 0;
        $new_total_total_non_kredit = 0;
        $new_total_total_non_kredit_cash = 0;
        $new_total_total_non_kredit_non_cash = 0;
        $new_total_total_non_kredit_tt = 0;
        $new_total_total_penjualan = 0;
        $new_total_total_jasa_dokter = 0;
        $new_total_total_jasa_resep = 0;
        $new_total_total_paket_wd = 0;
        $new_total_total_lab = 0;
        $new_total_total_apd = 0;

        $total_diskon = $detail_penjualan->total_diskon_persen + $penjualan2->total_diskon_rp;
        $total_3 = $detail_penjualan->total-$total_diskon;
        $grand_total = $total_3+$penjualan2->total_jasa_dokter+$penjualan2->total_jasa_resep+$penjualan2->total_paket_wd+$penjualan2->total_lab+$penjualan2->total_apd;
        $total_cash = $grand_total - $penjualan2->total_debet;
        $total_penjualan_cn_cash = 0;
        if(!empty($penjualan_cn_cash->total_penjualan)) {
            $total_penjualan_cn_cash = $penjualan_cn_cash->total_penjualan - $detail_penjualan_cn->total_diskon_persen;
        }
        $total_penjualan_cn_debet = 0;
        if(!empty($penjualan_cn_debet->total_debet)) {
            $total_penjualan_cn_debet = $detail_penjualan_cn->total-$total_penjualan_cn_cash;
        }
        $total_cn = 0 + $detail_penjualan_cn->total - $detail_penjualan_cn->total_diskon_persen;
        $total_2 = $grand_total-$total_cn;
        $total_cash_x = $total_cash-$total_penjualan_cn_cash;
        $total_debet_x = $penjualan2->total_debet-$total_penjualan_cn_debet;
        $total_penjualan = $total_2-($penjualan2->total_jasa_dokter+$penjualan2->total_jasa_resep+$penjualan2->total_paket_wd+$penjualan2->total_lab+$penjualan2->total_apd);
        $total_3_format = number_format($total_2,0,',',',');
        $g_format = number_format($total_debet_x,0,',',',');
        $h_format = number_format($total_cash_x,0,',',',');
        $a_format = number_format($penjualan2->total_jasa_dokter,0,',',',');
        $b_format = number_format($penjualan2->total_jasa_resep,0,',',',');
        $c_format = number_format($penjualan2->total_paket_wd,0,',',',');
        $d_format = number_format($penjualan2->total_lab,0,',',',');
        $e_format = number_format($penjualan2->total_apd,0,',',',');
        $f_format = number_format($penjualan_closing->jumlah_tt_a,0,',',',');
        $total_penjualan_format = number_format($total_penjualan,0,',',',');
        $new_data = array();
        $new_data['kerjasama'] = 'Umum';
        $new_data['total_kredit'] = '-';
        $new_data['total_kredit_terbayar'] = '-';
        $new_data['total_kredit_blm_terbayar'] = '-';
        $new_data['total_non_kredit'] = 'Rp '.$total_3_format;
        $new_data['total_non_kredit_cash'] = 'Rp '.$h_format;
        $new_data['total_non_kredit_non_cash'] = 'Rp '.$g_format;
        $new_data['total_non_kredit_tt'] = 'Rp '.$f_format;
        $new_data['total_penjualan'] = 'Rp '.$total_penjualan_format;
        $new_data['total_jasa_dokter'] = 'Rp '.$a_format;
        $new_data['total_jasa_resep'] = 'Rp '.$b_format;
        $new_data['total_paket_wd'] = 'Rp '.$c_format;
        $new_data['total_lab'] = 'Rp '.$d_format;
        $new_data['total_apd'] = 'Rp '.$e_format;
        $penjualan[] = $new_data;

        # update 
        $new_total_total_non_kredit = $new_total_total_non_kredit + $total_2;
        $new_total_total_non_kredit_cash = $new_total_total_non_kredit_cash + $total_cash_x;
        $new_total_total_non_kredit_non_cash = $new_total_total_non_kredit_non_cash + $total_debet_x;
        $new_total_total_non_kredit_tt = $new_total_total_non_kredit_tt + $penjualan_closing->jumlah_tt_a;
        $new_total_total_penjualan = $new_total_total_penjualan + $total_penjualan;
        $new_total_total_jasa_dokter = $new_total_total_jasa_dokter + $penjualan2->total_jasa_dokter;
        $new_total_total_jasa_resep = $new_total_total_jasa_resep + $penjualan2->total_jasa_resep;
        $new_total_total_paket_wd = $new_total_total_paket_wd + $penjualan2->total_paket_wd;
        $new_total_total_lab = $new_total_total_lab + $penjualan2->total_lab;
        $new_total_total_apd = $new_total_total_apd + $penjualan2->total_apd;

        $vendors = MasterVendor::where('is_deleted', 0)->get();
        foreach ($vendors as $key => $val) {
            $detail_penjualan_kredit = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereMonth('b.tgl_nota','=', $request->bulan)
                        ->whereYear('b.tgl_nota','=', $request->tahun)
                        ->where('b.id_apotek_nota','=', $id_apotek)
                        ->where('b.id_vendor','=', $val->id)
                        ->where('b.is_deleted', 0)
                        ->where('b.is_kredit', 1)
                        ->where('tb_detail_nota_penjualan.is_cn', 0)
                        ->first();

            $penjualan_kredit =  DB::table('tb_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'),
                                DB::raw('SUM(tb_nota_penjualan.harga_wd) AS total_paket_wd'),
                                DB::raw('SUM(tb_nota_penjualan.biaya_lab) AS total_lab'),
                                DB::raw('SUM(tb_nota_penjualan.biaya_apd) AS total_apd'),
                                DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'))
                        ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                        ->whereMonth('tgl_nota','=', $request->bulan)
                        ->whereYear('tgl_nota','=', $request->tahun)
                        ->where('id_apotek_nota','=', $id_apotek)
                        ->where('id_vendor','=', $val->id)
                        ->where('tb_nota_penjualan.is_deleted', 0)
                        ->where('tb_nota_penjualan.is_kredit', 1)
                        ->first();

            $total_cash_kredit = $detail_penjualan_kredit->total - $penjualan_kredit->total_debet;
            $total_cash_kredit_format = number_format($total_cash_kredit,0,',',',');


            $detail_penjualan_kredit_terbayar = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'),
                                DB::raw('SUM(b.diskon_vendor/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_vendor')
                            )
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereMonth('b.is_lunas_pembayaran_kredit_at','=', $request->bulan)
                        ->whereYear('b.is_lunas_pembayaran_kredit_at','=', $request->tahun)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.id_vendor','=', $val->id)
                        ->where('b.is_deleted', 0)
                        ->where('b.is_kredit', 1)
                        ->where('b.is_lunas_pembayaran_kredit', 1)
                        ->where('tb_detail_nota_penjualan.is_cn', 0)
                        ->first();
        
            $penjualan_kredit_terbayar =  DB::table('tb_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'),
                                DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'))
                        ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                        ->whereMonth('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','=',  $request->bulan)
                        ->whereYear('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','=',  $request->tahun)
                        ->where('tb_nota_penjualan.id_apotek_nota','=',$id_apotek)
                        ->where('tb_nota_penjualan.id_vendor','=', $val->id)
                        ->where('tb_nota_penjualan.is_deleted', 0)
                        ->where('tb_nota_penjualan.is_kredit', 1)
                        ->where('tb_nota_penjualan.is_lunas_pembayaran_kredit', 1)
                        ->first();


            $total_cash_kredit_terbayar = ($detail_penjualan_kredit_terbayar->total + $penjualan_kredit_terbayar->total_jasa_dokter + $penjualan_kredit_terbayar->total_jasa_resep) - $penjualan_kredit_terbayar->total_debet-$detail_penjualan_kredit_terbayar->total_diskon_vendor;
            $total_penjualan_kredit_terbayar = $penjualan_kredit_terbayar->total_debet+$total_cash_kredit_terbayar;
            $total_penjualan_kredit_terbayar_format = number_format($total_penjualan_kredit_terbayar,0,',',',');
            $total_penjualan_kredit_blm_terbayar = $total_cash_kredit - $total_penjualan_kredit_terbayar;
            $total_penjualan_kredit_blm_terbayar_format = number_format($total_penjualan_kredit_blm_terbayar,0,',',',');

            $total_penjualan = $detail_penjualan_kredit->total-($penjualan_kredit->total_jasa_dokter+$penjualan_kredit->total_jasa_resep+$penjualan_kredit->total_paket_wd+$penjualan_kredit->total_lab+$penjualan_kredit->total_apd);
         
            $a_format = number_format($penjualan_kredit->total_jasa_dokter,0,',',',');
            $b_format = number_format($penjualan_kredit->total_jasa_resep,0,',',',');
            $c_format = number_format($penjualan_kredit->total_paket_wd,0,',',',');
            $d_format = number_format($penjualan_kredit->total_lab,0,',',',');
            $e_format = number_format($penjualan_kredit->total_apd,0,',',',');
            $total_penjualan_format = number_format($total_penjualan,0,',',',');

            $new_data = array();
            $new_data['kerjasama'] = $val->nama;
            $new_data['total_kredit'] = 'Rp '.$total_cash_kredit_format;
            $new_data['total_kredit_terbayar'] = 'Rp '.$total_penjualan_kredit_terbayar_format;
            $new_data['total_kredit_blm_terbayar'] = 'Rp '.$total_penjualan_kredit_blm_terbayar_format;
            $new_data['total_non_kredit'] = '-';
            $new_data['total_non_kredit_cash'] = '-';
            $new_data['total_non_kredit_non_cash'] = '-';
            $new_data['total_non_kredit_tt'] = '-';
            $new_data['total_penjualan'] = 'Rp '.$total_penjualan_format;
            $new_data['total_jasa_dokter'] = 'Rp '.$a_format;
            $new_data['total_jasa_resep'] = 'Rp '.$b_format;
            $new_data['total_paket_wd'] = 'Rp '.$c_format;
            $new_data['total_lab'] = 'Rp '.$d_format;
            $new_data['total_apd'] = 'Rp '.$e_format;
            $penjualan[] = $new_data;

            # update 
            $new_total_total_kredit = $new_total_total_kredit + $total_cash_kredit;
            $new_total_total_kredit_terbayar = $new_total_total_kredit_terbayar + $total_penjualan_kredit_terbayar;
            $new_total_total_kredit_blm_terbayar = $new_total_total_kredit_blm_terbayar + $total_penjualan_kredit_blm_terbayar;
            $new_total_total_penjualan = $new_total_total_penjualan + $total_penjualan;
            $new_total_total_jasa_dokter = $new_total_total_jasa_dokter + $penjualan_kredit->total_jasa_dokter;
            $new_total_total_jasa_resep = $new_total_total_jasa_resep + $penjualan_kredit->total_jasa_resep;
            $new_total_total_paket_wd = $new_total_total_paket_wd + $penjualan_kredit->total_paket_wd;
            $new_total_total_lab = $new_total_total_lab + $penjualan_kredit->total_lab;
            $new_total_total_apd = $new_total_total_apd + $penjualan_kredit->total_apd;
        }

        foreach ($penjualan as $key => $obj) {
            $data.= '<tr>
                            <td class="text-left">'.$obj['kerjasama'].'</td>
                            <td class="text-right">'.$obj['total_kredit'].'</td>
                            <td class="text-right">'.$obj['total_kredit_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_kredit_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_cash'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_non_cash'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_tt'].'</td>
                            <td class="text-right">'.$obj['total_penjualan'].'</td>
                            <td class="text-right">'.$obj['total_jasa_dokter'].'</td>
                            <td class="text-right">'.$obj['total_jasa_resep'].'</td>
                            <td class="text-right">'.$obj['total_paket_wd'].'</td>
                            <td class="text-right">'.$obj['total_lab'].'</td>
                            <td class="text-right">'.$obj['total_apd'].'</td>
                        </tr>';
        }

        if(count($penjualan) == 0) {
            $data.= '<tr>
                            <td class="text-center" colspan="14">TIDAK ADA PENJUALAN</td>
                        </tr>';
        }

        $new_total_total_kredit_format = number_format($new_total_total_kredit,0,',',',');
        $new_total_total_kredit_terbayar_format = number_format($new_total_total_kredit_terbayar,0,',',',');
        $new_total_total_kredit_blm_terbayar_format = number_format($new_total_total_kredit_blm_terbayar,0,',',',');
        $new_total_total_non_kredit_format = number_format($new_total_total_non_kredit,0,',',',');
        $new_total_total_non_kredit_cash_format = number_format($new_total_total_non_kredit_cash,0,',',',');
        $new_total_total_non_kredit_non_cash_format = number_format($new_total_total_non_kredit_non_cash,0,',',',');
        $new_total_total_non_kredit_tt_format = number_format($new_total_total_non_kredit_tt,0,',',',');
        $new_total_total_penjualan_format = number_format($new_total_total_penjualan,0,',',',');
        $new_total_total_jasa_dokter_format = number_format($new_total_total_jasa_dokter,0,',',',');
        $new_total_total_jasa_resep_format = number_format($new_total_total_jasa_resep,0,',',',');
        $new_total_total_paket_wd_format = number_format($new_total_total_paket_wd,0,',',',');
        $new_total_total_lab_format = number_format($new_total_total_lab,0,',',',');
        $new_total_total_apd_format = number_format($new_total_total_apd,0,',',',');

        $grand_total = $new_total_total_kredit+$new_total_total_non_kredit;
        $grand_total_format = number_format($grand_total,0,',',',');

        $data .= '<tr>
                    <td class="text-left"><b>TOTAL</b></td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_format.'</td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_non_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_tt_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_penjualan_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_jasa_dokter_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_jasa_resep_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_paket_wd_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_lab_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_apd_format.'</td>
                </tr>';

        $data .= '<tr>
                    <td class="text-left" colspan="8"><b>GRAND TOTAL</b></td>
                    <td class="text-right text-white" style="background-color:#0097a7;" colspan="6">Rp '.$grand_total_format.'</td>
                </tr>';

        $data .= '</tbody></table>';
        echo $data;
    }

    public function recap_all_pembelian_load_view(Request $request) {
        $id_apotek = session('id_apotek_active');
        $data = '';
        $data .= '<table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%" colspan="14" class="text-center text-white" style="background-color:#455a64;">PEMBELIAN</th>
                        </tr>
                        <tr>
                            <th width="10%" rowspan="3" class="text-center">SUPLIER</th>
                            <th width="20%" rowspan="3" class="text-center text-white" style="background-color:#9575cd;">TOTAL PEMBELIAN</th>
                            <th width="20%" colspan="5" class="text-center text-white" style="background-color:#7e57c2;">RINCIAN</th>
                            <th width="20%" rowspan="2" colspan="2" class="text-center text-white" style="background-color:#673ab7;">JATUH TEMPO</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#7e57c2;" rowspan="2">Cash</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;" colspan="2">Credit</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;" colspan="2">Konsinyasi</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Belum terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Belum terbayar</th>
                            <th class="text-center text-white" style="background-color:#673ab7;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#673ab7;">Belum terbayar</th>
                        </tr>
                    </thead>
                    <tbody>';
        $pembelian = array();

        $new_total_pembelian = 0;
        $new_total_pembelian_cash = 0;
        $new_total_pembelian_credit_terbayar = 0;
        $new_total_pembelian_credit_blm_terbayar = 0;
        $new_total_pembelian_konsinyasi_terbayar = 0;
        $new_total_pembelian_konsinyasi_blm_terbayar = 0;
        $new_total_pembelian_jatuhtempo_terbayar = 0;
        $new_total_pembelian_jetuhtempo_blm_terbayar = 0;

        $supliers = MasterSuplier::where('is_deleted', 0)->get();
        foreach ($supliers as $key => $val) {
            $detail_pembelian = DB::table('tb_detail_nota_pembelian')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                'b.diskon1',
                                'b.diskon2',
                                'b.ppn')
                        ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                        ->whereMonth('b.tgl_nota','=',$request->bulan)
                        ->whereYear('b.tgl_nota','=', $request->tahun)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.id_suplier','=',$val->id)
                        ->where('b.is_deleted', 0)
                        ->first();

            if($detail_pembelian->total != 0) {
                $detail_pembelian_cash = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',1)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_credit = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_konsinyasi = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',3)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_credit_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();


                $detail_pembelian_konsinyasi_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',3)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();

                $detail_pembelian_jatuh_tempo_blm_bayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->whereMonth('b.tgl_jatuh_tempo','=',$request->bulan)
                            ->whereYear('b.tgl_jatuh_tempo','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 0)
                            ->first();

                $detail_pembelian_jatuh_tempo_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereMonth('b.tgl_nota','=',$request->bulan)
                            ->whereYear('b.tgl_nota','=', $request->tahun)
                            ->whereMonth('b.tgl_jatuh_tempo','=',$request->bulan)
                            ->whereYear('b.tgl_jatuh_tempo','=', $request->tahun)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();

                $total_pembelian1 = $detail_pembelian->total-($detail_pembelian->diskon1+$detail_pembelian->diskon2);
                $total_pembelian = $total_pembelian1 + ($total_pembelian1 * $detail_pembelian->ppn/100);

                $total_pembelian_cash1 = $detail_pembelian_cash->total-($detail_pembelian_cash->diskon1+$detail_pembelian_cash->diskon2);
                $total_pembelian_cash = $total_pembelian_cash1 + ($total_pembelian_cash1 * $detail_pembelian_cash->ppn/100);

                $total_pembelian_credit_terbayar1 = $detail_pembelian_credit_terbayar->total-($detail_pembelian_credit_terbayar->diskon1+$detail_pembelian_credit_terbayar->diskon2);
                $total_pembelian_credit_terbayar = $total_pembelian_credit_terbayar1 + ($total_pembelian_credit_terbayar1 * $detail_pembelian_credit_terbayar->ppn/100);

                $total_pembelian_credit_blm_terbayar1 = $detail_pembelian_credit->total-($detail_pembelian_credit->diskon1+$detail_pembelian_credit->diskon2);
                $total_pembelian_credit_blm_terbayar = $total_pembelian_credit_blm_terbayar1 + ($total_pembelian_credit_blm_terbayar1 * $detail_pembelian_credit->ppn/100);
                $total_pembelian_credit_blm_terbayar = $total_pembelian_credit_blm_terbayar - $total_pembelian_credit_terbayar;

                $total_pembelian_konsinyasi_terbayar1 = $detail_pembelian_konsinyasi_terbayar->total-($detail_pembelian_konsinyasi_terbayar->diskon1+$detail_pembelian_konsinyasi_terbayar->diskon2);
                $total_pembelian_konsinyasi_terbayar = $total_pembelian_konsinyasi_terbayar1 + ($total_pembelian_konsinyasi_terbayar1 * $detail_pembelian_konsinyasi_terbayar->ppn/100);

                $total_pembelian_konsinyasi_blm_terbayar1 = $detail_pembelian_konsinyasi->total-($detail_pembelian_konsinyasi->diskon1+$detail_pembelian_konsinyasi->diskon2);
                $total_pembelian_konsinyasi_blm_terbayar2 = $total_pembelian_konsinyasi_blm_terbayar1 + ($total_pembelian_konsinyasi_blm_terbayar1 * $detail_pembelian_konsinyasi->ppn/100);
                $total_pembelian_konsinyasi_blm_terbayar3 = $detail_pembelian_konsinyasi_terbayar->total-($detail_pembelian_konsinyasi_terbayar->diskon1+$detail_pembelian_konsinyasi_terbayar->diskon2);
                $total_pembelian_konsinyasi_blm_terbayar4 = $total_pembelian_konsinyasi_blm_terbayar3 + ($total_pembelian_konsinyasi_blm_terbayar3 * $detail_pembelian_konsinyasi_terbayar->ppn/100);
                $total_pembelian_konsinyasi_blm_terbayar = $total_pembelian_konsinyasi_blm_terbayar2 - $total_pembelian_konsinyasi_blm_terbayar4;

                $total_pembelian_jatuhtempo_terbayar1 = $detail_pembelian_jatuh_tempo_terbayar->total-($detail_pembelian_jatuh_tempo_terbayar->diskon1+$detail_pembelian_jatuh_tempo_terbayar->diskon2);
                $total_pembelian_jatuhtempo_terbayar = $total_pembelian_jatuhtempo_terbayar1 + ($total_pembelian_jatuhtempo_terbayar1 * $detail_pembelian_jatuh_tempo_terbayar->ppn/100);

                $total_pembelian_jetuhtempo_blm_terbayar1 = $detail_pembelian_jatuh_tempo_blm_bayar->total-($detail_pembelian_jatuh_tempo_blm_bayar->diskon1+$detail_pembelian_jatuh_tempo_blm_bayar->diskon2);
                $total_pembelian_jetuhtempo_blm_terbayar = $total_pembelian_jetuhtempo_blm_terbayar1 + ($total_pembelian_jetuhtempo_blm_terbayar1 * $detail_pembelian_jatuh_tempo_blm_bayar->ppn/100);

                $new_total_pembelian = $new_total_pembelian+$detail_pembelian->total;
                $new_total_pembelian_cash = $new_total_pembelian_cash+$detail_pembelian_cash->total;
                $new_total_pembelian_credit_terbayar = $new_total_pembelian_credit_terbayar+$detail_pembelian_credit_terbayar->total;
                $new_total_pembelian_credit_blm_terbayar = $new_total_pembelian_credit_blm_terbayar+$detail_pembelian_credit->total - $detail_pembelian_credit_terbayar->total;
                $new_total_pembelian_konsinyasi_terbayar = $new_total_pembelian_konsinyasi_terbayar+$detail_pembelian_konsinyasi_terbayar->total;
                $new_total_pembelian_konsinyasi_blm_terbayar = $new_total_pembelian_konsinyasi_blm_terbayar+$detail_pembelian_konsinyasi->total - $detail_pembelian_konsinyasi_terbayar->total;
                $new_total_pembelian_jatuhtempo_terbayar = $new_total_pembelian_jatuhtempo_terbayar+$detail_pembelian_jatuh_tempo_terbayar->total;
                $new_total_pembelian_jetuhtempo_blm_terbayar = $new_total_pembelian_jetuhtempo_blm_terbayar+$detail_pembelian_jatuh_tempo_blm_bayar->total;

                $total_pembelian_format = number_format($total_pembelian,0,',',',');
                $total_pembelian_cash_format = number_format($total_pembelian_cash,0,',',',');
                $total_pembelian_credit_terbayar_format = number_format($total_pembelian_credit_terbayar,0,',',',');
                $total_pembelian_credit_blm_terbayar_format = number_format($total_pembelian_credit_blm_terbayar,0,',',',');
                $total_pembelian_konsinyasi_terbayar_format = number_format($total_pembelian_konsinyasi_terbayar,0,',',',');
                $total_pembelian_konsinyasi_blm_terbayar_format = number_format($total_pembelian_konsinyasi_blm_terbayar,0,',',',');
                $total_pembelian_jatuhtempo_terbayar_format = number_format($total_pembelian_jatuhtempo_terbayar,0,',',',');
                $total_pembelian_jetuhtempo_blm_terbayar_format = number_format($total_pembelian_jetuhtempo_blm_terbayar,0,',',',');

                $new_data = array();
                $new_data['suplier'] = $val->nama;
                $new_data['total'] = $total_pembelian;
                $new_data['total_pembelian'] = 'Rp '.$total_pembelian_format;
                $new_data['total_pembelian_cash'] = 'Rp '.$total_pembelian_cash_format;
                $new_data['total_pembelian_credit_terbayar'] = 'Rp '.$total_pembelian_credit_terbayar_format;
                $new_data['total_pembelian_credit_blm_terbayar'] = 'Rp '.$total_pembelian_credit_blm_terbayar_format;
                $new_data['total_pembelian_konsinyasi_terbayar'] = 'Rp '.$total_pembelian_konsinyasi_terbayar_format;
                $new_data['total_pembelian_konsinyasi_blm_terbayar'] = 'Rp '.$total_pembelian_konsinyasi_blm_terbayar_format;
                $new_data['total_pembelian_jatuhtempo_terbayar'] = 'Rp '.$total_pembelian_jatuhtempo_terbayar_format;
                $new_data['total_pembelian_jetuhtempo_blm_terbayar'] = 'Rp '.$total_pembelian_jetuhtempo_blm_terbayar_format;
                $pembelian[] = $new_data;

            } 
        }

        foreach ($pembelian as $key => $obj) {
            $data.= '<tr>
                            <td class="text-left">'.$obj['suplier'].'</td>
                            <td class="text-right">'.$obj['total_pembelian'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_cash'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_credit_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_credit_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_konsinyasi_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_konsinyasi_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_jatuhtempo_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_jetuhtempo_blm_terbayar'].'</td>
                        </tr>';
        }

        if(count($pembelian) == 0) {
            $data.= '<tr>
                            <td class="text-center" colspan="9">TIDAK ADA PEMBELIAN</td>
                        </tr>';
        }

        $new_total_pembelian_format = number_format($new_total_pembelian,0,',',',');
        $new_total_pembelian_cash_format = number_format($new_total_pembelian_cash,0,',',',');
        $new_total_pembelian_credit_terbayar_format = number_format($new_total_pembelian_credit_terbayar,0,',',',');
        $new_total_pembelian_credit_blm_terbayar_format = number_format($new_total_pembelian_credit_blm_terbayar,0,',',',');
        $new_total_pembelian_konsinyasi_terbayar_format = number_format($new_total_pembelian_konsinyasi_terbayar,0,',',',');
        $new_total_pembelian_konsinyasi_blm_terbayar_format = number_format($new_total_pembelian_konsinyasi_blm_terbayar,0,',',',');
        $new_total_pembelian_jatuhtempo_terbayar_format = number_format($new_total_pembelian_jatuhtempo_terbayar,0,',',',');
        $new_total_pembelian_jetuhtempo_blm_terbayar_format = number_format($new_total_pembelian_jetuhtempo_blm_terbayar,0,',',',');

        $data .= '<tr>
                    <td class="text-left"><b>TOTAL</b></td>
                    <td class="text-right text-white" style="background-color:#9575cd;">Rp '.$new_total_pembelian_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_credit_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_credit_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_konsinyasi_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_konsinyasi_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#673ab7;">Rp '.$new_total_pembelian_jatuhtempo_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#673ab7;">Rp '.$new_total_pembelian_jetuhtempo_blm_terbayar_format.'</td>
                </tr>';

        $data .= '</tbody></table>';
        echo $data;
    }

    public function recap_perhari() {
        $tahun = date('Y');
        $bulan = date('m');

        return view('recap_perhari')->with(compact('tahun', 'bulan'));
    }

    public function recap_perhari_load_view(Request $request) {
        if($request->tanggal != "") {
            $split                      = explode("-", $request->tanggal);
            $tgl_awal       = date('Y-m-d H:i:s',strtotime($split[0]));
            $tgl_akhir      = date('Y-m-d H:i:s',strtotime($split[1]));
        } else {
            $tgl_awal       = date('Y-m-d H:i:s');
            $tgl_akhir      = date('Y-m-d H:i:s');
        }

        $id_apotek = session('id_apotek_active');
        $data = '';
        $data .= '<table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%" colspan="14" class="text-center text-white" style="background-color:#455a64;">PENJUALAN</th>
                        </tr>
                        <tr>
                            <th width="10%" rowspan="2" class="text-center">KERJASAMA</th>
                            <th width="20%" colspan="3" class="text-center text-white" style="background-color:#00bcd4;">KREDIT</th>
                            <th width="20%" colspan="4" class="text-center text-white" style="background-color:#00acc1;">NON KREDIT</th>
                            <th width="20%" colspan="6" class="text-center text-white" style="background-color:#0097a7;">RINCIAN NON KREDIT</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Total Penjualan</th>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#00bcd4;">Belum Terbayar</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Total Penjualan</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Cash</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">Non Cash</th>
                            <th class="text-center text-white" style="background-color:#00acc1;">TT</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Penjualan</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Jasa Dokter</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Jasa Resep</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Paket WT</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">Lab</th>
                            <th class="text-center text-white" style="background-color:#0097a7;">APD</th>
                        </tr>
                    </thead>
                    <tbody>';
        $penjualan = array();

        $detail_penjualan = DB::table('tb_detail_nota_penjualan')
                    ->select(
                            DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                            DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                            DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                            DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                    ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                    ->whereDate('b.tgl_nota','>=', $tgl_awal)
                    ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                    ->where('b.id_apotek_nota','=',$id_apotek)
                    ->where('b.is_deleted', 0)
                    ->where('b.is_kredit', 0)
                    ->first();

        $penjualan2 =  DB::table('tb_nota_penjualan')
                    ->select(
                            DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                            DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                            DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'),
                            DB::raw('SUM(tb_nota_penjualan.harga_wd) AS total_paket_wd'),
                            DB::raw('SUM(tb_nota_penjualan.biaya_lab) AS total_lab'),
                            DB::raw('SUM(tb_nota_penjualan.biaya_apd) AS total_apd'),
                            DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'))
                    ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                    ->whereDate('tgl_nota','>=', $tgl_awal)
                    ->whereDate('tgl_nota','<=', $tgl_akhir)
                    ->where('id_apotek_nota','=',$id_apotek)
                    ->where('tb_nota_penjualan.is_deleted', 0)
                    ->where('tb_nota_penjualan.is_kredit', 0)
                    ->first();

        $penjualan_closing = TransaksiPenjualanClosing::select([

                                    DB::raw('SUM(total_jasa_dokter) as total_jasa_dokter_a'),
                                    DB::raw('SUM(total_jasa_resep) as total_jasa_resep_a'),
                                    DB::raw('SUM(total_paket_wd) as total_paket_wd_a'),
                                    DB::raw('SUM(total_penjualan) as total_penjualan_a'),
                                    DB::raw('SUM(total_debet) as total_debet_a'),
                                    DB::raw('SUM(total_penjualan_cash) as total_penjualan_cash_a'),
                                    DB::raw('SUM(total_penjualan_cn) as total_penjualan_cn_a'),
                                    DB::raw('SUM(total_penjualan_kredit) as total_penjualan_kredit_a'),
                                    DB::raw('SUM(total_penjualan_kredit_terbayar) as total_penjualan_kredit_terbayar_a'),
                                    DB::raw('SUM(total_diskon) as total_diskon_a'),
                                    DB::raw('SUM(uang_seharusnya) as uang_seharusnya_a'),
                                    DB::raw('SUM(total_akhir) as total_akhir_a'),
                                    DB::raw('SUM(jumlah_tt) as jumlah_tt_a')
                                ])
                                ->whereDate('tanggal','>=', $tgl_awal)
                                ->whereDate('tanggal','<=', $tgl_akhir)
                                ->where('id_apotek_nota','=',$id_apotek)
                                ->first();

        $detail_penjualan_cn = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereDate('tb_detail_nota_penjualan.cn_at','>=', $tgl_awal)
                        ->whereDate('tb_detail_nota_penjualan.cn_at','<=', $tgl_akhir)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.is_deleted', 0)
                        ->where('tb_detail_nota_penjualan.is_cn', 1)
                        ->where('b.is_kredit', 0)
                        ->first();

        $penjualan_cn_cash = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah_cn) AS total_penjualan')
                            )
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereDate('tb_detail_nota_penjualan.cn_at','>=', $tgl_awal)
                        ->whereDate('tb_detail_nota_penjualan.cn_at','<=', $tgl_akhir)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.is_deleted', 0)
                        ->where('b.debet', 0)
                        ->where('tb_detail_nota_penjualan.is_cn', 1)
                        ->where('b.is_kredit', 0)
                        ->first();

        $new_total_total_kredit = 0;
        $new_total_total_kredit_terbayar = 0;
        $new_total_total_kredit_blm_terbayar = 0;
        $new_total_total_non_kredit = 0;
        $new_total_total_non_kredit_cash = 0;
        $new_total_total_non_kredit_non_cash = 0;
        $new_total_total_non_kredit_tt = 0;
        $new_total_total_penjualan = 0;
        $new_total_total_jasa_dokter = 0;
        $new_total_total_jasa_resep = 0;
        $new_total_total_paket_wd = 0;
        $new_total_total_lab = 0;
        $new_total_total_apd = 0;

        $total_diskon = $detail_penjualan->total_diskon_persen + $penjualan2->total_diskon_rp;
        $total_3 = $detail_penjualan->total-$total_diskon;
        $grand_total = $total_3+$penjualan2->total_jasa_dokter+$penjualan2->total_jasa_resep+$penjualan2->total_paket_wd+$penjualan2->total_lab+$penjualan2->total_apd;
        $total_cash = $grand_total - $penjualan2->total_debet;
        $total_penjualan_cn_cash = 0;
        if(!empty($penjualan_cn_cash->total_penjualan)) {
            $total_penjualan_cn_cash = $penjualan_cn_cash->total_penjualan - $detail_penjualan_cn->total_diskon_persen;
        }
        $total_penjualan_cn_debet = 0;
        if(!empty($penjualan_cn_debet->total_debet)) {
            $total_penjualan_cn_debet = $detail_penjualan_cn->total-$total_penjualan_cn_cash;
        }
        $total_cn = 0 + $detail_penjualan_cn->total - $detail_penjualan_cn->total_diskon_persen;
        $total_2 = $grand_total-$total_cn;
        $total_cash_x = $total_cash-$total_penjualan_cn_cash;
        $total_debet_x = $penjualan2->total_debet-$total_penjualan_cn_debet;
        $total_penjualan = $total_2-($penjualan2->total_jasa_dokter+$penjualan2->total_jasa_resep+$penjualan2->total_paket_wd+$penjualan2->total_lab+$penjualan2->total_apd);
        $total_3_format = number_format($total_2,0,',',',');
        $g_format = number_format($total_debet_x,0,',',',');
        $h_format = number_format($total_cash_x,0,',',',');
        $a_format = number_format($penjualan2->total_jasa_dokter,0,',',',');
        $b_format = number_format($penjualan2->total_jasa_resep,0,',',',');
        $c_format = number_format($penjualan2->total_paket_wd,0,',',',');
        $d_format = number_format($penjualan2->total_lab,0,',',',');
        $e_format = number_format($penjualan2->total_apd,0,',',',');
        $f_format = number_format($penjualan_closing->jumlah_tt_a,0,',',',');
        $total_penjualan_format = number_format($total_penjualan,0,',',',');
        $new_data = array();
        $new_data['kerjasama'] = 'Umum';
        $new_data['total_kredit'] = '-';
        $new_data['total_kredit_terbayar'] = '-';
        $new_data['total_kredit_blm_terbayar'] = '-';
        $new_data['total_non_kredit'] = 'Rp '.$total_3_format;
        $new_data['total_non_kredit_cash'] = 'Rp '.$h_format;
        $new_data['total_non_kredit_non_cash'] = 'Rp '.$g_format;
        $new_data['total_non_kredit_tt'] = 'Rp '.$f_format;
        $new_data['total_penjualan'] = 'Rp '.$total_penjualan_format;
        $new_data['total_jasa_dokter'] = 'Rp '.$a_format;
        $new_data['total_jasa_resep'] = 'Rp '.$b_format;
        $new_data['total_paket_wd'] = 'Rp '.$c_format;
        $new_data['total_lab'] = 'Rp '.$d_format;
        $new_data['total_apd'] = 'Rp '.$e_format;
        $penjualan[] = $new_data;

        # update 
        $new_total_total_non_kredit = $new_total_total_non_kredit + $total_2;
        $new_total_total_non_kredit_cash = $new_total_total_non_kredit_cash + $total_cash_x;
        $new_total_total_non_kredit_non_cash = $new_total_total_non_kredit_non_cash + $total_debet_x;
        $new_total_total_non_kredit_tt = $new_total_total_non_kredit_tt + $penjualan_closing->jumlah_tt_a;
        $new_total_total_penjualan = $new_total_total_penjualan + $total_penjualan;
        $new_total_total_jasa_dokter = $new_total_total_jasa_dokter + $penjualan2->total_jasa_dokter;
        $new_total_total_jasa_resep = $new_total_total_jasa_resep + $penjualan2->total_jasa_resep;
        $new_total_total_paket_wd = $new_total_total_paket_wd + $penjualan2->total_paket_wd;
        $new_total_total_lab = $new_total_total_lab + $penjualan2->total_lab;
        $new_total_total_apd = $new_total_total_apd + $penjualan2->total_apd;

        $vendors = MasterVendor::where('is_deleted', 0)->get();
        foreach ($vendors as $key => $val) {
            $detail_penjualan_kredit = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'))
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereDate('b.tgl_nota','>=', $tgl_awal)
                        ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                        ->where('b.id_apotek_nota','=', $id_apotek)
                        ->where('b.id_vendor','=', $val->id)
                        ->where('b.is_deleted', 0)
                        ->where('b.is_kredit', 1)
                        ->where('tb_detail_nota_penjualan.is_cn', 0)
                        ->first();

            $penjualan_kredit =  DB::table('tb_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'),
                                DB::raw('SUM(tb_nota_penjualan.harga_wd) AS total_paket_wd'),
                                DB::raw('SUM(tb_nota_penjualan.biaya_lab) AS total_lab'),
                                DB::raw('SUM(tb_nota_penjualan.biaya_apd) AS total_apd'),
                                DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'))
                        ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                        ->whereDate('tgl_nota','>=', $tgl_awal)
                        ->whereDate('tgl_nota','<=', $tgl_akhir)
                        ->where('id_apotek_nota','=', $id_apotek)
                        ->where('id_vendor','=', $val->id)
                        ->where('tb_nota_penjualan.is_deleted', 0)
                        ->where('tb_nota_penjualan.is_kredit', 1)
                        ->first();

            $total_cash_kredit = $detail_penjualan_kredit->total - $penjualan_kredit->total_debet;
            $total_cash_kredit_format = number_format($total_cash_kredit,0,',',',');


            $detail_penjualan_kredit_terbayar = DB::table('tb_detail_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) AS total_penjualan'),
                                DB::raw('SUM(tb_detail_nota_penjualan.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah) - tb_detail_nota_penjualan.diskon) AS total'),
                                DB::raw('SUM(b.diskon_persen/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_persen'),
                                DB::raw('SUM(b.diskon_vendor/100 * ((tb_detail_nota_penjualan.harga_jual * tb_detail_nota_penjualan.jumlah)- tb_detail_nota_penjualan.diskon)) AS total_diskon_vendor')
                            )
                        ->join('tb_nota_penjualan as b','b.id','=','tb_detail_nota_penjualan.id_nota')
                        ->whereDate('b.is_lunas_pembayaran_kredit_at','>=', $tgl_awal)
                        ->whereDate('b.is_lunas_pembayaran_kredit_at','<=', $tgl_akhir)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.id_vendor','=', $val->id)
                        ->where('b.is_deleted', 0)
                        ->where('b.is_kredit', 1)
                        ->where('b.is_lunas_pembayaran_kredit', 1)
                        ->where('tb_detail_nota_penjualan.is_cn', 0)
                        ->first();
        
            $penjualan_kredit_terbayar =  DB::table('tb_nota_penjualan')
                        ->select(
                                DB::raw('SUM(tb_nota_penjualan.biaya_jasa_dokter) AS total_jasa_dokter'),
                                DB::raw('SUM(a.biaya) AS total_jasa_resep'),
                                DB::raw('SUM(tb_nota_penjualan.diskon_rp) AS total_diskon_rp'),
                                DB::raw('SUM(tb_nota_penjualan.debet) AS total_debet'))
                        ->join('tb_m_jasa_resep as a','a.id','=','tb_nota_penjualan.id_jasa_resep')
                        ->whereDate('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','>=', $tgl_awal)
                        ->whereDate('tb_nota_penjualan.is_lunas_pembayaran_kredit_at','<=', $tgl_akhir)
                        ->where('tb_nota_penjualan.id_apotek_nota','=',$id_apotek)
                        ->where('tb_nota_penjualan.id_vendor','=', $val->id)
                        ->where('tb_nota_penjualan.is_deleted', 0)
                        ->where('tb_nota_penjualan.is_kredit', 1)
                        ->where('tb_nota_penjualan.is_lunas_pembayaran_kredit', 1)
                        ->first();


            $total_cash_kredit_terbayar = ($detail_penjualan_kredit_terbayar->total + $penjualan_kredit_terbayar->total_jasa_dokter + $penjualan_kredit_terbayar->total_jasa_resep) - $penjualan_kredit_terbayar->total_debet-$detail_penjualan_kredit_terbayar->total_diskon_vendor;
            $total_penjualan_kredit_terbayar = $penjualan_kredit_terbayar->total_debet+$total_cash_kredit_terbayar;
            $total_penjualan_kredit_terbayar_format = number_format($total_penjualan_kredit_terbayar,0,',',',');
            $total_penjualan_kredit_blm_terbayar = $total_cash_kredit - $total_penjualan_kredit_terbayar;
            $total_penjualan_kredit_blm_terbayar_format = number_format($total_penjualan_kredit_blm_terbayar,0,',',',');
            $total_penjualan = $detail_penjualan_kredit->total-($penjualan_kredit->total_jasa_dokter+$penjualan_kredit->total_jasa_resep+$penjualan_kredit->total_paket_wd+$penjualan_kredit->total_lab+$penjualan_kredit->total_apd);
         
            $a_format = number_format($penjualan_kredit->total_jasa_dokter,0,',',',');
            $b_format = number_format($penjualan_kredit->total_jasa_resep,0,',',',');
            $c_format = number_format($penjualan_kredit->total_paket_wd,0,',',',');
            $d_format = number_format($penjualan_kredit->total_lab,0,',',',');
            $e_format = number_format($penjualan_kredit->total_apd,0,',',',');
            $total_penjualan_format = number_format($total_penjualan,0,',',',');

            $new_data = array();
            $new_data['kerjasama'] = $val->nama;
            $new_data['total_kredit'] = 'Rp '.$total_cash_kredit_format;
            $new_data['total_kredit_terbayar'] = 'Rp '.$total_penjualan_kredit_terbayar_format;
            $new_data['total_kredit_blm_terbayar'] = 'Rp '.$total_penjualan_kredit_blm_terbayar_format;
            $new_data['total_non_kredit'] = '-';
            $new_data['total_non_kredit_cash'] = '-';
            $new_data['total_non_kredit_non_cash'] = '-';
            $new_data['total_non_kredit_tt'] = '-';
            $new_data['total_penjualan'] = 'Rp '.$total_penjualan_format;
            $new_data['total_jasa_dokter'] = 'Rp '.$a_format;
            $new_data['total_jasa_resep'] = 'Rp '.$b_format;
            $new_data['total_paket_wd'] = 'Rp '.$c_format;
            $new_data['total_lab'] = 'Rp '.$d_format;
            $new_data['total_apd'] = 'Rp '.$e_format;
            $penjualan[] = $new_data;

            # update 
            $new_total_total_kredit = $new_total_total_kredit + $total_cash_kredit;
            $new_total_total_kredit_terbayar = $new_total_total_kredit_terbayar + $total_penjualan_kredit_terbayar;
            $new_total_total_kredit_blm_terbayar = $new_total_total_kredit_blm_terbayar + $total_penjualan_kredit_blm_terbayar;
            $new_total_total_penjualan = $new_total_total_penjualan + $total_penjualan;
            $new_total_total_jasa_dokter = $new_total_total_jasa_dokter + $penjualan_kredit->total_jasa_dokter;
            $new_total_total_jasa_resep = $new_total_total_jasa_resep + $penjualan_kredit->total_jasa_resep;
            $new_total_total_paket_wd = $new_total_total_paket_wd + $penjualan_kredit->total_paket_wd;
            $new_total_total_lab = $new_total_total_lab + $penjualan_kredit->total_lab;
            $new_total_total_apd = $new_total_total_apd + $penjualan_kredit->total_apd;
        }

        foreach ($penjualan as $key => $obj) {
            $data.= '<tr>
                            <td class="text-left">'.$obj['kerjasama'].'</td>
                            <td class="text-right">'.$obj['total_kredit'].'</td>
                            <td class="text-right">'.$obj['total_kredit_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_kredit_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_cash'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_non_cash'].'</td>
                            <td class="text-right">'.$obj['total_non_kredit_tt'].'</td>
                            <td class="text-right">'.$obj['total_penjualan'].'</td>
                            <td class="text-right">'.$obj['total_jasa_dokter'].'</td>
                            <td class="text-right">'.$obj['total_jasa_resep'].'</td>
                            <td class="text-right">'.$obj['total_paket_wd'].'</td>
                            <td class="text-right">'.$obj['total_lab'].'</td>
                            <td class="text-right">'.$obj['total_apd'].'</td>
                        </tr>';
        }


        if(count($penjualan) == 0) {
            $data.= '<tr>
                            <td class="text-center" colspan="14">TIDAK ADA PENJUALAN</td>
                        </tr>';
        }


        $new_total_total_kredit_format = number_format($new_total_total_kredit,0,',',',');
        $new_total_total_kredit_terbayar_format = number_format($new_total_total_kredit_terbayar,0,',',',');
        $new_total_total_kredit_blm_terbayar_format = number_format($new_total_total_kredit_blm_terbayar,0,',',',');
        $new_total_total_non_kredit_format = number_format($new_total_total_non_kredit,0,',',',');
        $new_total_total_non_kredit_cash_format = number_format($new_total_total_non_kredit_cash,0,',',',');
        $new_total_total_non_kredit_non_cash_format = number_format($new_total_total_non_kredit_non_cash,0,',',',');
        $new_total_total_non_kredit_tt_format = number_format($new_total_total_non_kredit_tt,0,',',',');
        $new_total_total_penjualan_format = number_format($new_total_total_penjualan,0,',',',');
        $new_total_total_jasa_dokter_format = number_format($new_total_total_jasa_dokter,0,',',',');
        $new_total_total_jasa_resep_format = number_format($new_total_total_jasa_resep,0,',',',');
        $new_total_total_paket_wd_format = number_format($new_total_total_paket_wd,0,',',',');
        $new_total_total_lab_format = number_format($new_total_total_lab,0,',',',');
        $new_total_total_apd_format = number_format($new_total_total_apd,0,',',',');

        $data .= '<tr>
                    <td class="text-left"><b>TOTAL</b></td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_format.'</td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#00bcd4;">Rp '.$new_total_total_kredit_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_non_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#00acc1;">Rp '.$new_total_total_non_kredit_tt_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_penjualan_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_jasa_dokter_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_jasa_resep_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_paket_wd_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_lab_format.'</td>
                    <td class="text-right text-white" style="background-color:#0097a7;">Rp '.$new_total_total_apd_format.'</td>
                </tr>';

        $data .= '</tbody></table>';
        echo $data;
    }

    public function recap_perhari_pembelian_load_view(Request $request) {
        if($request->tanggal != "") {
            $split                      = explode("-", $request->tanggal);
            $tgl_awal       = date('Y-m-d H:i:s',strtotime($split[0]));
            $tgl_akhir      = date('Y-m-d H:i:s',strtotime($split[1]));
        } else {
            $tgl_awal       = date('Y-m-d H:i:s');
            $tgl_akhir      = date('Y-m-d H:i:s');
        }

        $id_apotek = session('id_apotek_active');
        $data = '';
        $data .= '<table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%" colspan="14" class="text-center text-white" style="background-color:#455a64;">PEMBELIAN</th>
                        </tr>
                        <tr>
                            <th width="10%" rowspan="3" class="text-center">SUPLIER</th>
                            <th width="20%" rowspan="3" class="text-center text-white" style="background-color:#9575cd;">TOTAL PEMBELIAN</th>
                            <th width="20%" colspan="5" class="text-center text-white" style="background-color:#7e57c2;">RINCIAN</th>
                            <th width="20%" rowspan="2" colspan="2" class="text-center text-white" style="background-color:#673ab7;">JATUH TEMPO</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#7e57c2;" rowspan="2">Cash</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;" colspan="2">Credit</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;" colspan="2">Konsinyasi</th>
                        </tr>
                        <tr>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Belum terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#7e57c2;">Belum terbayar</th>
                            <th class="text-center text-white" style="background-color:#673ab7;">Sudah Terbayar</th>
                            <th class="text-center text-white" style="background-color:#673ab7;">Belum terbayar</th>
                        </tr>
                    </thead>
                    <tbody>';
        $pembelian = array();

        $new_total_pembelian = 0;
        $new_total_pembelian_cash = 0;
        $new_total_pembelian_credit_terbayar = 0;
        $new_total_pembelian_credit_blm_terbayar = 0;
        $new_total_pembelian_konsinyasi_terbayar = 0;
        $new_total_pembelian_konsinyasi_blm_terbayar = 0;
        $new_total_pembelian_jatuhtempo_terbayar = 0;
        $new_total_pembelian_jetuhtempo_blm_terbayar = 0;

        $supliers = MasterSuplier::where('is_deleted', 0)->get();
        foreach ($supliers as $key => $val) {
            $detail_pembelian = DB::table('tb_detail_nota_pembelian')
                        ->select(
                                DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                'b.diskon1',
                                'b.diskon2',
                                'b.ppn')
                        ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                        ->whereDate('b.tgl_nota','>=', $tgl_awal)
                        ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                        ->where('b.id_apotek_nota','=',$id_apotek)
                        ->where('b.id_suplier','=',$val->id)
                        ->where('b.is_deleted', 0)
                        ->first();

            if($detail_pembelian->total != 0) {
                $detail_pembelian_cash = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',1)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_credit = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_konsinyasi = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',3)
                            ->where('b.is_deleted', 0)
                            ->first();

                $detail_pembelian_credit_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();


                $detail_pembelian_konsinyasi_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',3)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();

                $detail_pembelian_jatuh_tempo_blm_bayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->whereDate('b.tgl_jatuh_tempo','>=', $tgl_awal)
                            ->whereDate('b.tgl_jatuh_tempo','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 0)
                            ->first();

                $detail_pembelian_jatuh_tempo_terbayar = DB::table('tb_detail_nota_pembelian')
                            ->select(
                                    DB::raw('SUM(tb_detail_nota_pembelian.diskon) AS total_diskon'),
                                    DB::raw('SUM((tb_detail_nota_pembelian.diskon_persen/100) * tb_detail_nota_pembelian.total_harga) AS total_diskon_persen'),
                                    DB::raw('SUM(tb_detail_nota_pembelian.total_harga) AS total'),
                                    'b.diskon1',
                                    'b.diskon2',
                                    'b.ppn')
                            ->join('tb_nota_pembelian as b','b.id','=','tb_detail_nota_pembelian.id_nota')
                            ->whereDate('b.tgl_nota','>=', $tgl_awal)
                            ->whereDate('b.tgl_nota','<=', $tgl_akhir)
                            ->whereDate('b.tgl_jatuh_tempo','>=', $tgl_awal)
                            ->whereDate('b.tgl_jatuh_tempo','<=', $tgl_akhir)
                            ->where('b.id_apotek_nota','=',$id_apotek)
                            ->where('b.id_suplier','=',$val->id)
                            ->where('b.id_jenis_pembelian','=',2)
                            ->where('b.is_deleted', 0)
                            ->where('b.is_lunas', 1)
                            ->first();

                $total_pembelian1 = $detail_pembelian->total-($detail_pembelian->diskon1+$detail_pembelian->diskon2);
                $total_pembelian = $total_pembelian1 + ($total_pembelian1 * $detail_pembelian->ppn/100);

                $total_pembelian_cash1 = $detail_pembelian_cash->total-($detail_pembelian_cash->diskon1+$detail_pembelian_cash->diskon2);
                $total_pembelian_cash = $total_pembelian_cash1 + ($total_pembelian_cash1 * $detail_pembelian_cash->ppn/100);

                $total_pembelian_credit_terbayar1 = $detail_pembelian_credit_terbayar->total-($detail_pembelian_credit_terbayar->diskon1+$detail_pembelian_credit_terbayar->diskon2);
                $total_pembelian_credit_terbayar = $total_pembelian_credit_terbayar1 + ($total_pembelian_credit_terbayar1 * $detail_pembelian_credit_terbayar->ppn/100);

                $total_pembelian_credit_blm_terbayar1 = $detail_pembelian_credit->total-($detail_pembelian_credit->diskon1+$detail_pembelian_credit->diskon2);
                $total_pembelian_credit_blm_terbayar = $total_pembelian_credit_blm_terbayar1 + ($total_pembelian_credit_blm_terbayar1 * $detail_pembelian_credit->ppn/100);
                $total_pembelian_credit_blm_terbayar = $total_pembelian_credit_blm_terbayar - $total_pembelian_credit_terbayar;

                $total_pembelian_konsinyasi_terbayar1 = $detail_pembelian_konsinyasi_terbayar->total-($detail_pembelian_konsinyasi_terbayar->diskon1+$detail_pembelian_konsinyasi_terbayar->diskon2);
                $total_pembelian_konsinyasi_terbayar = $total_pembelian_konsinyasi_terbayar1 + ($total_pembelian_konsinyasi_terbayar1 * $detail_pembelian_konsinyasi_terbayar->ppn/100);

                $total_pembelian_konsinyasi_blm_terbayar1 = $detail_pembelian_konsinyasi->total-($detail_pembelian_konsinyasi->diskon1+$detail_pembelian_konsinyasi->diskon2);
                $total_pembelian_konsinyasi_blm_terbayar2 = $total_pembelian_konsinyasi_blm_terbayar1 + ($total_pembelian_konsinyasi_blm_terbayar1 * $detail_pembelian_konsinyasi->ppn/100);
                $total_pembelian_konsinyasi_blm_terbayar3 = $detail_pembelian_konsinyasi_terbayar->total-($detail_pembelian_konsinyasi_terbayar->diskon1+$detail_pembelian_konsinyasi_terbayar->diskon2);
                $total_pembelian_konsinyasi_blm_terbayar4 = $total_pembelian_konsinyasi_blm_terbayar3 + ($total_pembelian_konsinyasi_blm_terbayar3 * $detail_pembelian_konsinyasi_terbayar->ppn/100);
                $total_pembelian_konsinyasi_blm_terbayar = $total_pembelian_konsinyasi_blm_terbayar2 - $total_pembelian_konsinyasi_blm_terbayar4;

                $total_pembelian_jatuhtempo_terbayar1 = $detail_pembelian_jatuh_tempo_terbayar->total-($detail_pembelian_jatuh_tempo_terbayar->diskon1+$detail_pembelian_jatuh_tempo_terbayar->diskon2);
                $total_pembelian_jatuhtempo_terbayar = $total_pembelian_jatuhtempo_terbayar1 + ($total_pembelian_jatuhtempo_terbayar1 * $detail_pembelian_jatuh_tempo_terbayar->ppn/100);

                $total_pembelian_jetuhtempo_blm_terbayar1 = $detail_pembelian_jatuh_tempo_blm_bayar->total-($detail_pembelian_jatuh_tempo_blm_bayar->diskon1+$detail_pembelian_jatuh_tempo_blm_bayar->diskon2);
                $total_pembelian_jetuhtempo_blm_terbayar = $total_pembelian_jetuhtempo_blm_terbayar1 + ($total_pembelian_jetuhtempo_blm_terbayar1 * $detail_pembelian_jatuh_tempo_blm_bayar->ppn/100);

                $new_total_pembelian = $new_total_pembelian+$detail_pembelian->total;
                $new_total_pembelian_cash = $new_total_pembelian_cash+$detail_pembelian_cash->total;
                $new_total_pembelian_credit_terbayar = $new_total_pembelian_credit_terbayar+$detail_pembelian_credit_terbayar->total;
                $new_total_pembelian_credit_blm_terbayar = $new_total_pembelian_credit_blm_terbayar+$detail_pembelian_credit->total - $detail_pembelian_credit_terbayar->total;
                $new_total_pembelian_konsinyasi_terbayar = $new_total_pembelian_konsinyasi_terbayar+$detail_pembelian_konsinyasi_terbayar->total;
                $new_total_pembelian_konsinyasi_blm_terbayar = $new_total_pembelian_konsinyasi_blm_terbayar+$detail_pembelian_konsinyasi->total - $detail_pembelian_konsinyasi_terbayar->total;
                $new_total_pembelian_jatuhtempo_terbayar = $new_total_pembelian_jatuhtempo_terbayar+$detail_pembelian_jatuh_tempo_terbayar->total;
                $new_total_pembelian_jetuhtempo_blm_terbayar = $new_total_pembelian_jetuhtempo_blm_terbayar+$detail_pembelian_jatuh_tempo_blm_bayar->total;

                $total_pembelian_format = number_format($total_pembelian,0,',',',');
                $total_pembelian_cash_format = number_format($total_pembelian_cash,0,',',',');
                $total_pembelian_credit_terbayar_format = number_format($total_pembelian_credit_terbayar,0,',',',');
                $total_pembelian_credit_blm_terbayar_format = number_format($total_pembelian_credit_blm_terbayar,0,',',',');
                $total_pembelian_konsinyasi_terbayar_format = number_format($total_pembelian_konsinyasi_terbayar,0,',',',');
                $total_pembelian_konsinyasi_blm_terbayar_format = number_format($total_pembelian_konsinyasi_blm_terbayar,0,',',',');
                $total_pembelian_jatuhtempo_terbayar_format = number_format($total_pembelian_jatuhtempo_terbayar,0,',',',');
                $total_pembelian_jetuhtempo_blm_terbayar_format = number_format($total_pembelian_jetuhtempo_blm_terbayar,0,',',',');

                $new_data = array();
                $new_data['suplier'] = $val->nama;
                $new_data['total'] = $total_pembelian;
                $new_data['total_pembelian'] = 'Rp '.$total_pembelian_format;
                $new_data['total_pembelian_cash'] = 'Rp '.$total_pembelian_cash_format;
                $new_data['total_pembelian_credit_terbayar'] = 'Rp '.$total_pembelian_credit_terbayar_format;
                $new_data['total_pembelian_credit_blm_terbayar'] = 'Rp '.$total_pembelian_credit_blm_terbayar_format;
                $new_data['total_pembelian_konsinyasi_terbayar'] = 'Rp '.$total_pembelian_konsinyasi_terbayar_format;
                $new_data['total_pembelian_konsinyasi_blm_terbayar'] = 'Rp '.$total_pembelian_konsinyasi_blm_terbayar_format;
                $new_data['total_pembelian_jatuhtempo_terbayar'] = 'Rp '.$total_pembelian_jatuhtempo_terbayar_format;
                $new_data['total_pembelian_jetuhtempo_blm_terbayar'] = 'Rp '.$total_pembelian_jetuhtempo_blm_terbayar_format;
                $pembelian[] = $new_data;

            } 
        }

        foreach ($pembelian as $key => $obj) {
            $data.= '<tr>
                            <td class="text-left">'.$obj['suplier'].'</td>
                            <td class="text-right">'.$obj['total_pembelian'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_cash'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_credit_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_credit_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_konsinyasi_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_konsinyasi_blm_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_jatuhtempo_terbayar'].'</td>
                            <td class="text-right">'.$obj['total_pembelian_jetuhtempo_blm_terbayar'].'</td>
                        </tr>';
        }

        if(count($pembelian) == 0) {
            $data.= '<tr>
                            <td class="text-center" colspan="9">TIDAK ADA PEMBELIAN</td>
                        </tr>';
        }

        $new_total_pembelian_format = number_format($new_total_pembelian,0,',',',');
        $new_total_pembelian_cash_format = number_format($new_total_pembelian_cash,0,',',',');
        $new_total_pembelian_credit_terbayar_format = number_format($new_total_pembelian_credit_terbayar,0,',',',');
        $new_total_pembelian_credit_blm_terbayar_format = number_format($new_total_pembelian_credit_blm_terbayar,0,',',',');
        $new_total_pembelian_konsinyasi_terbayar_format = number_format($new_total_pembelian_konsinyasi_terbayar,0,',',',');
        $new_total_pembelian_konsinyasi_blm_terbayar_format = number_format($new_total_pembelian_konsinyasi_blm_terbayar,0,',',',');
        $new_total_pembelian_jatuhtempo_terbayar_format = number_format($new_total_pembelian_jatuhtempo_terbayar,0,',',',');
        $new_total_pembelian_jetuhtempo_blm_terbayar_format = number_format($new_total_pembelian_jetuhtempo_blm_terbayar,0,',',',');

        $data .= '<tr>
                    <td class="text-left"><b>TOTAL</b></td>
                    <td class="text-right text-white" style="background-color:#9575cd;">Rp '.$new_total_pembelian_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_cash_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_credit_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_credit_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_konsinyasi_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#7e57c2;">Rp '.$new_total_pembelian_konsinyasi_blm_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#673ab7;">Rp '.$new_total_pembelian_jatuhtempo_terbayar_format.'</td>
                    <td class="text-right text-white" style="background-color:#673ab7;">Rp '.$new_total_pembelian_jetuhtempo_blm_terbayar_format.'</td>
                </tr>';

        $data .= '</tbody></table>';
        echo $data;
    }

    public function page_not_authorized()
    {
        return view('page_not_authorized');
    }

    public function page_not_found()
    {
        return view('page_not_found');
    }

    public function set_active_apotek($id_apotek){
        $apotek = MasterApotek::where('id', '=', $id_apotek)->first();

        if(!is_null($apotek)){
            session(['nama_apotek_singkat_active'=>strtolower($apotek->nama_singkat)]);
            session(['nama_apotek_panjang_active'=>$apotek->nama_panjang]);
            session(['nama_apotek_active'=>$apotek->nama_singkat]);
            session(['id_apotek_active'=>$apotek->id]);
            session()->flash('success', 'Sukses melakukan perubahan apotek menjadi '.$apotek->nama_singkat.'!');
        }else{
            session()->flash('error', 'Gagal melakukan perubahan apotek menjadi '.$apotek->nama_singkat.'!. Apotek tidak dapat ditemukan.');
        }
        return redirect()->intended('/home');
    }

    public function set_active_role($id_role){
        $user_role = RbacUserRole::join('rbac_roles', 'rbac_roles.id', '=', 'rbac_user_role.id_role')
                                ->where('rbac_user_role.id_user', '=', Auth::user()->id)
                                ->where('rbac_roles.id', '=', $id_role)
                                ->first();

        if(!empty($user_role)){
            session(['nama_role_active'=>$user_role->nama]);
            session(['id_role_active'=>$user_role->id]);

            $menus = array();

            $role_permissions = RbacRolePermission::where("id_role", $user_role->id)->get();
            foreach ($role_permissions as $role_permission) {
                $permission = RbacPermission::find($role_permission->id_permission);
                $menus[] = $permission->id_menu;
            }


            $menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $menus)->orderBy('weight')->get();
            $parents = array();
            foreach ($menu as $key => $val) {
                if($val->parent == 0) {
                    $data_parent = RbacMenu::find($val->id);
                    $parents[] = $data_parent->id;
                } else {
                    $data_parent = RbacMenu::find($val->parent);
                    $parents[] = $data_parent->id;
                }
               
            }

            $parent_menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $parents)->orderBy('weight')->get();

            foreach ($parent_menu as $key => $obj) {
                $sub_menu = array(); 
                if ($obj->link == "#") {
                    foreach ($menu as $key => $val) {
                        if($val->parent == $obj->id) {
                            $sub_menu[] = $val;
                        }
                    }
                    $obj->link == "#";
                    $obj->submenu = $sub_menu;
                    $obj->ada_sub = 1;
                    
                } else {
                    $obj->submenu = "";
                    $obj->ada_sub = 0;
                }
            }
            

            session(['menu' => $parent_menu]);
            session()->flash('success', 'Sukses melakukan perubahan role menjadi dan dengan menu '.$user_role->nama);
        }else{
            session()->flash('error', 'Gagal melakukan perubahan role menjadi '.$user_role->nama.'. Anda tidak memiliki role tersebut.');
        }

        return redirect()->intended('/home');
    }

    public function set_active_tahun($tahun){
        $tahun = MasterTahun::where('tahun', '=', $tahun)->first();

        if(!is_null($tahun)){
            session(['id_tahun_active'=>$tahun->tahun]);
            session()->flash('success', 'Sukses melakukan perubahan tahun menjadi '.$tahun->tahun.'!');
        }else{
            session()->flash('error', 'Gagal melakukan perubahan tahun menjadi '.$tahun->tahun.'!. Tahun tidak dapat ditemukan.');
        }
        return redirect()->intended('/home');
    }


    public function send_email() {
   
        $details = [
            'title' => 'Mail from ItSolutionStuff.com',
            'body' => 'This is for testing email using smtp'
        ];
       
        \Mail::to('sriutami821@gmail.com')->send(new \App\Mail\MailPenjualanRetur($details));
       
        dd("Email is Sent.");
    }

    public function load_grafik(Request $request) {
        $data = array();
        $app = app();
        $data_ = $app->make('stdClass');
        $tahun = session('id_tahun_active');
        $currentMonth = date('m');

        /*$startMonth = date('1');
        $endtMonth = date('12');
        for($m=$endtMonth ; $m<=$currentMonth; ++$m){
            $months[$m] = strftime('%B', mktime(0, 0, 0, $m, 1));
            echo $months[$m]."<br>";
        }*/

        $label_ = array();
        $values_ = array();
        $all_ = array();
        $values_pembelian_ = array();
        $values_to_masuk_ = array();
        $values_to_keluar_ = array();
        for($i=1;$i<=$currentMonth;$i++){
            $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1));
            array_push($label_, $months[$i]);

            $rekaps = TransaksiPenjualanClosing::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_closing_nota_penjualan.*'])
                                ->where(function($query) use($request, $tahun, $i){
                                    $query->where('tb_closing_nota_penjualan.id_apotek_nota','=',session('id_apotek_active'));
                                    $query->whereYear('tb_closing_nota_penjualan.tanggal', $tahun);
                                    $query->whereMonth('tb_closing_nota_penjualan.tanggal', $i);
                                })
                                ->orderBy('tb_closing_nota_penjualan.id', 'asc')
                                ->get();

            $rekap_alls = TransaksiPenjualanClosing::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_closing_nota_penjualan.*'])
                                ->where(function($query) use($request, $tahun, $i){
                                    $query->whereYear('tb_closing_nota_penjualan.tanggal', $tahun);
                                    $query->whereMonth('tb_closing_nota_penjualan.tanggal', $i);
                                })
                                ->orderBy('tb_closing_nota_penjualan.id', 'asc')
                                ->get();

            $rekap_pembelian = TransaksiPembelianDetail::select([
                                        DB::raw('@rownum  := @rownum  + 1 AS no'), 
                                        DB::raw('CAST(SUM(tb_detail_nota_pembelian.total_harga) as decimal(20,2)) as total_pembelian')
                                ])
                                ->join('tb_nota_pembelian as a', 'a.id', '=', 'tb_detail_nota_pembelian.id_nota')
                                ->where(function($query) use($request, $tahun, $i){
                                    $query->where('a.is_deleted', 0);
                                    $query->where('a.id_apotek_nota','=',session('id_apotek_active'));
                                    $query->whereYear('a.tgl_nota', $tahun);
                                    $query->whereMonth('a.tgl_nota', $i);
                                })
                                ->first();

            $to_masuk = TransaksiTODetail::select([
                                        DB::raw('@rownum  := @rownum  + 1 AS no'), 
                                        DB::raw('CAST(SUM(tb_detail_nota_transfer_outlet.total) as decimal(20,2)) as total_to_masuk')
                                ])
                                ->join('tb_nota_transfer_outlet as a', 'a.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where(function($query) use($request, $tahun, $i){
                                    $query->where('a.is_deleted', 0);
                                    $query->where('a.id_apotek_tujuan','=',session('id_apotek_active'));
                                    $query->whereYear('a.tgl_nota', $tahun);
                                    $query->whereMonth('a.tgl_nota', $i);
                                })
                                ->first();

            $to_keluar = TransaksiTODetail::select([
                                        DB::raw('@rownum  := @rownum  + 1 AS no'), 
                                        DB::raw('CAST(SUM(tb_detail_nota_transfer_outlet.total) as decimal(20,2)) as total_to_keluar')
                                ])
                                ->join('tb_nota_transfer_outlet as a', 'a.id', '=', 'tb_detail_nota_transfer_outlet.id_nota')
                                ->where(function($query) use($request, $tahun, $i){
                                    $query->where('a.is_deleted', 0);
                                    $query->where('a.id_apotek_nota','=',session('id_apotek_active'));
                                    $query->whereYear('a.tgl_nota', $tahun);
                                    $query->whereMonth('a.tgl_nota', $i);
                                })
                                ->first();


            $total_excel=0;
            foreach($rekaps as $rekap) {
                $total_1 = $rekap->jumlah_penjualan;
                if($total_1 == 0) {
                    $total_1 = $rekap->total_penjualan+$rekap->total_diskon;
                }

                $total_3 = $total_1-$rekap->total_diskon;
                $grand_total = $total_3+$rekap->total_jasa_dokter+$rekap->total_jasa_resep+$rekap->total_paket_wd+$rekap->total_lab+$rekap->total_apd;

                $total_2 = $grand_total-$rekap->total_penjualan_cn;
               // $total_debet_x = $rekap->total_debet-$rekap->total_penjualan_cn_debet;
               // $total_cash_x = $rekap->uang_seharusnya-$rekap->total_penjualan_cn_cash;
                //$new_total = $rekap->total_akhir+$rekap->total_penjualan_kredit_terbayar;
                $new_total = $total_2+$rekap->total_penjualan_kredit;

                if($tahun == 2020) {
                    $total_excel = $total_excel+$total_1;
                } else {
                    $total_excel = $total_excel+$new_total;
                }
            }
            $total_excel = $total_excel;

            $total_all=0;
            foreach($rekap_alls as $rekap) {
                $total_1 = $rekap->jumlah_penjualan;
                if($total_1 == 0) {
                    $total_1 = $rekap->total_penjualan+$rekap->total_diskon;
                }
                $total_3 = $total_1-$rekap->total_diskon;
                $grand_total = $total_3+$rekap->total_jasa_dokter+$rekap->total_jasa_resep+$rekap->total_paket_wd+$rekap->total_lab+$rekap->total_apd;

                $total_2 = $grand_total-$rekap->total_penjualan_cn;
               // $total_debet_x = $rekap->total_debet-$rekap->total_penjualan_cn_debet;
               // $total_cash_x = $rekap->uang_seharusnya-$rekap->total_penjualan_cn_cash;
                $new_total = $total_2+$rekap->total_penjualan_kredit;
                $new_total_x = $new_total/5;

                if($tahun == 2020) {
                    $new_total_x = $total_1/5;
                    $total_all = $total_all+$new_total_x;
                } else {
                    $total_all = $total_all+$new_total_x;
                }
            }
            $total_all = $total_all;
            $total_pembelian = $rekap_pembelian->total_pembelian;
            $total_to_masuk = $to_masuk->total_to_masuk;
            $total_to_keluar = $to_keluar->total_to_keluar;
            array_push($values_, $total_excel);
            array_push($values_pembelian_, $total_pembelian);
            array_push($values_to_masuk_, $total_to_masuk);
            array_push($values_to_keluar_, $total_to_keluar);
            array_push($all_, $total_all);
        }

        //$penjualan = TransaksiPenjualanClosing::where('')
        $penjualan = $app->make('stdClass');
        $penjualan->label = $label_;//array('January', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli');
        $penjualan->values = $values_;//array(10,20,30,40,50,60,70);
        $penjualan->all = $all_;
        $data_->penjualan = $penjualan;

        $pembelian = $app->make('stdClass');
        $pembelian->values = $values_pembelian_;
        $data_->pembelian = $pembelian;


        $transfer_masuk = $app->make('stdClass');
        $transfer_masuk->values = $values_to_masuk_;
        $data_->transfer_masuk = $transfer_masuk;

        $transfer_keluar = $app->make('stdClass');
        $transfer_keluar->values = $values_to_keluar_;
        $data_->transfer_keluar = $transfer_keluar;

        return response()->json($data_);
    }
}

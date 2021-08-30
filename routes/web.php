<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('penjualan/cetak_tes/{id}', ['as'=>'penjualan.cetak_tes', 'uses'=>'T_PenjualanController@cetak_tes']);
Route::get('page_not_authorized', ['as'=>'page_not_authorized', 'uses'=>'HomeController@page_not_authorized']);
Route::get('page_not_found', ['as'=>'page_not_found', 'uses'=>'HomeController@page_not_found']);
Route::resource('tes', 'TesController');

Route::get('send-mail', function () {
   
	    $details = [
	        'title' => 'Mail from ItSolutionStuff.com',
	        'body' => 'This is for testing email using smtp'
	    ];
	   
	   /* \Mail::to('sriutami821@gmail.com')->send(new \App\Mail\MailPenjualanRetur($details));*/
	   
	   return view('emails._retur_penjualan')->with(compact('details'));
	    //dd("Email is Sent.");
	});

Auth::routes();
Route::get('login_admin', ['as'=>'login_admin', 'uses'=>'Auth\LoginController@login_admin']);
Route::post('login_admin_post', ['as'=>'login_admin_post', 'uses'=>'Auth\LoginController@login_admin_check']);
Route::get('login_outlet', ['as'=>'login_outlet', 'uses'=>'Auth\LoginController@login_outlet']);
Route::post('login_outlet_post', ['as'=>'login_outlet_post', 'uses'=>'Auth\LoginController@login_outlet_check']);
Route::group(['middleware' => 'cekakses', 'auth'], function () {
	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('home/load_grafik', ['as'=>'home/load_grafik', 'uses'=>'HomeController@load_grafik']);
	Route::get('set_active_apotek/{id}', ['as'=>'set_active_apotek/{id}', 'uses'=>'HomeController@set_active_apotek']);
	Route::get('set_active_role/{id}', ['as'=>'set_active_role/{id}', 'uses'=>'HomeController@set_active_role']);
	Route::get('set_active_tahun/{id}', ['as'=>'set_active_tahun/{id}', 'uses'=>'HomeController@set_active_tahun']);
	Route::get('recap_all', ['as'=>'recap_all', 'uses'=>'HomeController@recap_all']);
	Route::get('recap_all_load_view', ['as'=>'recap_all_load_view', 'uses'=>'HomeController@recap_all_load_view']);
	Route::get('recap_all_pembelian_load_view', ['as'=>'recap_all_pembelian_load_view', 'uses'=>'HomeController@recap_all_pembelian_load_view']);
	Route::get('recap_all_to_load_view', ['as'=>'recap_all_to_load_view', 'uses'=>'HomeController@recap_all_to_load_view']);
	Route::get('recap_perhari', ['as'=>'recap_perhari', 'uses'=>'HomeController@recap_perhari']);
	Route::get('recap_perhari_load_view', ['as'=>'recap_perhari_load_view', 'uses'=>'HomeController@recap_perhari_load_view']);
	Route::get('recap_perhari_pembelian_load_view', ['as'=>'recap_perhari_pembelian_load_view', 'uses'=>'HomeController@recap_perhari_pembelian_load_view']);
	Route::get('recap_perhari_to_load_view', ['as'=>'recap_perhari_to_load_view', 'uses'=>'HomeController@recap_perhari_to_load_view']);

	
	
	# login as 
	Route::get('loginas', ['as'=>'loginas', 'uses'=>'LoginAsController@index']);
	Route::post('loginas/login', ['as'=>'loginas/login', 'uses'=>'LoginAsController@login']);
	# end login as 

	/*=============================================================================================*/ 
	/*=========================================== RBAC ============================================*/
	/*=============================================================================================*/ 
	/* rbac menu */
	Route::get('menu/list_menu', ['as'=>'menu.list_menu', 'uses'=>'MenuController@list_menu']);
	Route::post('menu/update_sorting_menu', ['as'=>'menu.update_sorting_menu', 'uses'=>'MenuController@update_sorting_menu']);
	Route::resource('menu', 'MenuController');

	/* rbac permission */
	Route::get('permission/list_permission', ['as'=>'permission.list_permission', 'uses'=>'PermissionController@list_permission']);
	Route::get('permission/reload_permission', ['as'=>'permission.reload_permission', 'uses'=>'PermissionController@reload_permission']);
	Route::resource('permission', 'PermissionController');

	/* rbac role */
	Route::get('role/list_role', ['as'=>'role.list_role', 'uses'=>'RoleController@list_role']);
	Route::resource('role', 'RoleController');

	/* rbac admin admin */
	Route::get('profile', ['as'=>'profile', 'uses'=>'UserController@profile']);
	Route::get('admin/list_calon_user', ['as'=>'admin.list_calon_user', 'uses'=>'UserController@list_calon_user']);
	Route::get('admin/list_user', ['as'=>'admin.list_user', 'uses'=>'UserController@list_user']);
	Route::get('admin/list_unit', ['as'=>'admin.list_unit', 'uses'=>'UserController@list_unit']);
	Route::get('admin/list_sunit', ['as'=>'admin.list_sunit', 'uses'=>'UserController@list_sunit']);
	Route::get('admin/setting_role_akses/{id}', ['as'=>'admin.setting_role_akses/{id}', 'uses'=>'UserController@setting_role_akses']);
	Route::get('admin/setting_apotek_akses/{id}', ['as'=>'admin.setting_apotek_akses/{id}', 'uses'=>'UserController@setting_apotek_akses']);
	Route::put('admin/update_roles_akses/{id}', ['as'=>'admin.update_roles_akses','uses'=>'UserController@update_roles_akses']);
	Route::put('admin/update_apotek_akses/{id}', ['as'=>'admin.update_apotek_akses','uses'=>'UserController@update_apotek_akses']);
	Route::put('admin/update_profile/{id}', ['as'=>'admin.update_profile', 'uses'=>'UserController@update_profile']);
	Route::resource('admin', 'UserController');
	Route::post('admin/add_row_role', ['as'=>'admin.add_row_role','uses'=>'UserController@add_row_role']);
	/*=========================================== END =============================================*/ 

	/*=============================================================================================*/ 
	/*======================================== DATA MASTER ========================================*/
	/*=============================================================================================*/
	Route::get('user/list_data_user', ['as'=>'user.list_data_user', 'uses'=>'M_UserController@list_data_user']);
	Route::resource('user', 'M_UserController');

	Route::get('apotek/list_apotek', ['as'=>'apotek.list_apotek', 'uses'=>'M_ApotekController@list_apotek']);
	Route::resource('apotek', 'M_ApotekController');

	Route::get('suplier/list_suplier', ['as'=>'suplier.list_suplier', 'uses'=>'M_SuplierController@list_suplier']);
	Route::resource('suplier', 'M_SuplierController');

	Route::get('produsen/list_produsen', ['as'=>'produsen.list_produsen', 'uses'=>'M_ProdusenController@list_produsen']);
	Route::resource('produsen', 'M_ProdusenController');
	
	Route::get('obat/list_obat', ['as'=>'obat.list_obat', 'uses'=>'M_ObatController@list_obat']);
	Route::get('obat/kenaikan_harga', ['as'=>'obat.kenaikan_harga', 'uses'=>'M_ObatController@kenaikan_harga']);
	Route::get('obat/export_data', ['as'=>'obat.export_data', 'uses'=>'M_ObatController@export_data']);
	Route::get('obat/sync_obat_outlet/{id}', ['as'=>'obat.sync_obat_outlet', 'uses'=>'M_ObatController@sync_obat_outlet']);
	Route::resource('obat', 'M_ObatController');
	Route::get('obat/sync_harga_per_item', ['as'=>'obat.sync_harga_per_item', 'uses'=>'M_ObatController@sync_harga_per_item']);
	Route::post('obat/list_kenaikan_harga', ['as'=>'obat.list_kenaikan_harga', 'uses'=>'M_ObatController@list_kenaikan_harga']);
	Route::post('obat/setting_harga_jual', ['as'=>'obat.setting_harga_jual', 'uses'=>'M_ObatController@setting_harga_jual']);
	Route::put('obat/update_harga/{id}', ['as'=>'obat.update_harga', 'uses'=>'M_ObatController@update_harga']);
	
	Route::get('klinik/list_klinik', ['as'=>'klinik.list_klinik', 'uses'=>'M_KlinikController@list_klinik']);
	Route::resource('klinik', 'M_KlinikController');

	Route::get('kabupaten/list_kabupaten', ['as'=>'kabupaten.list_kabupaten', 'uses'=>'M_KabupatenController@list_kabupaten']);
	Route::resource('kabupaten', 'M_KabupatenController');

	Route::get('provinsi/list_provinsi', ['as'=>'provinsi.list_provinsi', 'uses'=>'M_ProvinsiController@list_provinsi']);
	Route::resource('provinsi', 'M_ProvinsiController');

	Route::get('golongan_obat/list_golongan_obat', ['as'=>'golongan_obat.list_golongan_obat', 'uses'=>'M_GolonganObatController@list_golongan_obat']);
	Route::resource('golongan_obat', 'M_GolonganObatController');

	Route::get('kategori_kehamilan/list_kategori_kehamilan', ['as'=>'kategori_kehamilan.list_kategori_kehamilan', 'uses'=>'M_KategoriKehamilanController@list_kategori_kehamilan']);
	Route::resource('kategori_kehamilan', 'M_KategoriKehamilanController');

	Route::get('jenis_pembelian/list_jenis_pembelian', ['as'=>'jenis_pembelian.list_jenis_pembelian', 'uses'=>'M_JenisPembelianController@list_jenis_pembelian']);
	Route::resource('jenis_pembelian', 'M_JenisPembelianController');

	Route::get('jenis_pembayaran/list_jenis_pembayaran', ['as'=>'jenis_pembayaran.list_jenis_pembayaran', 'uses'=>'M_JenisPembayaranController@list_jenis_pembayaran']);
	Route::resource('jenis_pembayaran', 'M_JenisPembayaranController');

	Route::get('agama/list_agama', ['as'=>'agama.list_agama', 'uses'=>'M_AgamaController@list_agama']);
	Route::resource('agama', 'M_AgamaController');

	Route::get('golongan_darah/list_golongan_darah', ['as'=>'golongan_darah.list_golongan_darah', 'uses'=>'M_GolonganDarahController@list_golongan_darah']);
	Route::resource('golongan_darah', 'M_GolonganDarahController');

	Route::get('kewarganegaraan/list_kewarganegaraan', ['as'=>'kewarganegaraan.list_kewarganegaraan', 'uses'=>'M_KewarganegaraanController@list_kewarganegaraan']);
	Route::resource('kewarganegaraan', 'M_KewarganegaraanController');

	Route::get('jenis_kelamin/list_jenis_kelamin', ['as'=>'jenis_kelamin.list_jenis_kelamin', 'uses'=>'M_JenisKelaminController@list_jenis_kelamin']);
	Route::resource('jenis_kelamin', 'M_JenisKelaminController');

	Route::get('satuan/list_satuan', ['as'=>'satuan.list_satuan', 'uses'=>'M_SatuanController@list_satuan']);
	Route::resource('satuan', 'M_SatuanController');

	Route::get('group_apotek/list_group_apotek', ['as'=>'group_apotek.list_group_apotek', 'uses'=>'M_GroupApotekController@list_group_apotek']);
	Route::resource('group_apotek', 'M_GroupApotekController');

	Route::get('penandaan_obat/list_penandaan_obat', ['as'=>'penandaan_obat.list_penandaan_obat', 'uses'=>'M_PenandaanObatController@list_penandaan_obat']);
	Route::resource('penandaan_obat', 'M_PenandaanObatController');

	Route::get('tes_create_table', ['as'=>'tes_create_table', 'uses'=>'HomeController@tes_create_table']);
	Route::post('apotek/sync_data_stok_harga', ['as'=>'apotek.sync_data_stok_harga', 'uses'=>'M_ApotekController@sync_data_stok_harga']);
	Route::post('apotek/add_table_stok_harga', ['as'=>'apotek.add_table_stok_harga', 'uses'=>'M_ApotekController@add_table_stok_harga']);

	Route::get('kode_akuntansi/list_kode_akuntansi', ['as'=>'kode_akuntansi.list_kode_akuntansi', 'uses'=>'M_KodeAkunController@list_kode_akuntansi']);
	Route::resource('kode_akuntansi', 'M_KodeAkunController');

	Route::get('sub_kode_akuntansi/list_sub_kode_akuntansi', ['as'=>'sub_kode_akuntansi.list_sub_kode_akuntansi', 'uses'=>'M_KodeAkunSubController@list_sub_kode_akuntansi']);
	Route::resource('sub_kode_akuntansi', 'M_KodeAkunSubController');

	Route::get('dokter/list_dokter', ['as'=>'dokter.list_dokter', 'uses'=>'M_DokterController@list_dokter']);
	Route::resource('dokter', 'M_DokterController');

	Route::get('apoteker/list_apoteker', ['as'=>'apoteker.list_apoteker', 'uses'=>'M_ApotekerController@list_apoteker']);
	Route::resource('apoteker', 'M_ApotekerController');

	Route::get('member/list_member', ['as'=>'member.list_member', 'uses'=>'M_MemberController@list_member']);
	Route::resource('member', 'M_MemberController');

	Route::get('jasa_resep/list_jasa_resep', ['as'=>'jasa_resep.list_jasa_resep', 'uses'=>'M_JasaResepController@list_jasa_resep']);
	Route::resource('jasa_resep', 'M_JasaResepController');

	Route::get('jenis_kartu/list_jenis_kartu', ['as'=>'jenis_kartu.list_jenis_kartu', 'uses'=>'M_JenisKartuController@list_jenis_kartu']);
	Route::resource('jenis_kartu', 'M_JenisKartuController');

	Route::get('kartu/list_kartu', ['as'=>'kartu.list_kartu', 'uses'=>'M_KartuController@list_kartu']);
	Route::resource('kartu', 'M_KartuController');

	Route::get('paket_wd/list_data_paket_wd', ['as'=>'paket_wd.list_data_paket_wd', 'uses'=>'M_PaketWDController@list_data_paket_wd']);
	Route::resource('paket_wd', 'M_PaketWDController');

	Route::get('member_tipe/list_member_tipe', ['as'=>'member_tipe.list_member_tipe', 'uses'=>'M_MemberTipeController@list_member_tipe']);
	Route::resource('member_tipe', 'M_MemberTipeController');

	Route::get('jenis_promo/list_jenis_promo', ['as'=>'jenis_promo.list_jenis_promo', 'uses'=>'M_JenisPromoController@list_jenis_promo']);
	Route::resource('jenis_promo', 'M_JenisPromoController');
	/*=========================================== END =============================================*/ 


	/*=============================================================================================*/ 
	/*==================================== DATA SHOW NON MASTER ===================================*/
	/*=============================================================================================*/ 
	Route::get('data_obat/list_data_obat', ['as'=>'data_obat.list_data_obat', 'uses'=>'D_ObatController@list_data_obat']);
	Route::get('data_obat/reload_data_pembelian', ['as'=>'data_obat.reload_data_pembelian', 'uses'=>'D_ObatController@reload_data_pembelian']);
	Route::get('data_obat/reload_data_histori', ['as'=>'data_obat.reload_data_histori', 'uses'=>'D_ObatController@reload_data_histori']);
	Route::get('data_obat/sycn_harga_obat_all', ['as'=>'data_obat.sycn_harga_obat_all', 'uses'=>'D_ObatController@sycn_harga_obat_all']);
	Route::get('data_obat/sycn_harga_obat_tahap_satu/{id}', ['as'=>'data_obat.sycn_harga_obat_tahap_satu', 'uses'=>'D_ObatController@sycn_harga_obat_tahap_satu']);
	Route::get('data_obat/sycn_harga_obat_tahap_dua', ['as'=>'data_obat.sycn_harga_obat_tahap_dua', 'uses'=>'D_ObatController@sycn_harga_obat_tahap_dua']);
	Route::get('data_obat/stok_obat/{id}', ['as'=>'data_obat.stok_obat/{id}', 'uses'=>'D_ObatController@stok_obat']);
	Route::get('data_obat/histori_all/{id}', ['as'=>'data_obat.histori_all/{id}', 'uses'=>'D_ObatController@histori_all']);
	Route::get('data_obat/histori_harga/{id}', ['as'=>'data_obat.histori_harga/{id}', 'uses'=>'D_ObatController@histori_harga']);
	Route::get('data_obat/histori_harga_all/{id}', ['as'=>'data_obat.histori_harga_all/{id}', 'uses'=>'D_ObatController@histori_harga_all']);
	Route::get('data_obat/list_data_stok_obat', ['as'=>'data_obat.list_data_stok_obat', 'uses'=>'D_ObatController@list_data_stok_obat']); 
	Route::get('data_obat/list_data_histori_all', ['as'=>'data_obat.list_data_histori_all', 'uses'=>'D_ObatController@list_data_histori_all']);
	Route::get('data_obat/list_data_histori_harga', ['as'=>'data_obat.list_data_histori_harga', 'uses'=>'D_ObatController@list_data_histori_harga']);
	Route::get('data_obat/list_data_penyesuaian_stok_obat', ['as'=>'data_obat.list_data_penyesuaian_stok_obat', 'uses'=>'D_ObatController@list_data_penyesuaian_stok_obat']);
	Route::get('data_obat/export_data_obat', ['as'=>'data_obat.export_data_obat', 'uses'=>'D_ObatController@export_data_obat_stok']);
	Route::get('data_obat/penyesuaian_stok/{id}', ['as'=>'data_obat.penyesuaian_stok/{id}', 'uses'=>'D_ObatController@penyesuaian_stok']);
	Route::get('data_obat/export', ['as'=>'data_obat.export', 'uses'=>'D_ObatController@export']);
	Route::get('data_obat/persediaan', ['as'=>'data_obat.persediaan', 'uses'=>'D_ObatController@persediaan']);
	Route::get('data_obat/list_persediaan', ['as'=>'data_obat.list_persediaan', 'uses'=>'D_ObatController@list_persediaan']);
	Route::get('data_obat/export_persediaan', ['as'=>'data_obat.export_persediaan', 'uses'=>'D_ObatController@export_persediaan']);
	Route::get('data_obat/edit_harga_beli/{id}', ['as'=>'data_obat.edit_harga_beli/{id}', 'uses'=>'D_ObatController@edit_harga_beli']);
	Route::get('data_obat/list_edit_harga_beli', ['as'=>'data_obat.list_edit_harga_beli/{id}', 'uses'=>'D_ObatController@list_edit_harga_beli']);
	Route::get('data_obat/edit_harga_beli_ppn/{id}', ['as'=>'data_obat.edit_harga_beli_ppn/{id}', 'uses'=>'D_ObatController@edit_harga_beli_ppn']);
	Route::get('data_obat/list_edit_harga_beli_ppn', ['as'=>'data_obat.list_edit_harga_beli_ppnlist_edit_harga_beli_ppn/{id}', 'uses'=>'D_ObatController@list_edit_harga_beli_ppn']);
	Route::get('data_obat/edit_harga_jual/{id}', ['as'=>'data_obat.edit_harga_jual/{id}', 'uses'=>'D_ObatController@edit_harga_jual']);
	Route::get('data_obat/list_edit_harga_jual', ['as'=>'data_obat.list_edit_harga_jual/{id}', 'uses'=>'D_ObatController@list_edit_harga_jual']);
	Route::get('data_obat/gunakan_hb', ['as'=>'data_obat.gunakan_hb', 'uses'=>'D_ObatController@gunakan_hb']);
	Route::get('data_obat/gunakan_hb_ppn', ['as'=>'data_obat.gunakan_hb_ppn', 'uses'=>'D_ObatController@gunakan_hb_ppn']);
	Route::get('data_obat/gunakan_hj', ['as'=>'data_obat.gunakan_hj', 'uses'=>'D_ObatController@gunakan_hj']);
	Route::get('data_obat/reload_export_persediaan', ['as'=>'data_obat.reload_export_persediaan', 'uses'=>'D_ObatController@reload_export_persediaan']);
	Route::get('data_obat/clear_cache_persediaan', ['as'=>'data_obat.clear_cache_persediaan', 'uses'=>'D_ObatController@clear_cache_persediaan']);
	Route::get('data_obat/set_status_harga_outlet', ['as'=>'data_obat.set_status_harga_outlet', 'uses'=>'D_ObatController@set_status_harga_outlet']);
	Route::get('data_obat/perbaikan_data', ['as'=>'data_obat.perbaikan_data', 'uses'=>'D_ObatController@perbaikan_data']);
	Route::get('data_obat/reload_hpp_from_another_outlet', ['as'=>'data_obat.reload_hpp_from_another_outlet', 'uses'=>'D_ObatController@reload_hpp_from_another_outlet']);
	Route::resource('data_obat', 'D_ObatController');
	Route::post('data_obat/sycn_harga_obat', ['as'=>'data_obat.sycn_harga_obat', 'uses'=>'D_ObatController@sycn_harga_obat']);
	Route::post('data_obat/disabled_obat', ['as'=>'data_obat.disabled_obat', 'uses'=>'D_ObatController@disabled_obat']);
	Route::post('data_obat/import_data', ['as'=>'data_obat.import_data', 'uses'=>'D_ObatController@import_data']);
	Route::post('data_obat/import_obat_to_excel', ['as'=>'data_obat.import_obat_to_excel', 'uses'=>'D_ObatController@import_obat_to_excel']);

	Route::get('penyesuaian_stok/create/{id}', ['as'=>'penyesuaian_stok.create/{id}', 'uses'=>'PenyesuaianStokController@create']);
	Route::resource('penyesuaian_stok', 'PenyesuaianStokController');
	/*=========================================== END =============================================*/ 


	/*=============================================================================================*/ 
	/*===================================== DATA SETTING PROMO ====================================*/
	/*=============================================================================================*/ 
	Route::get('setting_promo/list_setting_promo', ['as'=>'setting_promo.list_setting_promo', 'uses'=>'SettingPromoController@list_setting_promo']);
	Route::get('setting_promo/list_data_obat', ['as'=>'setting_promo.list_data_obat', 'uses'=>'SettingPromoController@list_data_obat']);
	Route::resource('setting_promo', 'SettingPromoController');
	Route::post('setting_promo/add_row_item_beli', ['as'=>'setting_promo.add_row_item_beli', 'uses'=>'SettingPromoController@add_row_item_beli']);
	Route::post('setting_promo/add_row_item_diskon', ['as'=>'setting_promo.add_row_item_diskon', 'uses'=>'SettingPromoController@add_row_item_diskon']);
	Route::post('setting_promo/open_data_obat', ['as'=>'setting_promo.open_data_obat', 'uses'=>'SettingPromoController@open_data_obat']);
	/*=========================================== END =============================================*/ 


	/*=============================================================================================*/ 
	/*=================================== DEFECTA & PEMBELIAN =====================================*/
	/*=============================================================================================*/ 
	Route::get('defecta/input', ['as'=>'defecta.input', 'uses'=>'T_DefectaController@input']);
	Route::get('defecta/list_defecta_input', ['as'=>'defecta.list_defecta_input', 'uses'=>'T_DefectaController@list_defecta_input']);
	Route::get('defecta/list_defecta', ['as'=>'defecta.list_defecta', 'uses'=>'T_DefectaController@list_defecta']);
	Route::get('defecta/list_defecta_masuk', ['as'=>'defecta.list_defecta_masuk', 'uses'=>'T_DefectaController@list_defecta_masuk']);
	Route::get('defecta/hitung', ['as'=>'defecta.hitung', 'uses'=>'T_DefectaController@hitung']); 
	Route::get('defecta/data_masuk', ['as'=>'defecta.data_masuk', 'uses'=>'T_DefectaController@data_masuk']);
	Route::resource('defecta', 'T_DefectaController');
	Route::post('defecta/add_defecta', ['as'=>'defecta.add_defecta', 'uses'=>'T_DefectaController@add_defecta']);
	Route::post('defecta/send_defecta', ['as'=>'defecta.send_defecta', 'uses'=>'T_DefectaController@send_defecta']);
	Route::post('defecta/set_apotek_purchasing_aktif', ['as'=>'defecta.set_apotek_purchasing_aktif', 'uses'=>'T_DefectaController@set_apotek_purchasing_aktif']);
	Route::post('defecta/set_status_purchasing_aktif', ['as'=>'defecta.set_status_purchasing_aktif', 'uses'=>'T_DefectaController@set_status_purchasing_aktif']);
	Route::post('defecta/set_status_defecta', ['as'=>'defecta.set_status_defecta', 'uses'=>'T_DefectaController@set_status_defecta']);
	Route::post('defecta/konfirmasi_order', ['as'=>'defecta.konfirmasi_order','uses'=>'T_DefectaController@konfirmasi_order']);
	Route::post('defecta/konfirmasi_transfer', ['as'=>'defecta.konfirmasi_transfer','uses'=>'T_DefectaController@konfirmasi_transfer']);
	Route::post('defecta/konfirmasi_tolak', ['as'=>'defecta.konfirmasi_tolak','uses'=>'T_DefectaController@konfirmasi_tolak']);
	Route::post('defecta/konfirmasi_draft', ['as'=>'defecta.konfirmasi_draft','uses'=>'T_DefectaController@konfirmasi_draft']);


	// khusus route order
	Route::get('order/list_order', ['as'=>'order.list_order', 'uses'=>'T_OrderController@list_order']);
	Route::get('order/list_data_obat', ['as'=>'order.list_data_obat', 'uses'=>'T_OrderController@list_data_obat']);
	Route::get('order/data_order', ['as'=>'order.data_order', 'uses'=>'T_OrderController@data_order']);
	Route::get('order/list_data_order', ['as'=>'order.list_data_order', 'uses'=>'T_OrderController@list_data_order']);
	Route::resource('order', 'T_OrderController');
	Route::post('order/set_apotek_order_aktif', ['as'=>'order.set_apotek_order_aktif', 'uses'=>'T_OrderController@set_apotek_order_aktif']);
	Route::post('order/set_suplier_order_aktif', ['as'=>'order.set_suplier_order_aktif', 'uses'=>'T_OrderController@set_suplier_order_aktif']);
	Route::post('order/set_status_order_aktif', ['as'=>'order.set_status_order_aktif', 'uses'=>'T_OrderController@set_status_order_aktif']);
	Route::post('order/set_nota_order', ['as'=>'order.set_nota_order', 'uses'=>'T_OrderController@set_nota_order']);
	Route::post('order/cari_obat', ['as'=>'order.cari_obat', 'uses'=>'T_OrderController@cari_obat']);
	Route::post('order/open_data_obat', ['as'=>'order.open_data_obat', 'uses'=>'T_OrderController@open_data_obat']);
	Route::post('order/cari_obat_dialog', ['as'=>'order.cari_obat_dialog', 'uses'=>'T_OrderController@cari_obat_dialog']);
	Route::post('order/edit_detail', ['as'=>'order.edit_detail', 'uses'=>'T_OrderController@edit_detail']);
	Route::post('order/edit_order', ['as'=>'order.edit_order', 'uses'=>'T_OrderController@edit_order']);
	Route::put('order/update_defecta/{id}', ['as'=>'order.update_defecta', 'uses'=>'T_OrderController@update_defecta']);
	Route::put('order/update_order_detail/{id}', ['as'=>'order.update_order_detail', 'uses'=>'T_OrderController@update_order_detail']);



	// khusus route transfer
	Route::get('transfer/list_transfer', ['as'=>'transfer.list_transfer', 'uses'=>'T_TransferController@list_transfer']);
	Route::resource('transfer', 'T_TransferController');
	Route::post('transfer/set_apotek_transfer_aktif', ['as'=>'transfer.set_apotek_transfer_aktif', 'uses'=>'T_TransferController@set_apotek_transfer_aktif']);
	Route::post('transfer/set_apotektrans_transfer_aktif', ['as'=>'transfer.set_apotektrans_transfer_aktif', 'uses'=>'T_TransferController@set_apotektrans_transfer_aktif']);
	Route::post('transfer/set_status_transfer_aktif', ['as'=>'transfer.set_status_transfer_aktif', 'uses'=>'T_TransferController@set_status_transfer_aktif']);
	Route::post('transfer/set_nota_transfer', ['as'=>'transfer.set_nota_transfer', 'uses'=>'T_TransferController@set_nota_transfer']);
	Route::post('transfer/edit_detail', ['as'=>'transfer.edit_detail', 'uses'=>'T_TransferController@edit_detail']);
	/*=========================================== END =============================================*/ 


	/*=============================================================================================*/ 
	/*========================================== PENJUALAN ========================================*/
	/*=============================================================================================*/ 
	Route::get('penjualan/list_penjualan', ['as'=>'penjualan.list_penjualan', 'uses'=>'T_PenjualanController@list_penjualan']);
	Route::get('penjualan/list_penjualan_retur', ['as'=>'penjualan.list_penjualan_retur', 'uses'=>'T_PenjualanController@list_penjualan_retur']);
	Route::get('penjualan/list_data_obat', ['as'=>'penjualan.list_data_obat', 'uses'=>'T_PenjualanController@list_data_obat']);
	Route::get('penjualan/histori', ['as'=>'penjualan.histori', 'uses'=>'T_PenjualanController@histori']);
	Route::get('penjualan/list_histori', ['as'=>'penjualan.list_histori', 'uses'=>'T_PenjualanController@list_histori']);
	Route::get('penjualan/create_credit', ['as'=>'penjualan.create_credit', 'uses'=>'T_PenjualanController@create_credit']);
	Route::get('penjualan/kredit', ['as'=>'penjualan.kredit', 'uses'=>'T_PenjualanController@kredit']);
	Route::get('penjualan/list_kredit', ['as'=>'penjualan.list_kredit', 'uses'=>'T_PenjualanController@list_kredit']);
	Route::get('penjualan/detail/{id}', ['as'=>'penjualan.detail/{id}', 'uses'=>'T_PenjualanController@detail']);
	Route::get('penjualan/aprove', ['as'=>'penjualan.aprove', 'uses'=>'T_PenjualanController@aprove']);
	Route::get('penjualan/list_aprove', ['as'=>'penjualan.list_aprove', 'uses'=>'T_PenjualanController@list_aprove']);
	Route::get('penjualan/retur', ['as'=>'penjualan.retur', 'uses'=>'T_PenjualanController@retur']);
	Route::get('penjualan/list_retur', ['as'=>'penjualan.list_retur', 'uses'=>'T_PenjualanController@list_retur']);
	Route::get('penjualan/retur_aprove/{id}', ['as'=>'penjualan.retur_aprove', 'uses'=>'T_PenjualanController@retur_aprove']);
	Route::get('penjualan/cetak_nota/{id}', ['as'=>'penjualan.cetak_nota', 'uses'=>'T_PenjualanController@cetak_nota']);
	Route::get('penjualan/cetak_retur/{id}', ['as'=>'penjualan.cetak_retur', 'uses'=>'T_PenjualanController@cetak_retur']);
	Route::get('penjualan/lihat_detail_retur/{id}', ['as'=>'penjualan.lihat_detail_retur', 'uses'=>'T_PenjualanController@lihat_detail_retur']);
	Route::get('penjualan/list_data_pasien', ['as'=>'penjualan.list_data_pasien', 'uses'=>'T_PenjualanController@list_data_pasien']);
	Route::get('penjualan/print_closing_kasir/{id}', ['as'=>'penjualan.print_closing_kasir', 'uses'=>'T_PenjualanController@print_closing_kasir']);
	Route::get('penjualan/print_closing_kasir_pdf', ['as'=>'penjualan.print_closing_kasir_pdf', 'uses'=>'T_PenjualanController@print_closing_kasir_pdf']);
	Route::get('penjualan/load_data_nota_print/{id}', ['as'=>'penjualan.load_data_nota_print', 'uses'=>'T_PenjualanController@load_data_nota_print']);
	Route::get('penjualan/load_closing_kasir_print/{id}', ['as'=>'penjualan.load_closing_kasir_print', 'uses'=>'T_PenjualanController@load_closing_kasir_print']);
	Route::get('penjualan/pencarian_obat', ['as'=>'penjualan.pencarian_obat', 'uses'=>'T_PenjualanController@pencarian_obat']);
	Route::get('penjualan/list_pencarian_obat', ['as'=>'penjualan.list_pencarian_obat', 'uses'=>'T_PenjualanController@list_pencarian_obat']);
	Route::get('penjualan/rekap_laboratorium', ['as'=>'penjualan.rekap_laboratorium', 'uses'=>'T_PenjualanController@rekap_laboratorium']);
	Route::get('penjualan/list_rekap_laboratorium', ['as'=>'penjualan.list_rekap_laboratorium', 'uses'=>'T_PenjualanController@list_rekap_laboratorium']);
	Route::get('penjualan/export_rekap_laboratorium', ['as'=>'penjualan.export_rekap_laboratorium', 'uses'=>'T_PenjualanController@export_rekap_laboratorium']);
	Route::get('penjualan/rekap_jasa_dokter', ['as'=>'penjualan.rekap_jasa_dokter', 'uses'=>'T_PenjualanController@rekap_jasa_dokter']);
	Route::get('penjualan/list_rekap_jasa_dokter', ['as'=>'penjualan.list_rekap_jasa_dokter', 'uses'=>'T_PenjualanController@list_rekap_jasa_dokter']);
	Route::get('penjualan/export_rekap_jasa_dokter', ['as'=>'penjualan.export_rekap_jasa_dokter', 'uses'=>'T_PenjualanController@export_rekap_jasa_dokter']);
	Route::get('penjualan/rekap_jasa_resep', ['as'=>'penjualan.rekap_jasa_resep', 'uses'=>'T_PenjualanController@rekap_jasa_resep']);
	Route::get('penjualan/list_rekap_jasa_resep', ['as'=>'penjualan.list_rekap_jasa_resep', 'uses'=>'T_PenjualanController@list_rekap_jasa_resep']);
	Route::get('penjualan/export_rekap_jasa_resep', ['as'=>'penjualan.export_rekap_jasa_resep', 'uses'=>'T_PenjualanController@export_rekap_jasa_resep']);

	Route::get('penjualan/rekap_paket_wt', ['as'=>'penjualan.rekap_paket_wt', 'uses'=>'T_PenjualanController@rekap_paket_wt']);
	Route::get('penjualan/list_rekap_paket_wt', ['as'=>'penjualan.list_rekap_paket_wt', 'uses'=>'T_PenjualanController@list_rekap_paket_wt']);
	Route::get('penjualan/export_rekap_paket_wt', ['as'=>'penjualan.export_rekap_paket_wt', 'uses'=>'T_PenjualanController@export_rekap_paket_wt']);

	Route::get('penjualan/rekap_apd', ['as'=>'penjualan.rekap_apd', 'uses'=>'T_PenjualanController@rekap_apd']);
	Route::get('penjualan/list_rekap_apd', ['as'=>'penjualan.list_rekap_apd', 'uses'=>'T_PenjualanController@list_rekap_apd']);
	Route::get('penjualan/export_rekap_apd', ['as'=>'penjualan.export_rekap_apd', 'uses'=>'T_PenjualanController@export_rekap_apd']);

	Route::get('penjualan/rekap_omset', ['as'=>'penjualan.rekap_omset', 'uses'=>'T_PenjualanController@rekap_omset']);
	Route::get('penjualan/list_rekap_omset', ['as'=>'penjualan.list_rekap_omset', 'uses'=>'T_PenjualanController@list_rekap_omset']);
	Route::get('penjualan/export_rekap_omset', ['as'=>'penjualan.export_rekap_omset', 'uses'=>'T_PenjualanController@export_rekap_omset']);

	Route::get('penjualan/hpp', ['as'=>'penjualan.hpp', 'uses'=>'T_PenjualanController@hpp']);
	Route::get('penjualan/list_hpp', ['as'=>'penjualan.list_hpp', 'uses'=>'T_PenjualanController@list_hpp']);
	Route::get('penjualan/export_hpp', ['as'=>'penjualan.export_hpp', 'uses'=>'T_PenjualanController@export_hpp']);

	Route::get('penjualan/export_penjualan_kredit', ['as'=>'penjualan.export_penjualan_kredit', 'uses'=>'T_PenjualanController@export_penjualan_kredit']);
	Route::get('penjualan/export_all', ['as'=>'penjualan.export_all', 'uses'=>'T_PenjualanController@export_all']);
	Route::get('penjualan/cetak_nota_thermal/{id}', ['as'=>'penjualan.cetak_nota_thermal/{id}', 'uses'=>'T_PenjualanController@cetak_nota_thermal']);
	Route::get('penjualan/print_closing_kasir_thermal/{id}', ['as'=>'penjualan.print_closing_kasir_thermal/{id}', 'uses'=>'T_PenjualanController@print_closing_kasir_thermal']);
	Route::get('penjualan/load_page_print_closing_kasir/{id}', ['as'=>'penjualan.load_page_print_closing_kasir/{id}', 'uses'=>'T_PenjualanController@load_page_print_closing_kasir']);
	Route::get('penjualan/load_page_print_nota/{id}', ['as'=>'penjualan.load_page_print_nota/{id}', 'uses'=>'T_PenjualanController@load_page_print_nota']);
	Route::resource('penjualan', 'T_PenjualanController');
	Route::post('penjualan/cari_obat', ['as'=>'penjualan.cari_obat', 'uses'=>'T_PenjualanController@cari_obat']);
	Route::post('penjualan/cari_obat_dialog', ['as'=>'penjualan.cari_obat_dialog', 'uses'=>'T_PenjualanController@cari_obat_dialog']);
	Route::post('penjualan/open_data_obat', ['as'=>'penjualan.open_data_obat', 'uses'=>'T_PenjualanController@open_data_obat']);
	Route::post('penjualan/set_jasa_dokter', ['as'=>'penjualan.set_jasa_dokter', 'uses'=>'T_PenjualanController@set_jasa_dokter']);
	Route::post('penjualan/set_paket_wd', ['as'=>'penjualan.set_paket_wd', 'uses'=>'T_PenjualanController@set_paket_wd']);
	Route::post('penjualan/set_lab', ['as'=>'penjualan.set_lab', 'uses'=>'T_PenjualanController@set_lab']);
	Route::post('penjualan/set_apd', ['as'=>'penjualan.set_apd', 'uses'=>'T_PenjualanController@set_apd']);
	Route::post('penjualan/set_diskon_persen', ['as'=>'penjualan.set_diskon_persen', 'uses'=>'T_PenjualanController@set_diskon_persen']);
	Route::post('penjualan/open_pembayaran', ['as'=>'penjualan.open_pembayaran', 'uses'=>'T_PenjualanController@open_pembayaran']);
	Route::post('penjualan/find_ketentuan_keyboard', ['as'=>'penjualan.find_ketentuan_keyboard', 'uses'=>'T_PenjualanController@find_ketentuan_keyboard']);
	Route::post('penjualan/edit_detail', ['as'=>'penjualan.edit_detail', 'uses'=>'T_PenjualanController@edit_detail']);
	Route::post('penjualan/pembayaran_kredit/{id}', ['as'=>'penjualan.pembayaran_kredit', 'uses'=>'T_PenjualanController@pembayaran_kredit']);
	Route::post('penjualan/retur_item', ['as'=>'penjualan.retur_item', 'uses'=>'T_PenjualanController@retur_item']);
	Route::post('penjualan/set_jumlah_retur', ['as'=>'penjualan.set_jumlah_retur', 'uses'=>'T_PenjualanController@set_jumlah_retur']);
	Route::post('penjualan/batal_retur', ['as'=>'penjualan.batal_retur', 'uses'=>'T_PenjualanController@batal_retur']);
	Route::post('penjualan/retur_aprove_update/{id}', ['as'=>'penjualan.retur_aprove_update', 'uses'=>'T_PenjualanController@retur_aprove_update']);
	Route::post('penjualan/closing_kasir', ['as'=>'penjualan.closing_kasir', 'uses'=>'T_PenjualanController@closing_kasir']);
	Route::post('penjualan/cari_pasien_dialog', ['as'=>'penjualan.cari_pasien_dialog', 'uses'=>'T_PenjualanController@cari_pasien_dialog']);
	Route::post('penjualan/open_data_pasien', ['as'=>'penjualan.open_data_pasien', 'uses'=>'T_PenjualanController@open_data_pasien']);
	Route::put('penjualan/update_retur/{id}', ['as'=>'penjualan.update_retur', 'uses'=>'T_PenjualanController@update_retur']);
	Route::put('penjualan/update_pembayaran_kredit/{id}', ['as'=>'penjualan.update_pembayaran_kredit', 'uses'=>'T_PenjualanController@update_pembayaran_kredit']);
	Route::post('penjualan/cek_diskon', ['as'=>'penjualan.cek_diskon', 'uses'=>'T_PenjualanController@cek_diskon']);
	Route::post('penjualan/cek_diskon_item', ['as'=>'penjualan.cek_diskon_item', 'uses'=>'T_PenjualanController@cek_diskon_item']);
	Route::delete('penjualan/hapus_detail/{id}', ['as'=>'penjualan.hapus_detail', 'uses'=>'T_PenjualanController@hapus_detail']);
	

	Route::resource('penjualan_closing', 'T_PenjualanClosingController');
	
	Route::get('pembelian/list_pembelian_revisi', ['as'=>'pembelian.list_pembelian_revisi', 'uses'=>'T_PembelianController@list_pembelian_revisi']);
	Route::get('pembelian/list_pembelian', ['as'=>'pembelian.list_pembelian', 'uses'=>'T_PembelianController@list_pembelian']);
	Route::get('pembelian/list_pembelian_item', ['as'=>'pembelian.list_pembelian_item', 'uses'=>'T_PembelianController@list_pembelian_item']);
	Route::get('pembelian/data_pembelian_item', ['as'=>'pembelian.data_pembelian_item', 'uses'=>'T_PembelianController@data_pembelian_item']);
	Route::get('pembelian/list_data_suplier', ['as'=>'pembelian.list_data_suplier', 'uses'=>'T_PembelianController@list_data_suplier']);
	Route::get('pembelian/konfirmasi_barang_datang', ['as'=>'pembelian.konfirmasi_barang_datang', 'uses'=>'T_PembelianController@konfirmasi_barang_datang']);
	Route::get('pembelian/list_data_order', ['as'=>'pembelian.list_data_order', 'uses'=>'T_PembelianController@list_data_order']);
	Route::get('pembelian/pembayaran_faktur_belum_lunas', ['as'=>'pembelian.pembayaran_faktur_belum_lunas', 'uses'=>'T_PembelianController@pembayaran_faktur_belum_lunas']);
	Route::get('pembelian/list_pembayaran_faktur_belum_lunas', ['as'=>'pembelian.list_pembayaran_faktur_belum_lunas', 'uses'=>'T_PembelianController@list_pembayaran_faktur_belum_lunas']);
	Route::get('pembelian/pembayaran_faktur', ['as'=>'pembelian.pembayaran_faktur', 'uses'=>'T_PembelianController@pembayaran_faktur']);
	Route::get('pembelian/list_pembayaran_faktur', ['as'=>'pembelian.list_pembayaran_faktur', 'uses'=>'T_PembelianController@list_pembayaran_faktur']);
	Route::get('pembelian/pembayaran_faktur_lunas', ['as'=>'pembelian.pembayaran_faktur_lunas', 'uses'=>'T_PembelianController@pembayaran_faktur_lunas']);
	Route::get('pembelian/list_pembayaran_faktur_lunas', ['as'=>'pembelian.list_pembayaran_faktur_lunas', 'uses'=>'T_PembelianController@list_pembayaran_faktur_lunas']);
	Route::get('pembelian/reload_harga_beli_ppn', ['as'=>'pembelian.reload_harga_beli_ppn', 'uses'=>'T_PembelianController@reload_harga_beli_ppn']);
	Route::get('pembelian/reload_harga_ppn_form_outlet/{id}', ['as'=>'pembelian.reload_harga_ppn_form_outlet', 'uses'=>'T_PembelianController@reload_harga_ppn_form_outlet']);
	Route::get('pembelian/export', ['as'=>'pembelian.export', 'uses'=>'T_PembelianController@export']);
	Route::get('pembelian/export_all', ['as'=>'pembelian.export_all', 'uses'=>'T_PembelianController@export_all']);
	Route::get('pembelian/export_ed', ['as'=>'pembelian.export_ed', 'uses'=>'T_PembelianController@export_ed']);
	Route::get('pembelian/pencarian_obat', ['as'=>'pembelian.pencarian_obat', 'uses'=>'T_PembelianController@pencarian_obat']);
	Route::get('pembelian/list_pencarian_obat', ['as'=>'pembelian.list_pencarian_obat', 'uses'=>'T_PembelianController@list_pencarian_obat']);
	Route::get('pembelian/pembayaran_konsinyasi/{id}', ['as'=>'pembelian.pembayaran_konsinyasi/{id}', 'uses'=>'T_PembelianController@pembayaran_konsinyasi']);
	Route::get('pembelian/obat_kadaluarsa', ['as'=>'pembelian.obat_kadaluarsa', 'uses'=>'T_PembelianController@obat_kadaluarsa']);
	Route::get('pembelian/list_obat_kadaluarsa', ['as'=>'pembelian.list_obat_kadaluarsa', 'uses'=>'T_PembelianController@list_obat_kadaluarsa']);
	Route::get('pembelian/konfirmasi_ed/{id}', ['as'=>'pembelian.konfirmasi_ed', 'uses'=>'T_PembelianController@konfirmasi_ed']);
	Route::get('pembelian/reload_hb_ppn/{id}', ['as'=>'pembelian.reload_hb_ppn', 'uses'=>'T_PembelianController@reload_hb_ppn']);
	Route::get('pembelian/hapus_detail/{id}', ['as'=>'pembelian.hapus_detail', 'uses'=>'T_PembelianController@hapus_detail']);
	Route::resource('pembelian', 'T_PembelianController');
	Route::post('pembelian/konfirmasi_barang_store', ['as'=>'pembelian.konfirmasi_barang_store', 'uses'=>'T_PembelianController@konfirmasi_barang_store']);
	Route::post('pembelian/open_data_suplier', ['as'=>'pembelian.open_data_suplier', 'uses'=>'T_PembelianController@open_data_suplier']);
	Route::post('pembelian/cari_suplier_dialog', ['as'=>'pembelian.cari_suplier_dialog', 'uses'=>'T_PembelianController@cari_suplier_dialog']);
	Route::post('pembelian/find_ketentuan_keyboard', ['as'=>'pembelian.find_ketentuan_keyboard', 'uses'=>'T_PembelianController@find_ketentuan_keyboard']);
	Route::post('pembelian/edit_detail', ['as'=>'pembelian.edit_detail', 'uses'=>'T_PembelianController@edit_detail']);
	Route::post('pembelian/cek_tanda_terima_faktur', ['as'=>'pembelian.cek_tanda_terima_faktur', 'uses'=>'T_PembelianController@cek_tanda_terima_faktur']);
	Route::post('pembelian/edit_detail_from_order', ['as'=>'pembelian.edit_detail_from_order', 'uses'=>'T_PembelianController@edit_detail_from_order']);
	Route::post('pembelian/lunas_pembayaran', ['as'=>'pembelian.lunas_pembayaran', 'uses'=>'T_PembelianController@lunas_pembayaran']);
	Route::post('pembelian/lihat_detail_faktur', ['as'=>'pembelian.lihat_detail_faktur', 'uses'=>'T_PembelianController@lihat_detail_faktur']);
	Route::post('pembelian/set_pembayaran_kosinyasi/{id}', ['as' =>'pembelian.set_pembayaran_kosinyasi', 'uses' => 'T_PembelianController@set_pembayaran_kosinyasi']);
	Route::post('pembelian/add_pembayaran_konsinyasi', ['as'=>'pembelian.add_pembayaran_konsinyasi', 'uses'=>'T_PembelianController@add_pembayaran_konsinyasi']);
	Route::post('pembelian/update_pembayaran_konsinyasi/{id}', ['as'=>'pembelian.update_pembayaran_konsinyasi', 'uses'=>'T_PembelianController@update_pembayaran_konsinyasi']);
	Route::post('pembelian/update_konfirmasi_ed/{id}', ['as'=>'pembelian.update_konfirmasi_ed', 'uses'=>'T_PembelianController@update_konfirmasi_ed']);
	Route::post('pembelian/change_obat', ['as'=>'pembelian.change_obat', 'uses'=>'T_PembelianController@change_obat']);
	Route::post('pembelian/update_obat/{id}', ['as'=>'pembelian.update_obat', 'uses'=>'T_PembelianController@update_obat']);
	Route::resource('detail_pembelian', 'DetailPembelianController');

	Route::get('transfer_outlet/list_transfer_outlet', ['as'=>'transfer_outlet.list_transfer_outlet', 'uses'=>'T_TOController@list_transfer_outlet']);
	Route::get('transfer_outlet/permintaan_transfer', ['as'=>'transfer_outlet.permintaan_transfer', 'uses'=>'T_TOController@permintaan_transfer']);
	Route::get('transfer_outlet/load_data_nota_print/{id}', ['as'=>'transfer_outlet.load_data_nota_print', 'uses'=>'T_TOController@load_data_nota_print']);
	Route::get('transfer_outlet/pencarian_obat', ['as'=>'transfer_outlet.pencarian_obat', 'uses'=>'T_TOController@pencarian_obat']);
	Route::get('transfer_outlet/list_pencarian_obat', ['as'=>'transfer_outlet.list_pencarian_obat', 'uses'=>'T_TOController@list_pencarian_obat']);
	Route::get('transfer_outlet/export', ['as'=>'transfer_outlet.export', 'uses'=>'T_TOController@export']);
	Route::get('transfer_outlet/list_data_harga_obat', ['as'=>'transfer_outlet.list_data_harga_obat', 'uses'=>'T_TOController@list_data_harga_obat']);
	Route::get('transfer_outlet/konfirmasi_barang', ['as'=>'transfer_outlet.konfirmasi_barang', 'uses'=>'T_TOController@konfirmasi_barang']);
	Route::get('transfer_outlet/list_konfirmasi_barang', ['as'=>'transfer_outlet.list_konfirmasi_barang', 'uses'=>'T_TOController@list_konfirmasi_barang']);
	Route::get('transfer_outlet/konfirm/{id}', ['as'=>'transfer_outlet.konfirm', 'uses'=>'T_TOController@konfirm']);
	Route::resource('transfer_outlet', 'T_TOController');
	Route::post('transfer_outlet/find_ketentuan_keyboard', ['as'=>'transfer_outlet.find_ketentuan_keyboard', 'uses'=>'T_TOController@find_ketentuan_keyboard']);
	Route::post('transfer_outlet/edit_detail', ['as'=>'transfer_outlet.edit_detail', 'uses'=>'T_TOController@edit_detail']);
	Route::post('transfer_outlet/cetak_nota', ['as'=>'transfer_outlet.cetak_nota', 'uses'=>'T_TOController@cetak_nota']);
	Route::post('transfer_outlet/change_apotek', ['as'=>'transfer_outlet.change_apotek', 'uses'=>'T_TOController@change_apotek']);
	Route::post('transfer_outlet/update_apotek/{id}', ['as'=>'transfer_outlet.update_apotek', 'uses'=>'T_TOController@update_apotek']);
	Route::post('transfer_outlet/change_obat', ['as'=>'transfer_outlet.change_obat', 'uses'=>'T_TOController@change_obat']);
	Route::post('transfer_outlet/update_obat/{id}', ['as'=>'transfer_outlet.update_obat', 'uses'=>'T_TOController@update_obat']);
	Route::post('transfer_outlet/open_list_harga', ['as'=>'transfer_outlet.open_list_harga', 'uses'=>'T_TOController@open_list_harga']);
	Route::put('transfer_outlet/konfirm_update/{id}', ['as'=>'transfer_outlet.konfirm_update', 'uses'=>'T_TOController@konfirm_update']);
	Route::delete('transfer_outlet/hapus_detail/{id}', ['as'=>'transfer_outlet.hapus_detail', 'uses'=>'T_TOController@hapus_detail']);

	Route::get('transfer_dokter/list_transfer_dokter', ['as'=>'transfer_dokter.list_transfer_dokter', 'uses'=>'T_TDController@list_transfer_dokter']);
	Route::get('transfer_dokter/pencarian_obat', ['as'=>'transfer_dokter.pencarian_obat', 'uses'=>'T_TDController@pencarian_obat']);
	Route::get('transfer_dokter/list_pencarian_obat', ['as'=>'transfer_dokter.list_pencarian_obat', 'uses'=>'T_TDController@list_pencarian_obat']);
	Route::get('transfer_dokter/export', ['as'=>'transfer_dokter.export', 'uses'=>'T_TDController@export']);
	Route::get('transfer_dokter/load_data_nota_print/{id}', ['as'=>'transfer_dokter.load_data_nota_print', 'uses'=>'T_TDController@load_data_nota_print']);
	Route::resource('transfer_dokter', 'T_TDController');
	Route::post('transfer_dokter/find_ketentuan_keyboard', ['as'=>'transfer_dokter.find_ketentuan_keyboard', 'uses'=>'T_TDController@find_ketentuan_keyboard']);
	Route::post('transfer_dokter/edit_detail', ['as'=>'transfer_dokter.edit_detail', 'uses'=>'T_TDController@edit_detail']);
	Route::post('transfer_dokter/hapus_nota/{id}', ['as'=>'transfer_dokter.hapus_nota', 'uses'=>'T_TDController@destroy']);
	Route::post('transfer_dokter/cetak_nota', ['as'=>'transfer_dokter.cetak_nota', 'uses'=>'T_TDController@cetak_nota']);
	Route::post('transfer_dokter/hapus_detail/{id}', ['as'=>'transfer_dokter.hapus_detail', 'uses'=>'T_TDController@hapus_detail']);

	Route::get('obat_operasional/list_obat_operasional', ['as'=>'obat_operasional.list_obat_operasional', 'uses'=>'T_POController@list_obat_operasional']);
	Route::get('obat_operasional/pencarian_obat', ['as'=>'obat_operasional.pencarian_obat', 'uses'=>'T_POController@pencarian_obat']);
	Route::get('obat_operasional/list_pencarian_obat', ['as'=>'obat_operasional.list_pencarian_obat', 'uses'=>'T_POController@list_pencarian_obat']);
	Route::get('obat_operasional/export', ['as'=>'obat_operasional.export', 'uses'=>'T_POController@export']);
	Route::get('obat_operasional/load_data_nota_print/{id}', ['as'=>'obat_operasional.load_data_nota_print', 'uses'=>'T_POController@load_data_nota_print']);
	Route::resource('obat_operasional', 'T_POController');
	Route::post('obat_operasional/find_ketentuan_keyboard', ['as'=>'obat_operasional.find_ketentuan_keyboard', 'uses'=>'T_POController@find_ketentuan_keyboard']);
	Route::post('obat_operasional/edit_detail', ['as'=>'obat_operasional.edit_detail', 'uses'=>'T_POController@edit_detail']);
	Route::post('obat_operasional/hapus_nota/{id}', ['as'=>'obat_operasional.hapus_nota', 'uses'=>'T_POController@destroy']);
	Route::post('obat_operasional/cetak_nota', ['as'=>'obat_operasional.cetak_nota', 'uses'=>'T_POController@cetak_nota']);
    Route::post('obat_operasional/hapus_detail/{id}', ['as'=>'obat_operasional.hapus_detail', 'uses'=>'T_POController@hapus_detail']);
    
	Route::get('tips/list_tips', ['as'=>'tips.list_tips', 'uses'=>'TipsController@list_tips']);
    Route::resource('tips', 'TipsController');

    Route::get('news/list_news', ['as'=>'news.list_news', 'uses'=>'NewsController@list_news']);
    Route::resource('news', 'NewsController');
	/*=========================================== END =============================================*/ 

	Route::get('stok_opnam/export', ['as'=>'stok_opnam.export', 'uses'=>'StokOpnamController@export']);
	Route::get('stok_opnam/list_data', ['as'=>'stok_opnam.list_data', 'uses'=>'StokOpnamController@list_data']);
	Route::resource('stok_opnam', 'StokOpnamController');
	Route::post('stok_opnam/set_so_status_aktif', ['as'=>'stok_opnam.set_so_status_aktif', 'uses'=>'StokOpnamController@set_so_status_aktif']);

	/*Route::get('stok_opnam/lavie/export', ['as'=>'lavie.export', 'uses'=>'S_LVController@export']);
	Route::resource('stok_opnam/lavie', 'S_LVController');
	Route::post('stok_opnam/lavie/set_so_status_aktif', ['as'=>'lavie.set_so_status_aktif', 'uses'=>'S_LVController@set_so_status_aktif']);

	Route::get('stok_opnam/bekul/export', ['as'=>'bekul.export', 'uses'=>'S_BKLController@export']);
	Route::resource('stok_opnam/bekul', 'S_BKLController');
	Route::post('stok_opnam/bekul/set_so_status_aktif', ['as'=>'bekul.set_so_status_aktif', 'uses'=>'S_BKLController@set_so_status_aktif']);

	Route::get('stok_opnam/puja_mandala/export', ['as'=>'puja_mandala.export', 'uses'=>'S_PJMController@export']);
	Route::resource('stok_opnam/puja_mandala', 'S_PJMController');
	Route::post('stok_opnam/puja_mandala/set_so_status_aktif', ['as'=>'puja_mandala.set_so_status_aktif', 'uses'=>'S_PJMController@set_so_status_aktif']);

	Route::get('stok_opnam/puri_gading/export', ['as'=>'puri_gading.export', 'uses'=>'S_PGController@export']);
	Route::resource('stok_opnam/puri_gading', 'S_PGController');
	Route::post('stok_opnam/puri_gading/set_so_status_aktif', ['as'=>'puri_gading.set_so_status_aktif', 'uses'=>'S_PGController@set_so_status_aktif']);

	Route::get('stok_opnam/legian/export', ['as'=>'legian.export', 'uses'=>'S_TLController@export']);
	Route::resource('stok_opnam/legian', 'S_TLController');
	Route::post('stok_opnam/legian/set_so_status_aktif', ['as'=>'legian.set_so_status_aktif', 'uses'=>'S_TLController@set_so_status_aktif']);

	Route::get('stok_opnam/singaraja/export', ['as'=>'singaraja.export', 'uses'=>'S_SGController@export']);
	Route::resource('stok_opnam/singaraja', 'S_SGController');
	Route::post('stok_opnam/singaraja/set_so_status_aktif', ['as'=>'singaraja.set_so_status_aktif', 'uses'=>'S_SGController@set_so_status_aktif']);*/

	Route::get('setting_so/list_setting_so', ['as'=>'setting_so.list_setting_so', 'uses'=>'SettingSOController@list_setting_so']);
	Route::get('setting_so/reload_data_awal', ['as'=>'setting_so.reload_data_awal', 'uses'=>'SettingSOController@reload_data_awal']);
	Route::get('setting_so/reload_data_akhir', ['as'=>'setting_so.reload_data_akhir', 'uses'=>'SettingSOController@reload_data_akhir']);
	Route::get('setting_so/export', ['as'=>'setting_so.export', 'uses'=>'SettingSOController@export']);
	Route::resource('setting_so', 'SettingSOController');	

	//Route::get('edit_data_obat', ['as'=>'edit_data_obat', 'uses'=>'D_ObatController@edit_data_obat']);
	Route::resource('edit_data_obat', 'EditObatController');	

	Route::get('absensi/export_absensi', ['as'=>'absensi.export_absensi', 'uses'=>'AbsensiController@export_absensi']);
	Route::get('absensi/add_absensi', ['as'=>'absensi.add_absensi', 'uses'=>'AbsensiController@add_absensi']);
	Route::get('absensi/list_absensi', ['as'=>'absensi.list_absensi', 'uses'=>'AbsensiController@list_absensi']);
	Route::get('absensi/detail_data/{id1}/{id2}/{id3}/{id4}', ['as'=>'absensi.detail_data', 'uses'=>'AbsensiController@detail_data']);
	Route::get('absensi/list_data2', ['as'=>'absensi.list_data2', 'uses'=>'AbsensiController@list_data2']);
	Route::resource('absensi', 'AbsensiController');
	Route::post('absensi/cari_user', ['as'=>'absensi.cari_user', 'uses'=>'AbsensiController@cari_user']);


	// message
	Route::get('/list_message', 'ChatsController@index');
	Route::get('messages', 'ChatsController@fetchMessages');
	Route::post('messages', 'ChatsController@sendMessage');


	// untuk penggajian
	Route::get('jabatan/list_jabatan', ['as'=>'jabatan.list_jabatan', 'uses'=>'M_JabatanController@list_jabatan']);
	Route::resource('jabatan', 'M_JabatanController');

	Route::get('posisi/list_posisi', ['as'=>'posisi.list_posisi', 'uses'=>'M_PosisiController@list_posisi']);
	Route::resource('posisi', 'M_PosisiController');

	Route::get('status_karyawan/list_status_karyawan', ['as'=>'status_karyawan.list_status_karyawan', 'uses'=>'M_StatusKaryawanController@list_status_karyawan']);
	Route::resource('status_karyawan', 'M_StatusKaryawanController');
});


Route::prefix('homepage')->group(function () {
    Route::get('/', 'HomepageController@index')->name('index');
    Route::get('/about', 'HomepageController@about')->name('about');
    Route::get('/outlet', 'HomepageController@outlet')->name('outlet');
    Route::get('/tips', 'HomepageController@tips')->name('tips');
    Route::get('/tips/{slug}', 'HomepageController@tipsDetails')->name('tips_details');
    Route::get('/contact', 'HomepageController@contact')->name('contact');
    
    Route::get('/medical-record', 'HomepageController@medicalRecord')->name('medical_record');
    Route::post('/medical-record', 'HomepageController@medicalRecordSubmit')->name('medical_record.submit');
    
    Route::get('/doctor', 'HomepageController@doctor')->name('doctor');
    Route::get('/doctor/select', 'HomepageController@doctorSelect')->name('doctor.select');
    
    Route::get('/schedule', 'HomepageController@schedule')->name('schedule');
    Route::get('/schedule/select', 'HomepageController@scheduleSelect')->name('schedule.select');
    
    Route::get('/register', 'HomepageController@register')->name('register');
    Route::post('/register', 'HomepageController@registerSubmit')->name('register.submit');
    
    // Route::get('/pasien/login', 'HomepageController@login')->name('pasien.login');
    Route::post('/pasien/login', 'HomepageController@loginSubmit')->name('pasien.login.submit');

    Route::get('/session-display', 'HomepageController@sessionDisplay');
});
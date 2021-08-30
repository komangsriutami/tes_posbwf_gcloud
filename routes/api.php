<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::middleware('api')->group( function () {
    //Route::resource('products', 'API\ProductController');

    Route::get('download_apotek', ['as'=>'download_apotek', 'uses'=>'API\ServiceAppController@download_apotek']);
	Route::get('download_master_obat', ['as'=>'download_master_obat', 'uses'=>'API\ServiceAppController@download_master_obat']);
	Route::get('download_stok_obat', ['as'=>'download_stok_obat', 'uses'=>'API\ServiceAppController@download_stok_obat']);

	// route untuk go apotek
	Route::get('ef4c2ce3032d8f024c320308d9880a06', ['as'=>'ef4c2ce3032d8f024c320308d9880a06', 'uses'=>'API\ServiceAppController@ef4c2ce3032d8f024c320308d9880a06']);
	Route::get('f31d5936f25442ecf43a2e4a9aa911d1', ['as'=>'f31d5936f25442ecf43a2e4a9aa911d1', 'uses'=>'API\ServiceAppController@f31d5936f25442ecf43a2e4a9aa911d1']);
	Route::get('f36c008db00e367c7dae1c4a856e55ca', ['as'=>'f36c008db00e367c7dae1c4a856e55ca', 'uses'=>'API\ServiceAppController@f36c008db00e367c7dae1c4a856e55ca']);
	Route::get('ed70a85853284244f63de7fbd08ccea5', ['as'=>'ed70a85853284244f63de7fbd08ccea5', 'uses'=>'API\ServiceAppController@ed70a85853284244f63de7fbd08ccea5']);
	Route::get('f60ba84e9e162c05eaf305d15372e4f4', ['as'=>'f60ba84e9e162c05eaf305d15372e4f4', 'uses'=>'API\ServiceAppController@f60ba84e9e162c05eaf305d15372e4f4']);

	// template sinkron go apotek
	Route::get('template_lv', ['as'=>'template_lv', 'uses'=>'API\ServiceAppController@template_lv']);
	Route::get('template_bkl', ['as'=>'template_bkl', 'uses'=>'API\ServiceAppController@template_bkl']);
	Route::get('template_pjm', ['as'=>'template_pjm', 'uses'=>'API\ServiceAppController@template_pjm']);
	Route::get('template_pg', ['as'=>'template_pg', 'uses'=>'API\ServiceAppController@template_pg']);
	Route::get('template_tl', ['as'=>'template_tl', 'uses'=>'API\ServiceAppController@template_tl']);
});

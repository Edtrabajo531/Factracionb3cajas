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
Route::group([
    'middleware' => ['api', 'cors'],
], function () {
    Route::get('prueba', 'FacturacionController@prueba');
    Route::get('productos', 'FacturacionController@list_productos');
    Route::post('clientes', 'FacturacionController@list_clientes');
    Route::post('search-client', 'FacturacionController@searchClient');
    Route::post('store-update-client', 'FacturacionController@store_update_client');
    Route::post('pay', 'FacturacionController@pay');
    Route::get('data-print-invoice', 'FacturacionController@data_print_invoice');

    Route::post('search-product-code', 'FacturacionController@search_product_code');

    Route::post('validate-cash-status', 'FacturacionController@validate_cash_status');

});


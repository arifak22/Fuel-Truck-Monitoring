<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
use App\Helpers\Sideveloper;
Sideveloper::routeController('/auth','Api\AuthController');

Route::middleware(['jwt.verify'])->group(function () {
    Sideveloper::routeController('/transaksi','Api\TransaksiController');
    Sideveloper::routeController('/master-alat','Api\MasterAlatController');
});
<?php

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
use App\Helpers\Sideveloper;

Route::get('/', function () {
    return view('welcome');
});

Route::post('login', [ 'as' => 'login', 'uses' => 'LoginController@getIndex']);
Sideveloper::routeController('/login','Auth\LoginController');
Sideveloper::routeController('/home','HomeController');
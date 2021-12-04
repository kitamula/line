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

Route::get('/', function () {
    return view('welcome');
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('login/{provider}',          'Auth\SocialAccountController@redirectToProvider');
Route::get('login/{provider}/callback', 'Auth\SocialAccountController@handleProviderCallback');

// LINE メッセージ受信
Route::post('messaging/line/webhook', 'LineMessengerController@webhook')->name('messaging.line.webhook');
// LINE メッセージ送信用
Route::get('messaging/line/message', 'LineMessengerController@message')->name('messaging.line.message');

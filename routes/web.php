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

// 如果需要判断权限需要登录后操作
Route::group(['namespace' => 'Telegram', 'prefix' => 'telegram'], function() {
    // Route::post('/setTelegramWebhook', 'ConfigController@setTelegramWebhook')->name('setTelegramWebhook');
    Route::post('/set/webhook', 'ConfigController@setWebhook')->name('setWebhook');
    Route::post('/webhook', 'TelegramController@webhook')->name('webhook');
});

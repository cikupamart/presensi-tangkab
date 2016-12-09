<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/home', 'HomeController@index')->name('home');

// Pegawai
Route::get('pegawai', 'PegawaiController@index')->name('pegawai.index');
Route::get('pegawai/create', 'PegawaiController@create')->name('pegawai.create');
Route::post('pegawai', 'PegawaiController@store')->name('pegawai.post');
Route::get('pegawai/edit/{id}', 'PegawaiController@edit')->name('pegawai.edit');
Route::post('pegawai/edit', 'PegawaiController@editStore')->name('pegawai.editStore');

// SKPD
Route::get('skpd', 'SkpdController@index')->name('skpd.index');
Route::post('skpd', 'SkpdController@store')->name('skpd.post');
Route::get('skpd/{id}', 'SkpdController@bind');
Route::post('skpd/edit', 'SkpdController@edit')->name('skpd.edit');

// Golongan
Route::get('golongan', 'GolonganController@index')->name('golongan.index');
Route::post('golongan', 'GolonganController@store')->name('golongan.post');

// Jabatan
Route::get('jabatan', 'JabatanController@index')->name('jabatan.index');
Route::post('jabatan', 'JabatanController@store')->name('jabatan.post');
Route::get('jabatan/{id}', 'JabatanController@bind');
Route::post('jabatan/edit', 'JabatanController@edit')->name('jabatan.edit');

// Struktural
Route::get('struktural', 'StrukturalController@index')->name('struktural.index');
Route::post('struktural', 'StrukturalController@store')->name('struktural.post');

// Hari Libur
Route::get('harilibur', 'HariLiburController@index')->name('harilibur.index');
Route::post('harilibur', 'HariLiburController@store')->name('harilibur.post');
Route::get('harilibur/{id}', 'HariLiburController@bind');
Route::post('harilibur/edit', 'HariLiburController@edit')->name('harilibur.edit');

// Intervensi
Route::get('intervensi', 'IntervensiController@index')->name('intervensi.index');
Route::post('intervensi', 'IntervensiController@store')->name('intervensi.post');
Route::get('intervensi/bind/{id}', 'IntervensiController@bind');
Route::post('intervensi/edit', 'IntervensiController@edit')->name('intervensi.edit');
Route::get('intervensi/kelola', 'IntervensiController@kelola')->name('intervensi.kelola');
Route::get('intervensi/kelola/{id}', 'IntervensiController@kelolaAksi')->name('intervensi.kelola.aksi');

// Absensi
Route::get('absensi', 'AbsensiController@index')->name('absensi.index');


// Auth::routes();
Route::get('/', 'Auth\LoginController@showLoginForm')->name('index');
Route::post('login', 'Auth\LoginController@loginProcess')->name('login.proses');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
Route::get('firstLogin', 'Auth\LoginController@firstLogin')->name('firstLogin');

Route::get('cetakTpp', 'HomeController@cetakTPP')->name('cetakTPP');

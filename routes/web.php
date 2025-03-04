<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KonfigurasiController;
use App\Http\Controllers\MuridController;
use App\Http\Controllers\PoinController;
use App\Http\Controllers\PresensiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GambarController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['guest:user'])->group(function() {
    Route::get('/panel', function () {
        return view('auth.loginadmin');
    })->name('loginadmin');
    Route::post('/prosesloginadmin', [AuthController::class,'prosesloginadmin']);
});

Route::middleware(['guest:murid'])->group(function() {
    Route::get('/', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/proseslogin', [AuthController::class,'proseslogin']);
});

Route::middleware(['auth:murid'])->group(function () {
    //Login & Logout
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/proseslogout', [AuthController::class,'proseslogout']);

    //Presensi
    Route::get('/presensi/create', [PresensiController::class,'create']);
    Route::get('/gambar/{filename}', [GambarController::class, 'show']);
    Route::post('/presensi/store', [PresensiController::class, 'store']);
    
    // Cek status absensi siswa
    Route::get('/presensi/cek-absen', [PresensiController::class, 'cekAbsen'])->name('cek.absen');


    // Edit Profile
    Route::get('/editprofile',[PresensiController::class, 'editprofile']);
    Route::post('/presensi/{nisn}/updateprofile',[PresensiController::class, 'updateprofile']);

    //Histori
    Route::get('/presensi/histori', [PresensiController::class, 'histori']);
    Route::post('/gethistori', [PresensiController::class, 'gethistori']);

    //Izin
    Route::get('/presensi/izin', [PresensiController::class, 'izin']);
    Route::get('/presensi/buatizin', [PresensiController::class, 'buatizin']);
    Route::post('/presensi/storeizin', [PresensiController::class, 'storeizin']);
    Route::post('/presensi/cekpengajuanizin',[PresensiController::class,'cekpengajuanizin']);
});

Route::middleware(['auth:user'])->group(function(){
    Route::get('/proseslogoutadmin', [AuthController::class,'proseslogoutadmin']);
    Route::get('/panel/dashboardadmin',[DashboardController::class,'dashboardadmin']);

    //Murid
    Route::get('/murid',[MuridController::class,'index']);
    Route::post('/murid/store',[MuridController::class,'store']);
    Route::post('/murid/edit',[MuridController::class,'edit']);
    Route::post('/murid/{nisn}/update',[MuridController::class,'update']);
    Route::post('/murid/{nisn}/delete',[MuridController::class,'delete']);

    //Jurusan
    Route::get('/jurusan',[JurusanController::class,'index']);
    Route::post('/jurusan/store',[JurusanController::class,'store']);
    Route::post('/jurusan/edit',[JurusanController::class,'edit']);
    Route::post('/jurusan/{kode_jurusan}/update',[JurusanController::class,'update']);
    Route::post('/jurusan/{kode_jurusan}/delete',[JurusanController::class,'delete']);

    //Presensi
    Route::get('/presensi/monitoring',[PresensiController::class,'monitoring']);
    Route::post('/getpresensi',[PresensiController::class,'getpresensi']);
    Route::post('/tampilkanpeta',[PresensiController::class,'tampilkanpeta']);
    Route::get('/presensi/laporan',[PresensiController::class,'laporan']);
    Route::post('/presensi/cetaklaporan',[PresensiController::class,'cetaklaporan']);
    Route::get('/presensi/rekap',[PresensiController::class,'rekap']);
    Route::post('/presensi/cetakrekap',[PresensiController::class,'cetakrekap']);
    Route::get('/presensi/izinsakit',[PresensiController::class,'izinsakit']);
    Route::post('/presensi/approveizinsakit',[PresensiController::class,'approveizinsakit']);
    Route::get('/presensi/{id}/batalkanizinsakit',[PresensiController::class,'batalkanizinsakit']);

    //Poin Pelanggaran
    Route::get('/poin',[PoinController::class,'index']);
    Route::get('/poin/poin',[PoinController::class,'poin']);
    Route::get('/poin/{nisn}/riwayatpelanggaran',[PoinController::class,'riwayatpelanggaran']);
    Route::post('/poin/store',[PoinController::class,'store']);
    Route::post('/poin/{nisn}/editpoin/{id_riwayat}',[PoinController::class,'editpoin']);
    Route::post('/poin/{nisn}/updatepoin/{id_riwayat}',[PoinController::class,'updatepoin']);
    Route::post('/poin/{id_riwayat}/delete',[PoinController::class,'delete']);

    //Konfigurasi
    Route::get('/konfigurasi/lokasikantor',[KonfigurasiController::class,'lokasikantor']);
    Route::post('/konfigurasi/updatelokasikantor',[KonfigurasiController::class,'updatelokasikantor']);
});

<?php

use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BarangKeluarController;
use App\Http\Controllers\Api\LaporanController;
use Illuminate\Support\Facades\Route;

Route::apiResource('barang', BarangController::class);

Route::apiResource('barang-masuk', BarangMasukController::class);

Route::apiResource('barang-keluar', BarangKeluarController::class);

Route::get(
    'laporan/stok',
    [LaporanController::class, 'stok']
);

Route::get(
    'laporan/barang-masuk',
    [LaporanController::class, 'barangMasuk']
);

Route::get(
    'laporan/barang-keluar',
    [LaporanController::class, 'barangKeluar']
);

Route::get(
    'laporan/rekap-stok',
    [LaporanController::class, 'rekapStok']
);

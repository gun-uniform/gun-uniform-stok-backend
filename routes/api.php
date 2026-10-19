
<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BarangKeluarController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // =========================
    // MANAJEMEN AKUN
    // Admin dan pimpinan: lihat
    // Admin: tambah, edit, hapus
    // =========================

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:view-users');

    Route::middleware('can:manage-users')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // =========================
    // DATA BARANG
    // Semua role: lihat
    // Admin: tambah, edit, hapus
    // =========================

    Route::apiResource('barang', BarangController::class)
        ->only(['index', 'show'])
        ->middleware('can:view-stock');

    Route::apiResource('barang', BarangController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-barang');

    // =========================
    // BARANG MASUK
    // Semua role: lihat
    // Admin dan gudang: tambah, edit, hapus
    // =========================

    Route::apiResource('barang-masuk', BarangMasukController::class)
        ->only(['index', 'show'])
        ->middleware('can:view-stock');

    Route::apiResource('barang-masuk', BarangMasukController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-stock-transactions');

    // =========================
    // BARANG KELUAR
    // Semua role: lihat
    // Admin dan gudang: tambah, edit, hapus
    // =========================

    Route::apiResource('barang-keluar', BarangKeluarController::class)
        ->only(['index', 'show'])
        ->middleware('can:view-stock');

    Route::apiResource('barang-keluar', BarangKeluarController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-stock-transactions');

    // =========================
    // LAPORAN
    // Semua role: lihat
    // =========================

    Route::middleware('can:view-stock')->group(function () {
        Route::get('/laporan/stok', [LaporanController::class, 'stok']);

        Route::get('/laporan/barang-masuk', [
            LaporanController::class,
            'barangMasuk',
        ]);

        Route::get('/laporan/barang-keluar', [
            LaporanController::class,
            'barangKeluar',
        ]);

        Route::get('/laporan/rekap-stok', [
            LaporanController::class,
            'rekapStok',
        ]);
    });
});

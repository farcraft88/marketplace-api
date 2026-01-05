<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarketplaceController;

/*
|--------------------------------------------------------------------------
| API Routes - Marketplace Raden Mas
|--------------------------------------------------------------------------
*/

Route::get('/cek-api', function () {
    return response()->json(['status' => 'API Berhasil Diakses!']);
});
// --- 1. ROUTE PUBLIK (Bisa diakses tanpa login) ---
// Digunakan untuk pendaftaran, login, dan melihat katalog barang oleh calon pembeli.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/barang', [MarketplaceController::class, 'indexBarang']);

// --- 2. ROUTE TERPROTEKSI (Wajib menggunakan Token Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    // --- FITUR USER / PENYEWA ---
    // Endpoint untuk upgrade menjadi penyedia (buka toko)
    Route::post('/buka-toko', [AuthController::class, 'upgradePenyedia']);

    // Endpoint untuk melakukan penyewaan barang
    Route::post('/checkout', [MarketplaceController::class, 'checkout']);

    // --- FITUR PENYEDIA (KELOLA BARANG) ---
    // Menambah barang baru
    Route::post('/tambah-barang', [MarketplaceController::class, 'storeBarang']);

    // Update sebagian data barang (PATCH)
    Route::patch('/barang/{id}', [MarketplaceController::class, 'updateBarang']);

    // Menghapus barang
    Route::delete('/barang/{id}', [MarketplaceController::class, 'destroyBarang']);

    // --- AUTHENTICATION ---
    // Mendapatkan data user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout dan menghapus token
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil Logout'
        ]);
    });
    // Rute verifikasi-toko (WAJIB di luar rute /user)
    Route::post('/verifikasi-toko/{id}', [AuthController::class, 'verifikasiToko']);

    // Transaksi
    Route::get('/riwayat-transaksi', [MarketplaceController::class, 'riwayatTransaksi']);
    Route::post('/konfirmasi-transaksi/{id}', [MarketplaceController::class, 'konfirmasiTransaksi']);
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\Penyedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceController extends Controller
{
    // --- FITUR BARANG ---

    public function indexBarang()
    {
        return response()->json(Barang::all());
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'namabarang' => 'required',
            'harga' => 'required|numeric',
            'stok' => 'required|integer',
        ]);

        $user = $request->user();
        $penyedia = Penyedia::where('idpenyewa', $user->idpenyewa)->first();

        if (!$penyedia || $penyedia->status_verifikasi !== 'verified') {
            return response()->json(['message' => 'Toko Anda belum diverifikasi oleh Admin.'], 403);
        }

        $barang = Barang::create([
            'idbarang' => 'BRG-' . time(),
            'idpenyedia' => $penyedia->idpenyedia,
            'namabarang' => $request->namabarang,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'stok' => $request->stok,
            'foto' => $request->foto,
            'status' => 'tersedia'
        ]);

        return response()->json(['message' => 'Barang berhasil ditambahkan', 'data' => $barang], 201);
    }

    public function updateBarang(Request $request, $id)
    {
        $barang = Barang::where('idbarang', $id)->first();

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $penyedia = Penyedia::where('idpenyewa', $request->user()->idpenyewa)->first();
        if (!$penyedia || $barang->idpenyedia !== $penyedia->idpenyedia) {
            return response()->json(['message' => 'Akses ditolak. Ini bukan barang Anda.'], 403);
        }

        $dataUpdate = $request->only(['namabarang', 'deskripsi', 'harga', 'stok', 'foto', 'status']);
        $barang->update($dataUpdate);

        return response()->json(['message' => 'Barang diperbarui', 'data' => $barang]);
    }

    public function destroyBarang(Request $request, $id)
    {
        $barang = Barang::where('idbarang', $id)->first();

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $penyedia = Penyedia::where('idpenyewa', $request->user()->idpenyewa)->first();
        if (!$penyedia || $barang->idpenyedia !== $penyedia->idpenyedia) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $barang->delete();
        return response()->json(['message' => 'Barang berhasil dihapus']);
    }

    // --- FITUR TRANSAKSI ---

    public function checkout(Request $request)
    {
        $request->validate([
            'idbarang' => 'required',
            'jumlahhari' => 'required|integer|min:1'
        ]);

        $barang = Barang::where('idbarang', $request->idbarang)->first();

        if (!$barang || $barang->stok <= 0) {
            return response()->json(['message' => 'Barang tidak tersedia atau stok habis'], 404);
        }

        $total = $barang->harga * $request->jumlahhari;

        $trx = Transaksi::create([
            'idtransaksi' => 'TRX-' . time(),
            'idpenyewa' => $request->user()->idpenyewa,
            'idbarang' => $barang->idbarang,
            'idpenyedia' => $barang->idpenyedia,
            'tanggaltransaksi' => now(),
            'jumlahhari' => $request->jumlahhari,
            'totalharga' => $total,
            'status' => 'menunggu'
        ]);

        return response()->json(['message' => 'Checkout Berhasil', 'data' => $trx]);
    }

    public function riwayatTransaksi(Request $request)
    {
        $riwayat = Transaksi::where('idpenyewa', $request->user()->idpenyewa)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Daftar riwayat transaksi Raden Mas',
            'data' => $riwayat
        ]);
    }

    // --- FITUR KONFIRMASI (FIXED) ---
    public function konfirmasiTransaksi(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:disetujui,ditolak,selesai'
            ]);

            $trx = Transaksi::where('idtransaksi', $id)->first();
            if (!$trx) {
                return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
            }

            $user = $request->user();
            // Cek apakah user ini benar-benar punya data di tabel penyedia
            $penyedia = Penyedia::where('idpenyewa', $user->idpenyewa)->first();
            $isAdmin = $user->tokenCan('admin');

            // Gunakan Optional atau cek null sebelum akses property
            $idPenyediaToko = $penyedia ? $penyedia->idpenyedia : null;

            if ($isAdmin || ($idPenyediaToko && $trx->idpenyedia === $idPenyediaToko)) {

                if ($request->status === 'disetujui' && $trx->status !== 'disetujui') {
                    $barang = Barang::where('idbarang', $trx->idbarang)->first();
                    if ($barang && $barang->stok > 0) {
                        $barang->decrement('stok', 1);
                    }
                }

                $trx->update(['status' => $request->status]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Status berhasil diubah menjadi ' . $request->status,
                    'data' => $trx
                ]);

                // Tambahkan ini tepat setelah $trx->update
                $trx->refresh();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Status berhasil diubah menjadi ' . $request->status,
                    'data' => $trx
                ]);
            }

            return response()->json(['message' => 'Akses ditolak! Anda bukan pemilik toko ini.'], 403);
        } catch (\Exception $e) {
            // Jika masih error 500, pesan aslinya akan muncul di sini
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}

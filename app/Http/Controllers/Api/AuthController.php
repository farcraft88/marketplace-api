<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyewa;
use App\Models\Admin;
use App\Models\Penyedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // --- 1. REGISTER PENYEWA (UNTUK BUAT AKUN BARU) ---
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'namapenyewa'   => 'required|string',
            'email'         => 'required|email|unique:penyewa,email',
            'password'      => 'required|min:6',
            'notelepon'     => 'required',
            'alamatpenyewa' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan data ke tabel penyewa
        $user = Penyewa::create([
            'idpenyewa'     => 'PNW-' . time(), // Membuat ID String
            'namapenyewa'   => $request->namapenyewa,
            'email'         => $request->email,
            'password'      => Hash::make($request->password), // Di-hash agar aman
            'notelepon'     => $request->notelepon,
            'alamatpenyewa' => $request->alamatpenyewa,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Registrasi Berhasil, Selamat Datang!',
            'data'    => $user
        ], 201);
    }

    // --- 2. LOGIN (BISA ADMIN ATAU PENYEWA) ---
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // Cek Admin dulu
        $user = Admin::where('email', $request->email)->first();
        $role = 'admin';
        $userId = null;

        if ($user) {
            $userId = $user->id_admin;
        } else {
            // Jika bukan admin, cek Penyewa
            $user = Penyewa::where('email', $request->email)->first();
            $role = 'penyewa';
            if ($user) {
                $userId = $user->idpenyewa;
            }
        }

        // Validasi User & Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau Password Salah'], 401);
        }

        // Cek status toko jika dia penyewa
        $isPenyedia = false;
        if ($role === 'penyewa' && $userId) {
            $isPenyedia = Penyedia::where('idpenyewa', $userId)->exists();
        }

        // Buat Token Sanctum
        $token = $user->createToken('auth_token', [$role])->plainTextToken;

        return response()->json([
            'message'      => 'Login Berhasil, Selamat Datang ' . ($user->nama_admin ?? $user->namapenyewa),
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'role'         => $role,
            'is_penyedia'  => $isPenyedia,
            'user'         => $user
        ]);
    }

    // --- 3. UPGRADE KE PENYEDIA (BUKA TOKO) ---
    public function upgradePenyedia(Request $request)
    {
        $user = $request->user();

        if (Penyedia::where('idpenyewa', $user->idpenyewa)->exists()) {
            return response()->json(['message' => 'Anda sudah memiliki toko'], 400);
        }

        $penyedia = Penyedia::create([
            'idpenyedia'        => 'VND-' . time(),
            'idpenyewa'         => $user->idpenyewa,
            'namapenyedia'      => $request->namapenyedia,
            'status_verifikasi' => 'pending'
        ]);

        return response()->json([
            'message' => 'Toko berhasil dibuat, menunggu verifikasi admin',
            'data'    => $penyedia
        ]);
    }

    // --- 4. VERIFIKASI TOKO OLEH ADMIN ---
    public function verifikasiToko(Request $request, $id)
    {
        // 1. Cek apakah yang login adalah admin
        if ($request->user()->tokenCan('admin')) {

            $toko = Penyedia::where('idpenyedia', $id)->first();

            if (!$toko) {
                return response()->json(['message' => 'Toko tidak ditemukan'], 404);
            }

            $toko->update(['status_verifikasi' => 'verified']);

            return response()->json([
                'message' => 'Toko ' . $toko->namapenyedia . ' resmi diverifikasi!',
                'data' => $toko
            ]);
        }

        // Jika bukan admin yang mencoba akses
        return response()->json(['message' => 'Akses ditolak! Hanya Admin yang boleh verifikasi.'], 403);
    }
}

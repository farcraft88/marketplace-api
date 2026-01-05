<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Admin
        Schema::create('admins', function (Blueprint $table) {
            $table->string('id_admin', 20)->primary();
            $table->string('nama_admin');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // 2. Tabel Penyewa (User Biasa)
        Schema::create('penyewa', function (Blueprint $table) {
            $table->string('idpenyewa', 20)->primary();
            $table->string('namapenyewa');
            $table->string('email')->unique();
            $table->string('notelepon', 20);
            $table->string('password');
            $table->text('alamatpenyewa');
            $table->timestamps();
        });

        // 3. Tabel Penyedia (Toko)
        Schema::create('penyedia', function (Blueprint $table) {
            $table->string('idpenyedia', 20)->primary();
            $table->string('idpenyewa', 20); // Foreign Key ke Penyewa
            $table->string('namapenyedia');
            $table->enum('status_verifikasi', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreign('idpenyewa')->references('idpenyewa')->on('penyewa')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Tabel Barang
        Schema::create('barang', function (Blueprint $table) {
            $table->string('idbarang', 20)->primary();
            $table->string('idpenyedia', 20);
            $table->string('namabarang');
            $table->text('deskripsi')->nullable();
            $table->integer('harga');
            $table->integer('stok');
            $table->string('foto')->nullable();
            $table->foreign('idpenyedia')->references('idpenyedia')->on('penyedia')->onDelete('cascade');
            $table->timestamps();
        });

        // 5. Tabel Transaksi
        Schema::create('transaksi', function (Blueprint $table) {
            $table->string('idtransaksi', 20)->primary();
            $table->string('idpenyewa', 20);
            $table->string('idbarang', 20);
            $table->string('idpenyedia', 20);
            $table->date('tanggaltransaksi');
            $table->integer('jumlahhari');
            $table->integer('totalharga');
            $table->enum('status', ['menunggu', 'dibayar', 'selesai', 'batal'])->default('menunggu');
            
            $table->foreign('idpenyewa')->references('idpenyewa')->on('penyewa');
            $table->foreign('idbarang')->references('idbarang')->on('barang');
            $table->foreign('idpenyedia')->references('idpenyedia')->on('penyedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('barang');
        Schema::dropIfExists('penyedia');
        Schema::dropIfExists('penyewa');
        Schema::dropIfExists('admins');
    }
};
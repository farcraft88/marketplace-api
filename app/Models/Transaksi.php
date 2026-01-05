<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $primaryKey = 'idtransaksi';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'idtransaksi',
        'idpenyewa',
        'idbarang',
        'idpenyedia',
        'tanggaltransaksi',
        'jumlahhari',
        'totalharga',
        'status'
    ];

    // Tambahkan ini di dalam class Transaksi
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'idbarang', 'idbarang');
    }

    public function penyedia()
    {
        return $this->belongsTo(Penyedia::class, 'idpenyedia', 'idpenyedia');
    }
}

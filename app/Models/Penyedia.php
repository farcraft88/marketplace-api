<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyedia extends Model
{
    protected $table = 'penyedia';
    protected $primaryKey = 'idpenyedia';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['idpenyedia', 'idpenyewa', 'namapenyedia', 'status_verifikasi'];
}

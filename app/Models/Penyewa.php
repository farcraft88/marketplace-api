<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Penyewa extends Authenticatable
{
    use HasApiTokens; // PENTING: Agar bisa generate token

    protected $table = 'penyewa';
    protected $primaryKey = 'idpenyewa';
    public $incrementing = false; // Karena ID string
    protected $keyType = 'string';

    protected $fillable = [
        'idpenyewa', 'namapenyewa', 'email', 'notelepon', 'password', 'alamatpenyewa'
    ];

    protected $hidden = ['password'];
}
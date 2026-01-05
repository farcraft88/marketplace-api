<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admins';
    protected $primaryKey = 'id_admin';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id_admin', 'nama_admin', 'email', 'password'];
    protected $hidden = ['password'];
}
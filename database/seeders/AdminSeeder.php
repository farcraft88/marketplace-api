<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'id_admin' => 'ADM-001',
            'nama_admin' => 'Super Admin',
            'email' => 'admin@toko.com',
            'password' => Hash::make('password123')
        ]);
    }
}
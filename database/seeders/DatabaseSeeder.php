<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Supplier;
use App\Models\Barang;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        $owner = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@inventoryapp.com',
            'password' => Hash::make('password'),
            'role' => 'owner'
        ]);
        
        $admin = User::create([
            'name' => 'Admin Satu',
            'email' => 'admin@inventoryapp.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);
        
        $gudang = User::create([
            'name' => 'Gudang Joko',
            'email' => 'gudang@inventoryapp.com',
            'password' => Hash::make('password'),
            'role' => 'gudang'
        ]);
        
        // Create categories
        $kategoriResleting = Kategori::create([
            'nama' => 'Resleting', 
            'deskripsi' => 'Berbagai jenis resleting'
        ]);
        
        $kategoriRing = Kategori::create([
            'nama' => 'Ring', 
            'deskripsi' => 'Ring besi dan plastik'
        ]);
        
        $kategoriKain = Kategori::create([
            'nama' => 'Kain', 
            'deskripsi' => 'Kain bahan baku'
        ]);
        
        // Create suppliers
        $supplier1 = Supplier::create([
            'nama' => 'PT Sinar Resleting', 
            'kontak' => '08123456789', 
            'email' => 'sinar@resleting.com'
        ]);
        
        $supplier2 = Supplier::create([
            'nama' => 'CV Maju Ring', 
            'kontak' => '08198765432'
        ]);
        
        // Create products
        Barang::create([
            'nama' => 'Resleting No 3 Putih',
            'sku' => 'RSL-003-PTH',
            'stok' => 100,
            'stok_minimal' => 20,
            'kategori_id' => $kategoriResleting->id,
            'supplier_id' => $supplier1->id,
            'created_by' => $owner->id
        ]);
        
        Barang::create([
            'nama' => 'Ring Besi 2cm',
            'sku' => 'RNG-BSI-2CM',
            'stok' => 50,
            'stok_minimal' => 10,
            'kategori_id' => $kategoriRing->id,
            'supplier_id' => $supplier2->id,
            'created_by' => $admin->id
        ]);
        
        Barang::create([
            'nama' => 'Kain Katun Hitam',
            'sku' => 'KNN-KTN-HTM',
            'stok' => 500,
            'stok_minimal' => 100,
            'satuan' => 'meter',
            'kategori_id' => $kategoriKain->id,
            'created_by' => $owner->id
        ]);
    }
}
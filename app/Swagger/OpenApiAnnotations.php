<?php
// app/Swagger/OpenApiAnnotations.php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="InventoryApp API",
 *     version="1.0.0",
 *     description="API untuk manajemen inventory dengan role Owner, Admin, Gudang",
 *     @OA\Contact(
 *         email="support@inventoryapp.com",
 *         name="Support Team"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Masukkan token Bearer yang didapat dari login"
 * )
 * 
 * @OA\Components(
 *     @OA\Schema(
 *         schema="User",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *         @OA\Property(property="role", type="string", enum={"owner","admin","gudang"}, example="gudang"),
 *         @OA\Property(property="created_at", type="string", format="datetime"),
 *         @OA\Property(property="updated_at", type="string", format="datetime")
 *     ),
 *     @OA\Schema(
 *         schema="LoginRequest",
 *         type="object",
 *         required={"email","password"},
 *         @OA\Property(property="email", type="string", format="email", example="owner@inventoryapp.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password")
 *     ),
 *     @OA\Schema(
 *         schema="LoginResponse",
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Login successful"),
 *         @OA\Property(property="user", ref="#/components/schemas/User"),
 *         @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxx"),
 *         @OA\Property(property="token_type", type="string", example="Bearer")
 *     ),
 *     @OA\Schema(
 *         schema="Kategori",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nama", type="string", example="Resleting"),
 *         @OA\Property(property="deskripsi", type="string", example="Berbagai jenis resleting"),
 *         @OA\Property(property="created_at", type="string", format="datetime"),
 *         @OA\Property(property="updated_at", type="string", format="datetime")
 *     ),
 *     @OA\Schema(
 *         schema="Supplier",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nama", type="string", example="PT Sinar Resleting"),
 *         @OA\Property(property="kontak", type="string", example="08123456789"),
 *         @OA\Property(property="email", type="string", format="email", example="sinar@resleting.com"),
 *         @OA\Property(property="alamat", type="string", example="Jakarta"),
 *         @OA\Property(property="created_at", type="string", format="datetime"),
 *         @OA\Property(property="updated_at", type="string", format="datetime")
 *     ),
 *     @OA\Schema(
 *         schema="Barang",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nama", type="string", example="Resleting No 3 Putih"),
 *         @OA\Property(property="sku", type="string", example="RSL-003-PTH"),
 *         @OA\Property(property="deskripsi", type="string", example="Resleting premium"),
 *         @OA\Property(property="stok", type="integer", example=100),
 *         @OA\Property(property="stok_minimal", type="integer", example=20),
 *         @OA\Property(property="satuan", type="string", example="pcs"),
 *         @OA\Property(property="kategori_id", type="integer", example=1),
 *         @OA\Property(property="supplier_id", type="integer", example=1),
 *         @OA\Property(property="created_by", type="integer", example=1),
 *         @OA\Property(property="created_at", type="string", format="datetime"),
 *         @OA\Property(property="updated_at", type="string", format="datetime")
 *     ),
 *     @OA\Schema(
 *         schema="StokMasukRequest",
 *         type="object",
 *         required={"barang_id","jumlah"},
 *         @OA\Property(property="barang_id", type="integer", example=1),
 *         @OA\Property(property="jumlah", type="integer", example=50),
 *         @OA\Property(property="keterangan", type="string", example="Pembelian dari supplier"),
 *         @OA\Property(property="referensi", type="string", example="INV/2025/001")
 *     ),
 *     @OA\Schema(
 *         schema="StokKeluarRequest",
 *         type="object",
 *         required={"barang_id","jumlah"},
 *         @OA\Property(property="barang_id", type="integer", example=1),
 *         @OA\Property(property="jumlah", type="integer", example=10),
 *         @OA\Property(property="keterangan", type="string", example="Pengambilan untuk produksi"),
 *         @OA\Property(property="referensi", type="string", example="PROD/2025/001")
 *     ),
 *     @OA\Schema(
 *         schema="StokAdjustRequest",
 *         type="object",
 *         required={"barang_id","stok_baru","keterangan"},
 *         @OA\Property(property="barang_id", type="integer", example=1),
 *         @OA\Property(property="stok_baru", type="integer", example=150),
 *         @OA\Property(property="keterangan", type="string", example="Penyesuaian stok fisik")
 *     ),
 *     @OA\Schema(
 *         schema="SendMessageRequest",
 *         type="object",
 *         required={"body"},
 *         @OA\Property(property="user_id", type="integer", example=2),
 *         @OA\Property(property="conversation_id", type="integer", example=1),
 *         @OA\Property(property="body", type="string", example="Halo, tolong cek stok")
 *     )
 * )
 */
class OpenApiAnnotations
{
    // This class is only for Swagger annotations
}
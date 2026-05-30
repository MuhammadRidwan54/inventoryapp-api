<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\StokController;
use App\Http\Controllers\Api\V1\InboxController;
use App\Http\Controllers\Api\V1\AktivitasController;
use App\Http\Controllers\Api\V1\BarangController;
use App\Http\Controllers\Api\V1\KategoriController;
use App\Http\Controllers\Api\V1\SupplierController;

// Prefix: /api/v1
Route::prefix('v1')->group(function () {
    
    // PUBLIC ROUTES
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // PROTECTED ROUTES (require auth)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        
        // Dashboard (akses semua role, tapi isinya beda)
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // MASTER DATA - hanya owner & admin
        Route::middleware('role:owner,admin')->prefix('master')->group(function () {
            
            // ========== KATEGORI ==========
            // GET /api/v1/master/kategori
            // GET /api/v1/master/kategori/{id}
            // POST /api/v1/master/kategori
            // PUT /api/v1/master/kategori/{id}
            // DELETE /api/v1/master/kategori/{id}
            Route::apiResource('kategori', KategoriController::class);
            
            // GET /api/v1/master/kategori-all (untuk dropdown/select)
            Route::get('kategori-all', [KategoriController::class, 'all']);
            
            // ========== SUPPLIER ==========
            // GET /api/v1/master/supplier
            // GET /api/v1/master/supplier/{id}
            // POST /api/v1/master/supplier
            // PUT /api/v1/master/supplier/{id}
            // DELETE /api/v1/master/supplier/{id}
            Route::apiResource('supplier', SupplierController::class);
            
            // GET /api/v1/master/supplier-all (untuk dropdown/select)
            Route::get('supplier-all', [SupplierController::class, 'all']);
            
            // ========== BARANG ==========
            // GET /api/v1/master/barang
            // GET /api/v1/master/barang/{id}
            // POST /api/v1/master/barang
            // PUT /api/v1/master/barang/{id}
            // DELETE /api/v1/master/barang/{id}
            Route::apiResource('barang', BarangController::class);
            
            // Extra routes untuk foto barang
            Route::delete('barang/{barang}/foto/{foto}', [BarangController::class, 'deleteFoto']);
            Route::put('barang/{barang}/foto/{foto}/primary', [BarangController::class, 'setPrimaryFoto']);
        });
        
        // STOK - gudang, admin, owner bisa
        Route::middleware('role:owner,admin,gudang')->prefix('stok')->group(function () {
            // Semua role gudang, admin, owner bisa akses
            Route::post('/masuk', [StokController::class, 'masuk']);
            Route::post('/keluar', [StokController::class, 'keluar']);
            Route::get('/histori/{barangId}', [StokController::class, 'histori']);
            
            // Hanya owner & admin yang bisa akses
            Route::middleware('role:owner,admin')->group(function () {
                Route::post('/adjust', [StokController::class, 'adjust']);
                Route::get('/rekap', [StokController::class, 'rekap']);
                Route::get('/mutasi', [StokController::class, 'mutasi']);
            });
        });
        
        // INBOX - semua role bisa
        Route::prefix('inbox')->group(function () {
            Route::get('/conversations', [InboxController::class, 'conversations']);
            Route::get('/conversations/{conversation}/messages', [InboxController::class, 'messages']);
            Route::post('/send', [InboxController::class, 'send']);
            Route::post('/conversations/{conversation}/read', [InboxController::class, 'markAsRead']);
            Route::get('/unread-count', [InboxController::class, 'unreadCount']);
            Route::get('/system', [InboxController::class, 'systemMessages']);
            Route::get('/search', [InboxController::class, 'search']);
            
            Route::middleware('role:owner,admin')->group(function () {
                Route::delete('/messages/{message}', [InboxController::class, 'deleteMessage']);
            });
        });
        
        // AKTIVITAS - hanya owner
        Route::middleware('role:owner')->prefix('aktivitas')->group(function () {
            Route::get('/', [AktivitasController::class, 'index']);
            Route::get('/statistics', [AktivitasController::class, 'statistics']);
            Route::get('/actions', [AktivitasController::class, 'actionsList']);
            Route::get('/models', [AktivitasController::class, 'modelsList']);
            Route::get('/export', [AktivitasController::class, 'export']);
            Route::delete('/cleanup', [AktivitasController::class, 'cleanup']);
            Route::get('/user/{userId}', [AktivitasController::class, 'userActivities']);
            Route::get('/{id}', [AktivitasController::class, 'show']);
        });

        Route::get('/health', function () {
            return response()->json(['status' => 'ok', 'time' => now()]);
        });
    });
});
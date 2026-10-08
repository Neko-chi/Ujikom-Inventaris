<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangKeluarController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Hanya untuk pengunjung yang belum login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Semua role: mengubah profil sendiri (nama, foto, password).
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    // Catatan: route ".../create" didaftarkan sebelum route ".../{id}"
    // agar kata "create" tidak dianggap sebagai id.

    // Admin & Operator: mendaftarkan barang baru.
    Route::middleware('role:Admin,Operator')->group(function () {
        Route::get('/barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
    });

    // Operator: mencatat transaksi.
    Route::middleware('role:Operator')->group(function () {
        Route::get('/barang-masuk/create', [BarangMasukController::class, 'create'])->name('barang-masuk.create');
        Route::post('/barang-masuk', [BarangMasukController::class, 'store'])->name('barang-masuk.store');
        Route::get('/barang-keluar/create', [BarangKeluarController::class, 'create'])->name('barang-keluar.create');
        Route::post('/barang-keluar', [BarangKeluarController::class, 'store'])->name('barang-keluar.store');
    });

    // Admin: mengelola pengguna & data master, mengoreksi barang masuk.
    Route::middleware('role:Admin')->group(function () {
        Route::resource('user', UserController::class)->except('show');
        Route::resource('kategori', KategoriController::class)->except('show')
            ->parameters(['kategori' => 'kategori']);
        Route::resource('barang', BarangController::class)->only(['edit', 'update', 'destroy']);
        Route::resource('barang-masuk', BarangMasukController::class)->only(['edit', 'update', 'destroy']);
    });

    // Manager: memverifikasi (acc / tolak) barang keluar. Selain itu Manager hanya dapat melihat.
    Route::middleware('role:Manager')->group(function () {
        Route::patch('/barang-keluar/{barangKeluar}/setujui', [BarangKeluarController::class, 'setujui'])->name('barang-keluar.setujui');
        Route::patch('/barang-keluar/{barangKeluar}/tolak', [BarangKeluarController::class, 'tolak'])->name('barang-keluar.tolak');
    });

    // Dapat dilihat oleh semua role (Admin, Operator, Manager).
    Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
    Route::get('/barang/{barang}', [BarangController::class, 'show'])->name('barang.show');
    Route::get('/barang-masuk', [BarangMasukController::class, 'index'])->name('barang-masuk.index');
    Route::get('/barang-keluar', [BarangKeluarController::class, 'index'])->name('barang-keluar.index');
    Route::get('/barang-keluar/{barangKeluar}', [BarangKeluarController::class, 'show'])->name('barang-keluar.show');
});

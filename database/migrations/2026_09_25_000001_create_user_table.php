<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `user` : menyimpan akun pengguna sistem.
 *  - Admin    : pengawas, memverifikasi barang keluar & mengelola data master/pengguna.
 *  - Operator : petugas gudang, mencatat barang masuk & mengajukan barang keluar.
 * Kolom `password` VARCHAR(255) karena disimpan dalam bentuk hash bcrypt (60 karakter).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->integer('id_user')->autoIncrement();
            $table->string('nama_user', 100);
            $table->string('username', 50)->unique();
            $table->string('password', 255);
            $table->enum('role', ['Admin', 'Operator']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};

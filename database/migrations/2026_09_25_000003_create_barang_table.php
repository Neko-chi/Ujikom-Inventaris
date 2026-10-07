<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `barang` : data master barang di gudang.
 * `id_barang` = primary key auto increment,
 * `kode_barang` = kode unik berpola PREFIX-NOMOR (contoh AT-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->integer('id_barang')->autoIncrement();
            $table->string('kode_barang', 20)->unique();
            $table->integer('id_kategori');
            $table->string('nama_barang', 100);
            $table->integer('stok')->default(0);
            $table->string('satuan', 20);
            $table->string('lokasi', 100);
            $table->string('status_barang', 30);

            // Kategori tidak boleh dihapus selama masih dipakai barang.
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};

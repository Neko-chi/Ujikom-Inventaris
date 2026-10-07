<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `barang_keluar` : permintaan pengeluaran barang.
 *  - id_user        : Operator yang mengajukan.
 *  - id_verifikator : Admin yang menyetujui / menolak (kosong selama Pending).
 * Stok baru berkurang setelah Admin memberi verifikasi "Disetujui".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_keluar', function (Blueprint $table) {
            $table->integer('id_keluar')->autoIncrement();
            $table->integer('id_barang');
            $table->integer('id_user');
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->string('pemohon', 100);
            $table->string('tujuan', 100);
            $table->string('status_barang', 30);
            $table->enum('verifikasi', ['Pending', 'Disetujui', 'Ditolak'])->default('Pending');
            $table->integer('id_verifikator')->nullable();
            $table->date('tanggal_verifikasi')->nullable();
            $table->string('catatan_verifikasi', 255)->nullable();

            $table->foreign('id_barang')->references('id_barang')->on('barang')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_verifikator')->references('id_user')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_keluar');
    }
};

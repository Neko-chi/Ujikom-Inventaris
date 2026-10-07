<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `barang_masuk` : riwayat penerimaan barang ke gudang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_masuk', function (Blueprint $table) {
            $table->integer('id_masuk')->autoIncrement();
            $table->integer('id_barang');
            $table->integer('id_user');
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->string('sumber_barang', 255);
            $table->string('status_barang', 30);

            $table->foreign('id_barang')->references('id_barang')->on('barang')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_masuk');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kategori` : pengelompokan barang (Alat Tulis, Furnitur, dst).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->integer('id_kategori')->autoIncrement();
            $table->string('nama_kategori', 20)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};

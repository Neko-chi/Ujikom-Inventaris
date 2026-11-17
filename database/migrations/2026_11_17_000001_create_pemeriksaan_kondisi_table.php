<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `pemeriksaan_kondisi`: penyesuaian kondisi barang di data dengan kondisi fisiknya.
 *
 * Contoh: di data tercatat 20 Meja Guru Baik di Gudang Utama, ternyata 10 di antaranya
 * Rusak Ringan. Operator mengajukan "10 buah Baik -> Rusak Ringan" beserta alasan dan foto,
 * lalu Manager memeriksa dan menyetujui. Total stok tetap, hanya kondisinya yang berubah.
 *
 *  - id_user        : Operator yang mengajukan.
 *  - id_verifikator : Manager yang menyetujui / menolak (kosong selama Pending).
 * Stok baru berubah setelah Manager memberi verifikasi "Disetujui".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan_kondisi', function (Blueprint $table) {
            $table->integer('id_pemeriksaan')->autoIncrement();
            $table->integer('id_barang');
            $table->integer('id_lokasi');
            $table->integer('id_user');
            $table->date('tanggal');
            $table->string('kondisi_asal', 20); // Baik | Rusak Ringan | Rusak Berat
            $table->string('kondisi_baru', 20); // Baik | Rusak Ringan | Rusak Berat
            $table->integer('jumlah');
            $table->string('alasan', 255);
            $table->string('foto', 255)->nullable(); // wajib di aplikasi
            $table->string('verifikasi', 20)->default('Pending'); // Pending | Disetujui | Ditolak
            $table->integer('id_verifikator')->nullable();
            $table->date('tanggal_verifikasi')->nullable();
            $table->string('catatan_verifikasi', 255)->nullable();

            $table->foreign('id_barang')->references('id_barang')->on('barang')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('user')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_verifikator')->references('id_user')->on('user')->cascadeOnUpdate()->restrictOnDelete();
        });

        // Supabase (PostgreSQL): tutup akses REST API publik, sama seperti tabel lain.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE "pemeriksaan_kondisi" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_kondisi');
    }
};

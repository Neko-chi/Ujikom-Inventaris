<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Khusus PostgreSQL (Supabase): mengaktifkan Row Level Security pada semua tabel.
 *
 * Supabase otomatis membuka tabel di skema `public` lewat REST API publik.
 * Dengan RLS aktif tanpa policy, akses lewat API tersebut tertutup,
 * sedangkan Laravel tetap bisa mengakses karena terhubung sebagai pemilik tabel.
 * Di MySQL migration ini tidak melakukan apa-apa.
 */
return new class extends Migration
{
    private array $tabel = ['user', 'kategori', 'barang', 'barang_masuk', 'barang_keluar', 'migrations'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tabel as $nama) {
            DB::statement("ALTER TABLE \"{$nama}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tabel as $nama) {
            DB::statement("ALTER TABLE \"{$nama}\" DISABLE ROW LEVEL SECURITY");
        }
    }
};

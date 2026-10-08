<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi kedua rancangan database:
 *  - Role baru "Manager" (pengawas yang memberi persetujuan barang keluar).
 *  - Kolom foto/gambar untuk fitur unggah gambar (multimedia):
 *      user.foto           foto profil (opsional)
 *      barang.gambar       gambar barang (opsional)
 *      barang_masuk.foto   foto bukti penerimaan (opsional)
 *      barang_keluar.foto  foto bukti pengajuan (wajib di aplikasi; di database boleh
 *                          kosong agar data lama yang belum punya foto tetap valid)
 * Kolom foto menyimpan lokasi file (path), bukan isi gambarnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubahPilihanRole(['Admin', 'Operator', 'Manager']);

        Schema::table('user', function (Blueprint $table) {
            $table->string('foto', 255)->nullable();
        });

        Schema::table('barang', function (Blueprint $table) {
            $table->string('gambar', 255)->nullable();
        });

        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->string('foto', 255)->nullable();
        });

        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->string('foto', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('barang_keluar', fn (Blueprint $table) => $table->dropColumn('foto'));
        Schema::table('barang_masuk', fn (Blueprint $table) => $table->dropColumn('foto'));
        Schema::table('barang', fn (Blueprint $table) => $table->dropColumn('gambar'));

        Schema::table('user', fn (Blueprint $table) => $table->dropColumn('foto'));
        $this->ubahPilihanRole(['Admin', 'Operator']);
    }

    /**
     * Mengubah daftar nilai yang diizinkan pada kolom enum `role`.
     * Di PostgreSQL, enum Laravel berupa CHECK constraint bernama "user_role_check"
     * dan `->change()` menghasilkan SQL yang tidak valid, sehingga constraint diganti manual.
     */
    private function ubahPilihanRole(array $roles): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $daftar = implode(', ', array_map(fn (string $role) => "'{$role}'", $roles));

            DB::statement('ALTER TABLE "user" DROP CONSTRAINT IF EXISTS "user_role_check"');
            DB::statement("ALTER TABLE \"user\" ADD CONSTRAINT \"user_role_check\" CHECK (role IN ({$daftar}))");

            return;
        }

        Schema::table('user', fn (Blueprint $table) => $table->enum('role', $roles)->change());
    }
};

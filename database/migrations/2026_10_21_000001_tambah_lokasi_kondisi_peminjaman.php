<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saran pembina:
 *  1. Barang keluar punya jenis: Peminjaman (bisa dikembalikan), Pemindahan (ke ruangan lain), Pemusnahan.
 *  2. Kondisi dicatat per jumlah (contoh 20 barang = 5 Baik, 10 Rusak Ringan, 5 Rusak Berat).
 *  3. Lokasi barang dicatat (Gudang Utama, Gudang 2, Lab TKJ, ...), stok dihitung per lokasi.
 *
 * Perubahan tabel:
 *  - Tabel baru `lokasi`, `stok_barang` (stok per barang + lokasi + kondisi), `pengembalian`.
 *  - `barang`: kolom `lokasi` dan `status_barang` dihapus (pindah ke `stok_barang`); `stok` = total.
 *  - `barang_masuk`: `status_barang` diganti `id_lokasi` + jumlah per kondisi.
 *  - `barang_keluar`: `status_barang` diganti `jenis_keluar`, lokasi asal/tujuan, jumlah per kondisi,
 *    dan `tanggal_kembali` (rencana kembali untuk peminjaman).
 * Data lama dipindahkan otomatis ke struktur baru.
 */
return new class extends Migration
{
    private const KOLOM = ['Baik' => 'jumlah_baik', 'Rusak Ringan' => 'jumlah_rusak_ringan', 'Rusak Berat' => 'jumlah_rusak_berat'];

    public function up(): void
    {
        Schema::create('lokasi', function (Blueprint $table) {
            $table->integer('id_lokasi')->autoIncrement();
            $table->string('nama_lokasi', 50)->unique();
            $table->string('jenis', 20)->default('Gudang'); // Gudang | Ruangan
            $table->string('keterangan', 255)->nullable();
        });

        Schema::create('stok_barang', function (Blueprint $table) {
            $table->integer('id_stok')->autoIncrement();
            $table->integer('id_barang');
            $table->integer('id_lokasi');
            $table->string('kondisi', 20); // Baik | Rusak Ringan | Rusak Berat
            $table->integer('jumlah')->default(0);

            $table->unique(['id_barang', 'id_lokasi', 'kondisi']);
            $table->foreign('id_barang')->references('id_barang')->on('barang')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->integer('id_lokasi')->nullable();
            $table->integer('jumlah_baik')->default(0);
            $table->integer('jumlah_rusak_ringan')->default(0);
            $table->integer('jumlah_rusak_berat')->default(0);

            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->string('jenis_keluar', 20)->default('Peminjaman'); // Peminjaman | Pemindahan | Pemusnahan
            $table->integer('id_lokasi_asal')->nullable();
            $table->integer('id_lokasi_tujuan')->nullable();
            $table->integer('jumlah_baik')->default(0);
            $table->integer('jumlah_rusak_ringan')->default(0);
            $table->integer('jumlah_rusak_berat')->default(0);
            $table->date('tanggal_kembali')->nullable();

            $table->foreign('id_lokasi_asal')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_lokasi_tujuan')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('pengembalian', function (Blueprint $table) {
            $table->integer('id_kembali')->autoIncrement();
            $table->integer('id_keluar');
            $table->integer('id_user');
            $table->integer('id_lokasi');
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->integer('jumlah_baik')->default(0);
            $table->integer('jumlah_rusak_ringan')->default(0);
            $table->integer('jumlah_rusak_berat')->default(0);
            $table->string('catatan', 255)->nullable();
            $table->string('foto', 255)->nullable();

            $table->foreign('id_keluar')->references('id_keluar')->on('barang_keluar')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_user')->references('id_user')->on('user')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->cascadeOnUpdate()->restrictOnDelete();
        });

        $this->pindahkanDataLama();

        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn(['lokasi', 'status_barang']);
        });
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->dropColumn('status_barang');
        });
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->dropColumn('status_barang');
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['lokasi', 'stok_barang', 'pengembalian'] as $tabel) {
                DB::statement("ALTER TABLE \"{$tabel}\" ENABLE ROW LEVEL SECURITY");
            }
        }
    }

    /**
     * Data lama: lokasi teks bebas menjadi data master, stok barang menjadi stok per lokasi + kondisi,
     * transaksi lama mendapat lokasi dan rincian kondisi. Barang keluar lama dianggap Pemindahan
     * ke ruangan tujuannya (barang tetap milik sekolah, hanya pindah tempat).
     */
    private function pindahkanDataLama(): void
    {
        $idLokasi = function (?string $nama, string $jenis) {
            $nama = ucwords(trim((string) $nama)) ?: 'Gudang Utama';
            $ada = DB::table('lokasi')->where('nama_lokasi', $nama)->value('id_lokasi');

            return $ada ?? DB::table('lokasi')->insertGetId(['nama_lokasi' => $nama, 'jenis' => $jenis], 'id_lokasi');
        };
        $kondisi = fn (?string $s) => array_key_exists((string) $s, self::KOLOM) ? $s : 'Baik';

        $lokasiBarang = [];
        foreach (DB::table('barang')->get() as $b) {
            $lokasiBarang[$b->id_barang] = $idLokasi($b->lokasi, 'Gudang');
            if ($b->stok > 0) {
                DB::table('stok_barang')->insert([
                    'id_barang' => $b->id_barang, 'id_lokasi' => $lokasiBarang[$b->id_barang],
                    'kondisi' => $kondisi($b->status_barang), 'jumlah' => $b->stok,
                ]);
            }
        }

        foreach (DB::table('barang_masuk')->get() as $m) {
            DB::table('barang_masuk')->where('id_masuk', $m->id_masuk)->update([
                'id_lokasi' => $lokasiBarang[$m->id_barang] ?? $idLokasi(null, 'Gudang'),
                self::KOLOM[$kondisi($m->status_barang)] => $m->jumlah,
            ]);
        }

        foreach (DB::table('barang_keluar')->get() as $k) {
            $k2 = $kondisi($k->status_barang);
            $asal = $lokasiBarang[$k->id_barang] ?? $idLokasi(null, 'Gudang');
            $tujuan = $idLokasi($k->tujuan, 'Ruangan');
            DB::table('barang_keluar')->where('id_keluar', $k->id_keluar)->update([
                'jenis_keluar' => 'Pemindahan', 'id_lokasi_asal' => $asal, 'id_lokasi_tujuan' => $tujuan,
                self::KOLOM[$k2] => $k->jumlah,
            ]);

            // Pemindahan yang sudah disetujui: barangnya kini tercatat di ruangan tujuan
            if ($k->verifikasi === 'Disetujui') {
                $baris = DB::table('stok_barang')->where(['id_barang' => $k->id_barang, 'id_lokasi' => $tujuan, 'kondisi' => $k2]);
                if ($baris->exists()) {
                    $baris->increment('jumlah', $k->jumlah);
                } else {
                    DB::table('stok_barang')->insert(['id_barang' => $k->id_barang, 'id_lokasi' => $tujuan, 'kondisi' => $k2, 'jumlah' => $k->jumlah]);
                }
                DB::table('barang')->where('id_barang', $k->id_barang)->increment('stok', $k->jumlah);
            }
        }
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->string('lokasi', 100)->default('Gudang Utama');
            $table->string('status_barang', 30)->default('Baik');
        });
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->string('status_barang', 30)->default('Baik');
        });
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->string('status_barang', 30)->default('Baik');
        });

        Schema::dropIfExists('pengembalian');
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->dropForeign(['id_lokasi_asal']);
            $table->dropForeign(['id_lokasi_tujuan']);
            $table->dropColumn(['jenis_keluar', 'id_lokasi_asal', 'id_lokasi_tujuan', 'jumlah_baik', 'jumlah_rusak_ringan', 'jumlah_rusak_berat', 'tanggal_kembali']);
        });
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->dropForeign(['id_lokasi']);
            $table->dropColumn(['id_lokasi', 'jumlah_baik', 'jumlah_rusak_ringan', 'jumlah_rusak_berat']);
        });
        Schema::dropIfExists('stok_barang');
        Schema::dropIfExists('lokasi');
    }
};

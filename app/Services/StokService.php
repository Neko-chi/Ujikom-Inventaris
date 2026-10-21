<?php

namespace App\Services;

use App\Exceptions\StokTidakCukupException;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Models\StokBarang;
use InvalidArgumentException;

/**
 * Satu-satunya tempat yang mengubah stok barang.
 *
 * Stok disimpan per barang + lokasi + kondisi di tabel `stok_barang`
 * (contoh: Kursi Siswa · Gudang 2 · Rusak Ringan · 7 buah).
 * Kolom `barang.stok` adalah total semua lokasi dan kondisi, diperbarui otomatis.
 *
 * Aturan bisnis:
 *  - Barang masuk / pengembalian -> stok di lokasi tujuan bertambah sesuai kondisinya.
 *  - Barang keluar -> stok berkurang HANYA setelah disetujui Manager:
 *      Peminjaman  : stok lokasi asal berkurang (kembali bertambah saat dikembalikan)
 *      Pemindahan  : stok lokasi asal berkurang, stok lokasi tujuan bertambah (total tetap)
 *      Pemusnahan  : stok Rusak Berat lokasi asal berkurang permanen
 *  - Stok tidak boleh bernilai negatif.
 *
 * Method di sini sebaiknya dipanggil di dalam DB::transaction()
 * agar perubahan stok dan data transaksi tersimpan bersamaan.
 */
class StokService
{
    /** Jumlah yang tersedia untuk satu barang di satu lokasi dengan satu kondisi. */
    public function tersedia(int $idBarang, int $idLokasi, string $kondisi): int
    {
        return (int) StokBarang::where(['id_barang' => $idBarang, 'id_lokasi' => $idLokasi, 'kondisi' => $kondisi])->value('jumlah');
    }

    public function cukup(Barang $barang, int $idLokasi, string $kondisi, int $jumlah): bool
    {
        return $this->tersedia($barang->id_barang, $idLokasi, $kondisi) >= $jumlah;
    }

    public function tambah(Barang $barang, int $idLokasi, string $kondisi, int $jumlah): void
    {
        $this->pastikanValid($kondisi, $jumlah);

        $baris = $this->baris($barang, $idLokasi, $kondisi);
        $baris->jumlah += $jumlah;
        $baris->save();

        $this->perbaruiTotal($barang);
    }

    /**
     * @throws StokTidakCukupException jika stok di lokasi dan kondisi tersebut kurang
     */
    public function kurangi(Barang $barang, int $idLokasi, string $kondisi, int $jumlah): void
    {
        $this->pastikanValid($kondisi, $jumlah);

        $baris = $this->baris($barang, $idLokasi, $kondisi);
        if ($baris->jumlah < $jumlah) {
            throw new StokTidakCukupException($barang, $jumlah, $baris->jumlah, $kondisi, Lokasi::find($idLokasi)?->nama_lokasi);
        }

        $baris->jumlah -= $jumlah;
        $baris->save();

        $this->perbaruiTotal($barang);
    }

    /**
     * Menambah stok untuk beberapa kondisi sekaligus.
     *
     * @param  array<string, int>  $rincian  contoh ['Baik' => 8, 'Rusak Ringan' => 2]
     */
    public function tambahRincian(Barang $barang, int $idLokasi, array $rincian): void
    {
        foreach ($rincian as $kondisi => $jumlah) {
            if ($jumlah > 0) {
                $this->tambah($barang, $idLokasi, $kondisi, $jumlah);
            }
        }
    }

    /**
     * Mengurangi stok untuk beberapa kondisi sekaligus. Semua kondisi dicek dulu,
     * sehingga bila salah satu kurang tidak ada stok yang terlanjur berubah.
     *
     * @param  array<string, int>  $rincian
     *
     * @throws StokTidakCukupException
     */
    public function kurangiRincian(Barang $barang, int $idLokasi, array $rincian): void
    {
        foreach ($rincian as $kondisi => $jumlah) {
            $tersedia = $this->baris($barang, $idLokasi, $kondisi)->jumlah;
            if ($jumlah > 0 && $tersedia < $jumlah) {
                throw new StokTidakCukupException($barang, $jumlah, $tersedia, $kondisi, Lokasi::find($idLokasi)?->nama_lokasi);
            }
        }

        foreach ($rincian as $kondisi => $jumlah) {
            if ($jumlah > 0) {
                $this->kurangi($barang, $idLokasi, $kondisi, $jumlah);
            }
        }
    }

    /** Baris stok (dikunci untuk transaksi), dibuat baru dengan jumlah 0 bila belum ada. */
    private function baris(Barang $barang, int $idLokasi, string $kondisi): StokBarang
    {
        return StokBarang::lockForUpdate()->firstOrNew(
            ['id_barang' => $barang->id_barang, 'id_lokasi' => $idLokasi, 'kondisi' => $kondisi],
            ['jumlah' => 0]
        );
    }

    /** Kolom barang.stok = total semua lokasi dan kondisi. */
    private function perbaruiTotal(Barang $barang): void
    {
        $barang->stok = (int) StokBarang::where('id_barang', $barang->id_barang)->sum('jumlah');
        $barang->save();
    }

    private function pastikanValid(string $kondisi, int $jumlah): void
    {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
        }
        if (! in_array($kondisi, Barang::KONDISI, true)) {
            throw new InvalidArgumentException("Kondisi {$kondisi} tidak dikenal.");
        }
    }
}

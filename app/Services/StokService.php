<?php

namespace App\Services;

use App\Exceptions\StokTidakCukupException;
use App\Models\Barang;
use InvalidArgumentException;

/**
 * Satu-satunya tempat yang mengubah kolom `stok` pada tabel barang.
 *
 * Aturan bisnis:
 *  - Barang masuk  -> stok bertambah.
 *  - Barang keluar -> stok berkurang HANYA setelah disetujui Manager.
 *  - Stok tidak boleh bernilai negatif.
 *
 * Method di sini sebaiknya dipanggil di dalam DB::transaction()
 * agar perubahan stok dan data transaksi tersimpan bersamaan.
 */
class StokService
{
    public function tambah(Barang $barang, int $jumlah): Barang
    {
        $this->pastikanJumlahValid($jumlah);

        $barang->stok += $jumlah;
        $barang->save();

        return $barang;
    }

    /**
     * @throws StokTidakCukupException jika stok kurang dari jumlah yang diminta
     */
    public function kurangi(Barang $barang, int $jumlah): Barang
    {
        $this->pastikanJumlahValid($jumlah);

        if (! $this->cukup($barang, $jumlah)) {
            throw new StokTidakCukupException($barang, $jumlah);
        }

        $barang->stok -= $jumlah;
        $barang->save();

        return $barang;
    }

    public function cukup(Barang $barang, int $jumlah): bool
    {
        return $barang->stok >= $jumlah;
    }

    private function pastikanJumlahValid(int $jumlah): void
    {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
        }
    }
}

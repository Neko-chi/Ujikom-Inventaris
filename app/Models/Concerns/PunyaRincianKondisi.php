<?php

namespace App\Models\Concerns;

use App\Models\Barang;

/**
 * Dipakai model transaksi yang mencatat jumlah per kondisi
 * (kolom jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat):
 * barang masuk, barang keluar, dan pengembalian.
 */
trait PunyaRincianKondisi
{
    /**
     * Rincian jumlah per kondisi, contoh ['Baik' => 5, 'Rusak Ringan' => 10, 'Rusak Berat' => 5].
     *
     * @return array<string, int>
     */
    public function rincian(): array
    {
        $hasil = [];
        foreach (Barang::KOLOM_KONDISI as $kondisi => $kolom) {
            $hasil[$kondisi] = (int) $this->{$kolom};
        }

        return $hasil;
    }

    /** Teks ringkas untuk tabel, contoh "5 Baik · 10 Rusak Ringan" (kondisi bernilai 0 tidak ditulis). */
    public function ringkasKondisi(): string
    {
        $bagian = [];
        foreach ($this->rincian() as $kondisi => $jumlah) {
            if ($jumlah > 0) {
                $bagian[] = "{$jumlah} {$kondisi}";
            }
        }

        return implode(' · ', $bagian) ?: '-';
    }

    /**
     * Mengisi kolom jumlah per kondisi dan kolom total `jumlah` dari sebuah rincian.
     *
     * @param  array<string, int>  $rincian
     */
    public function isiRincian(array $rincian): static
    {
        foreach (Barang::KOLOM_KONDISI as $kondisi => $kolom) {
            $this->{$kolom} = (int) ($rincian[$kondisi] ?? 0);
        }
        $this->jumlah = array_sum($this->rincian());

        return $this;
    }

    /**
     * Mengambil rincian dari data form (input jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat).
     *
     * @return array<string, int>
     */
    public static function rincianDariInput(array $data): array
    {
        $hasil = [];
        foreach (Barang::KOLOM_KONDISI as $kondisi => $kolom) {
            $hasil[$kondisi] = (int) ($data[$kolom] ?? 0);
        }

        return $hasil;
    }
}

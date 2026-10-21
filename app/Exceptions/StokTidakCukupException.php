<?php

namespace App\Exceptions;

use App\Models\Barang;
use RuntimeException;

/**
 * Dilempar ketika stok barang (di satu lokasi dan kondisi) tidak mencukupi untuk dikurangi.
 */
class StokTidakCukupException extends RuntimeException
{
    public function __construct(Barang $barang, int $jumlahDiminta, int $tersedia = 0, ?string $kondisi = null, ?string $lokasi = null)
    {
        $rincian = trim(($kondisi ? "kondisi {$kondisi} " : '').($lokasi ? "di {$lokasi} " : ''));

        parent::__construct(sprintf(
            'Stok %s (%s)%s tidak mencukupi. Stok tersedia %d %s, diminta %d %s.',
            $barang->nama_barang,
            $barang->kode_barang,
            $rincian ? " {$rincian}" : '',
            $tersedia,
            $barang->satuan,
            $jumlahDiminta,
            $barang->satuan
        ));
    }
}

<?php

namespace App\Exceptions;

use App\Models\Barang;
use RuntimeException;

/**
 * Dilempar ketika stok barang tidak mencukupi untuk dikurangi.
 */
class StokTidakCukupException extends RuntimeException
{
    public function __construct(Barang $barang, int $jumlahDiminta)
    {
        parent::__construct(sprintf(
            'Stok %s (%s) tidak mencukupi. Stok tersedia %d %s, diminta %d %s.',
            $barang->nama_barang,
            $barang->kode_barang,
            $barang->stok,
            $barang->satuan,
            $jumlahDiminta,
            $barang->satuan
        ));
    }
}

<?php

namespace Tests;

use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Helper: membuat satu barang beserta kategorinya untuk keperluan pengujian.
     */
    protected function buatBarang(array $data = [], string $namaKategori = 'Alat Tulis'): Barang
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => $namaKategori]);

        return Barang::create(array_merge([
            'kode_barang' => Barang::generateKode($kategori),
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Pulpen',
            'stok' => 10,
            'satuan' => 'pack',
            'lokasi' => 'sarpras',
            'status_barang' => 'Baik',
        ], $data));
    }
}

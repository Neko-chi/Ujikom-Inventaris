<?php

namespace Tests;

use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // File yang diunggah selama pengujian disimpan di penyimpanan palsu (tidak ke disk asli).
        Storage::fake(config('filesystems.upload_disk'));
    }

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

    /**
     * Helper: gambar palsu untuk menguji fitur unggah foto.
     */
    protected function gambarPalsu(string $nama = 'bukti.jpg', int $ukuranKb = 100): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 640, 480)->size($ukuranKb);
    }

    protected function disk()
    {
        return Storage::disk(config('filesystems.upload_disk'));
    }
}

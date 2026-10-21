<?php

namespace Tests;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\StokBarang;
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
     * Helper: lokasi (dibuat bila belum ada). Default "Gudang Utama" berjenis Gudang.
     */
    protected function lokasi(string $nama = 'Gudang Utama', string $jenis = Lokasi::GUDANG): Lokasi
    {
        return Lokasi::firstOrCreate(['nama_lokasi' => $nama], ['jenis' => $jenis]);
    }

    /**
     * Helper: membuat satu barang beserta kategorinya untuk keperluan pengujian.
     * $data['stok'] = jumlah kondisi Baik di Gudang Utama (default 10);
     * $rincian (opsional) = [Baik, Rusak Ringan, Rusak Berat] di Gudang Utama.
     */
    protected function buatBarang(array $data = [], string $namaKategori = 'Alat Tulis', ?array $rincian = null): Barang
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => $namaKategori]);
        $rincian ??= [$data['stok'] ?? 10, 0, 0];

        $barang = Barang::create(array_merge([
            'kode_barang' => Barang::generateKode($kategori),
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Pulpen',
            'satuan' => 'pack',
        ], $data, ['stok' => array_sum($rincian)]));

        $this->isiStok($barang, $this->lokasi(), $rincian);

        return $barang;
    }

    /**
     * Helper: menambah stok awal barang di satu lokasi, [Baik, Rusak Ringan, Rusak Berat].
     */
    protected function isiStok(Barang $barang, Lokasi $lokasi, array $rincian): void
    {
        foreach (Barang::KONDISI as $i => $kondisi) {
            if (($rincian[$i] ?? 0) > 0) {
                StokBarang::create(['id_barang' => $barang->id_barang, 'id_lokasi' => $lokasi->id_lokasi, 'kondisi' => $kondisi, 'jumlah' => $rincian[$i]]);
            }
        }
        $barang->update(['stok' => (int) StokBarang::where('id_barang', $barang->id_barang)->sum('jumlah')]);
    }

    /**
     * Helper: stok satu barang di satu lokasi dan kondisi.
     */
    protected function stokDi(Barang $barang, Lokasi $lokasi, string $kondisi = Barang::BAIK): int
    {
        return (int) StokBarang::where(['id_barang' => $barang->id_barang, 'id_lokasi' => $lokasi->id_lokasi, 'kondisi' => $kondisi])->value('jumlah');
    }

    /**
     * Helper: input form jumlah per kondisi.
     */
    protected function kondisi(int $baik, int $ringan = 0, int $berat = 0): array
    {
        return ['jumlah_baik' => $baik, 'jumlah_rusak_ringan' => $ringan, 'jumlah_rusak_berat' => $berat];
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

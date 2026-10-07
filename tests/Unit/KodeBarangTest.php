<?php

namespace Tests\Unit;

use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian unit pembuatan kode barang otomatis (Barang::prefixKategori & Barang::generateKode).
 */
class KodeBarangTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefix_sesuai_kamus_data(): void
    {
        $this->assertSame('AT', Barang::prefixKategori('Alat Tulis'));
        $this->assertSame('FR', Barang::prefixKategori('Furnitur'));
        $this->assertSame('KN', Barang::prefixKategori('Kebersihan'));
        $this->assertSame('AO', Barang::prefixKategori('Alat Olahraga'));
        $this->assertSame('EK', Barang::prefixKategori('Elektronik'));
    }

    public function test_prefix_kategori_baru_dua_kata_memakai_huruf_awal(): void
    {
        $this->assertSame('AL', Barang::prefixKategori('Alat Laboratorium'));
    }

    public function test_prefix_kategori_baru_satu_kata_memakai_dua_huruf_pertama(): void
    {
        $this->assertSame('BU', Barang::prefixKategori('buku'));
    }

    public function test_kode_pertama_dalam_kategori_adalah_001(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);

        $this->assertSame('EK-001', Barang::generateKode($kategori));
    }

    public function test_kode_melanjutkan_nomor_terbesar(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Alat Olahraga']);
        $this->buatBarang(['kode_barang' => 'AO-001', 'id_kategori' => $kategori->id_kategori]);
        $this->buatBarang(['kode_barang' => 'AO-007', 'id_kategori' => $kategori->id_kategori]);

        $this->assertSame('AO-008', Barang::generateKode($kategori));
    }
}

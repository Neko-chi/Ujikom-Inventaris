<?php

namespace Tests\Unit;

use App\Exceptions\StokTidakCukupException;
use App\Services\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Pengujian unit untuk logika perubahan stok (App\Services\StokService).
 */
class StokServiceTest extends TestCase
{
    use RefreshDatabase;

    private StokService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StokService;
    }

    public function test_tambah_stok_menambah_jumlah_stok(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->service->tambah($barang, 5);

        $this->assertSame(15, $barang->fresh()->stok);
    }

    public function test_kurangi_stok_mengurangi_jumlah_stok(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->service->kurangi($barang, 4);

        $this->assertSame(6, $barang->fresh()->stok);
    }

    public function test_kurangi_stok_sampai_habis_diperbolehkan(): void
    {
        $barang = $this->buatBarang(['stok' => 3]);

        $this->service->kurangi($barang, 3);

        $this->assertSame(0, $barang->fresh()->stok);
    }

    public function test_kurangi_stok_melebihi_stok_melempar_exception(): void
    {
        $barang = $this->buatBarang(['stok' => 2]);

        $this->expectException(StokTidakCukupException::class);

        $this->service->kurangi($barang, 5);
    }

    public function test_stok_tidak_berubah_jika_pengurangan_gagal(): void
    {
        $barang = $this->buatBarang(['stok' => 2]);

        try {
            $this->service->kurangi($barang, 5);
        } catch (StokTidakCukupException) {
            // diharapkan
        }

        $this->assertSame(2, $barang->fresh()->stok);
    }

    public function test_jumlah_nol_atau_negatif_ditolak(): void
    {
        $barang = $this->buatBarang();

        $this->expectException(InvalidArgumentException::class);

        $this->service->tambah($barang, 0);
    }

    public function test_cek_stok_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);

        $this->assertTrue($this->service->cukup($barang, 5));
        $this->assertFalse($this->service->cukup($barang, 6));
    }
}

<?php

namespace Tests\Unit;

use App\Exceptions\StokTidakCukupException;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Services\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Pengujian unit StokService: stok per barang + lokasi + kondisi,
 * dan kolom barang.stok sebagai total semua lokasi.
 */
class StokServiceTest extends TestCase
{
    use RefreshDatabase;

    private StokService $stok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stok = new StokService;
    }

    public function test_tambah_stok_per_lokasi_dan_kondisi(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $gudang2 = $this->lokasi('Gudang 2');

        $this->stok->tambah($barang, $gudang2->id_lokasi, Barang::RUSAK_RINGAN, 3);

        $this->assertSame(3, $this->stokDi($barang, $gudang2, Barang::RUSAK_RINGAN));
        $this->assertSame(10, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(13, $barang->fresh()->stok); // total semua lokasi
    }

    public function test_kurangi_stok_mengurangi_jumlah_di_lokasi_dan_kondisi_tersebut(): void
    {
        $barang = $this->buatBarang([], 'Alat Tulis', [10, 4, 0]);

        $this->stok->kurangi($barang, $this->lokasi()->id_lokasi, Barang::RUSAK_RINGAN, 3);

        $this->assertSame(1, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
        $this->assertSame(10, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(11, $barang->fresh()->stok);
    }

    public function test_kurangi_stok_sampai_habis_diperbolehkan(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);

        $this->stok->kurangi($barang, $this->lokasi()->id_lokasi, Barang::BAIK, 5);

        $this->assertSame(0, $barang->fresh()->stok);
    }

    public function test_kurangi_melebihi_stok_kondisi_tersebut_melempar_exception(): void
    {
        // Total 10, tetapi Rusak Ringan hanya 2
        $barang = $this->buatBarang([], 'Alat Tulis', [8, 2, 0]);

        $this->expectException(StokTidakCukupException::class);
        $this->expectExceptionMessage('kondisi Rusak Ringan di Gudang Utama');

        $this->stok->kurangi($barang, $this->lokasi()->id_lokasi, Barang::RUSAK_RINGAN, 3);
    }

    public function test_kurangi_rincian_tidak_mengubah_apa_pun_bila_salah_satu_kurang(): void
    {
        $barang = $this->buatBarang([], 'Alat Tulis', [8, 2, 0]);

        try {
            $this->stok->kurangiRincian($barang, $this->lokasi()->id_lokasi, [Barang::BAIK => 5, Barang::RUSAK_RINGAN => 3]);
            $this->fail('Seharusnya melempar StokTidakCukupException.');
        } catch (StokTidakCukupException) {
            // diharapkan
        }

        $this->assertSame(8, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_stok_di_lokasi_lain_tidak_bisa_dipakai(): void
    {
        $barang = $this->buatBarang(['stok' => 10]); // semuanya di Gudang Utama
        $lab = $this->lokasi('Lab TKJ 3', Lokasi::RUANGAN);

        $this->assertFalse($this->stok->cukup($barang, $lab->id_lokasi, Barang::BAIK, 1));
        $this->expectException(StokTidakCukupException::class);
        $this->stok->kurangi($barang, $lab->id_lokasi, Barang::BAIK, 1);
    }

    public function test_jumlah_nol_negatif_atau_kondisi_tidak_dikenal_ditolak(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $idLokasi = $this->lokasi()->id_lokasi;

        foreach ([[0, Barang::BAIK], [-2, Barang::BAIK], [1, 'Hilang']] as [$jumlah, $kondisi]) {
            try {
                $this->stok->tambah($barang, $idLokasi, $kondisi, $jumlah);
                $this->fail("Seharusnya ditolak: {$jumlah} {$kondisi}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(5, $barang->fresh()->stok);
    }

    public function test_cek_stok_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $idLokasi = $this->lokasi()->id_lokasi;

        $this->assertTrue($this->stok->cukup($barang, $idLokasi, Barang::BAIK, 5));
        $this->assertFalse($this->stok->cukup($barang, $idLokasi, Barang::BAIK, 6));
        $this->assertFalse($this->stok->cukup($barang, $idLokasi, Barang::RUSAK_BERAT, 1));
    }
}

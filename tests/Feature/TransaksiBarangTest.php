<?php

namespace Tests\Feature;

use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian alur transaksi: Operator mencatat barang masuk & mengajukan barang keluar,
 * Admin mengoreksi barang masuk & memverifikasi barang keluar.
 */
class TransaksiBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->operator = User::factory()->operator()->create();
    }

    private function dataKeluar(int $idBarang, int $jumlah): array
    {
        return [
            'id_barang' => $idBarang,
            'tanggal' => '2026-10-05',
            'jumlah' => $jumlah,
            'pemohon' => 'Budi Santoso',
            'tujuan' => 'Lab TKJ3',
            'status_barang' => 'Baik',
        ];
    }

    private function buatPermintaanKeluar(int $idBarang, int $jumlah): BarangKeluar
    {
        return BarangKeluar::create($this->dataKeluar($idBarang, $jumlah) + ['id_user' => $this->operator->id_user]);
    }

    private function buatBarangMasuk(int $idBarang, int $jumlah): BarangMasuk
    {
        return BarangMasuk::create([
            'id_barang' => $idBarang, 'id_user' => $this->operator->id_user, 'tanggal' => '2026-09-15',
            'jumlah' => $jumlah, 'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik',
        ]);
    }

    // ---------- Barang masuk ----------

    public function test_operator_mencatat_barang_masuk_dan_stok_bertambah(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);

        $this->actingAs($this->operator)->post('/barang-masuk', [
            'id_barang' => $barang->id_barang,
            'tanggal' => '2026-09-15',
            'jumlah' => 9,
            'sumber_barang' => 'Dana BOS 2026',
            'status_barang' => 'Baik',
        ])->assertRedirect('/barang-masuk');

        $this->assertSame(14, $barang->fresh()->stok);
        $this->assertDatabaseHas('barang_masuk', ['id_barang' => $barang->id_barang, 'id_user' => $this->operator->id_user, 'jumlah' => 9]);
    }

    public function test_admin_mengoreksi_jumlah_barang_masuk_dan_stok_menyesuaikan(): void
    {
        $barang = $this->buatBarang(['stok' => 15]);
        $masuk = $this->buatBarangMasuk($barang->id_barang, 10);

        $this->actingAs($this->admin)->put(route('barang-masuk.update', $masuk), [
            'id_barang' => $barang->id_barang,
            'tanggal' => '2026-09-15',
            'jumlah' => 4,
            'sumber_barang' => 'Pembelian',
            'status_barang' => 'Baik',
        ])->assertRedirect('/barang-masuk');

        $this->assertSame(9, $barang->fresh()->stok); // 15 - (10 - 4)
    }

    public function test_admin_menghapus_barang_masuk_dan_stok_dikembalikan(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $masuk = $this->buatBarangMasuk($barang->id_barang, 8);

        $this->actingAs($this->admin)->delete(route('barang-masuk.destroy', $masuk));

        $this->assertSame(12, $barang->fresh()->stok);
        $this->assertModelMissing($masuk);
    }

    public function test_hapus_barang_masuk_ditolak_jika_stok_sudah_terpakai(): void
    {
        $barang = $this->buatBarang(['stok' => 3]);
        $masuk = $this->buatBarangMasuk($barang->id_barang, 8);

        $this->actingAs($this->admin)
            ->delete(route('barang-masuk.destroy', $masuk))
            ->assertSessionHas('gagal');

        $this->assertModelExists($masuk);
        $this->assertSame(3, $barang->fresh()->stok);
    }

    public function test_jumlah_barang_masuk_minimal_1(): void
    {
        $barang = $this->buatBarang();

        $this->actingAs($this->operator)->post('/barang-masuk', [
            'id_barang' => $barang->id_barang, 'tanggal' => '2026-09-15', 'jumlah' => 0,
            'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik',
        ])->assertSessionHasErrors('jumlah');
    }

    // ---------- Barang keluar ----------

    public function test_pengajuan_barang_keluar_berstatus_pending_dan_stok_tetap(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->dataKeluar($barang->id_barang, 4))
            ->assertRedirect('/barang-keluar');

        $this->assertDatabaseHas('barang_keluar', [
            'id_barang' => $barang->id_barang,
            'id_user' => $this->operator->id_user,
            'pemohon' => 'Budi Santoso',
            'verifikasi' => 'Pending',
            'id_verifikator' => null,
        ]);
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_pengajuan_wajib_mengisi_pemohon(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $data = $this->dataKeluar($barang->id_barang, 1);
        unset($data['pemohon']);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $data)
            ->assertSessionHasErrors('pemohon');
    }

    public function test_pengajuan_melebihi_stok_ditolak_validasi(): void
    {
        $barang = $this->buatBarang(['stok' => 4]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->dataKeluar($barang->id_barang, 50))
            ->assertSessionHasErrors('jumlah');

        $this->assertSame(0, BarangKeluar::count());
    }

    // ---------- Verifikasi oleh Admin ----------

    public function test_admin_menyetujui_mengurangi_stok_dan_mencatat_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);

        $this->actingAs($this->admin)
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('sukses');

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DISETUJUI, $keluar->verifikasi);
        $this->assertSame($this->admin->id_user, $keluar->id_verifikator);
        $this->assertTrue($keluar->tanggal_verifikasi->isToday());
        $this->assertSame(6, $barang->fresh()->stok);
    }

    public function test_admin_menolak_wajib_alasan_dan_stok_tidak_berubah(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);
        $this->actingAs($this->admin);

        $this->patch(route('barang-keluar.tolak', $keluar))->assertSessionHasErrors('catatan_verifikasi');
        $this->assertSame(BarangKeluar::PENDING, $keluar->fresh()->verifikasi);

        $this->patch(route('barang-keluar.tolak', $keluar), ['catatan_verifikasi' => 'Barang masih dipakai.']);

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DITOLAK, $keluar->verifikasi);
        $this->assertSame('Barang masih dipakai.', $keluar->catatan_verifikasi);
        $this->assertSame($this->admin->id_user, $keluar->id_verifikator);
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_persetujuan_gagal_jika_stok_sudah_tidak_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 3]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 100);

        $this->actingAs($this->admin)
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('gagal');

        $keluar->refresh();
        $this->assertSame(BarangKeluar::PENDING, $keluar->verifikasi);
        $this->assertNull($keluar->id_verifikator);
        $this->assertSame(3, $barang->fresh()->stok);
    }

    public function test_permintaan_yang_sudah_diverifikasi_tidak_bisa_diverifikasi_ulang(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);

        $this->actingAs($this->admin);
        $this->patch(route('barang-keluar.setujui', $keluar));
        $this->patch(route('barang-keluar.setujui', $keluar))->assertSessionHas('gagal');

        $this->assertSame(6, $barang->fresh()->stok); // hanya berkurang sekali
    }

    public function test_detail_barang_keluar_menampilkan_pengaju_dan_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);
        $this->actingAs($this->admin)->patch(route('barang-keluar.setujui', $keluar));

        $this->actingAs($this->operator)
            ->get(route('barang-keluar.show', $keluar))
            ->assertOk()
            ->assertSee($this->operator->nama_user)
            ->assertSee($this->admin->nama_user)
            ->assertSee('Disetujui');
    }
}

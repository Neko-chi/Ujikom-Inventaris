<?php

namespace Tests\Feature;

use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Pengujian alur transaksi: Operator mencatat barang masuk & mengajukan barang keluar,
 * Admin mengoreksi barang masuk, Manager memverifikasi barang keluar.
 */
class TransaksiBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
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

    /** Data form pengajuan lengkap dengan foto bukti (wajib). */
    private function formKeluar(int $idBarang, int $jumlah): array
    {
        return $this->dataKeluar($idBarang, $jumlah) + ['foto' => $this->gambarPalsu()];
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
            ->post('/barang-keluar', $this->formKeluar($barang->id_barang, 4))
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
        $data = $this->formKeluar($barang->id_barang, 1);
        unset($data['pemohon']);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $data)
            ->assertSessionHasErrors('pemohon');
    }

    public function test_pengajuan_melebihi_stok_ditolak_validasi(): void
    {
        $barang = $this->buatBarang(['stok' => 4]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->formKeluar($barang->id_barang, 50))
            ->assertSessionHasErrors('jumlah');

        $this->assertSame(0, BarangKeluar::count());
    }

    // ---------- Verifikasi oleh Admin ----------

    public function test_manager_menyetujui_mengurangi_stok_dan_mencatat_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);

        $this->actingAs($this->manager)
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('sukses');

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DISETUJUI, $keluar->verifikasi);
        $this->assertSame($this->manager->id_user, $keluar->id_verifikator);
        $this->assertTrue($keluar->tanggal_verifikasi->isToday());
        $this->assertSame(6, $barang->fresh()->stok);
    }

    public function test_manager_menolak_wajib_alasan_dan_stok_tidak_berubah(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);
        $this->actingAs($this->manager);

        $this->patch(route('barang-keluar.tolak', $keluar))->assertSessionHasErrors('catatan_verifikasi');
        $this->assertSame(BarangKeluar::PENDING, $keluar->fresh()->verifikasi);

        $this->patch(route('barang-keluar.tolak', $keluar), ['catatan_verifikasi' => 'Barang masih dipakai.']);

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DITOLAK, $keluar->verifikasi);
        $this->assertSame('Barang masih dipakai.', $keluar->catatan_verifikasi);
        $this->assertSame($this->manager->id_user, $keluar->id_verifikator);
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_persetujuan_gagal_jika_stok_sudah_tidak_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 3]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 100);

        $this->actingAs($this->manager)
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

        $this->actingAs($this->manager);
        $this->patch(route('barang-keluar.setujui', $keluar));
        $this->patch(route('barang-keluar.setujui', $keluar))->assertSessionHas('gagal');

        $this->assertSame(6, $barang->fresh()->stok); // hanya berkurang sekali
    }

    public function test_detail_barang_keluar_menampilkan_pengaju_dan_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang->id_barang, 4);
        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        $this->actingAs($this->operator)
            ->get(route('barang-keluar.show', $keluar))
            ->assertOk()
            ->assertSee($this->operator->nama_user)
            ->assertSee($this->manager->nama_user)
            ->assertSee('Disetujui');
    }

    // ---------- Unggah foto bukti ----------

    public function test_pengajuan_barang_keluar_wajib_menyertakan_foto(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->dataKeluar($barang->id_barang, 2))
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_foto_barang_keluar_tersimpan_dan_dapat_ditampilkan(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)->post('/barang-keluar', $this->formKeluar($barang->id_barang, 2));

        $keluar = BarangKeluar::firstOrFail();
        $this->assertStringStartsWith('barang-keluar/', $keluar->foto);
        $this->disk()->assertExists($keluar->foto);

        $this->actingAs($this->manager)
            ->get(route('barang-keluar.show', $keluar))
            ->assertOk()
            ->assertSee($keluar->foto_url);
    }

    public function test_file_bukan_gambar_atau_lebih_dari_2mb_ditolak(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $this->actingAs($this->operator);

        $data = $this->dataKeluar($barang->id_barang, 1) + ['foto' => UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf')];
        $this->post('/barang-keluar', $data)->assertSessionHasErrors('foto');

        $data = $this->dataKeluar($barang->id_barang, 1) + ['foto' => $this->gambarPalsu('besar.jpg', 3000)];
        $this->post('/barang-keluar', $data)->assertSessionHasErrors('foto');

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_foto_barang_masuk_opsional_dan_ikut_terhapus(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $dataMasuk = [
            'id_barang' => $barang->id_barang, 'tanggal' => '2026-09-15', 'jumlah' => 3,
            'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik',
        ];

        // Tanpa foto tetap berhasil
        $this->actingAs($this->operator)->post('/barang-masuk', $dataMasuk)->assertRedirect('/barang-masuk');
        $this->assertNull(BarangMasuk::firstOrFail()->foto);

        // Dengan foto: file tersimpan
        $this->post('/barang-masuk', $dataMasuk + ['foto' => $this->gambarPalsu('nota.png')]);
        $masuk = BarangMasuk::whereNotNull('foto')->firstOrFail();
        $this->disk()->assertExists($masuk->foto);

        // Admin menghapus data: file foto ikut dihapus
        $this->actingAs($this->admin)->delete(route('barang-masuk.destroy', $masuk));
        $this->disk()->assertMissing($masuk->foto);
    }

    public function test_foto_baru_dihapus_kembali_bila_koreksi_gagal(): void
    {
        $barang = $this->buatBarang(['stok' => 2]);
        $masuk = $this->buatBarangMasuk($barang->id_barang, 8);

        // Mengurangi jumlah 8 -> 1 butuh stok 7 dikurangi, padahal stok hanya 2: koreksi gagal.
        $this->actingAs($this->admin)->put(route('barang-masuk.update', $masuk), [
            'id_barang' => $barang->id_barang, 'tanggal' => '2026-09-15', 'jumlah' => 1,
            'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik', 'foto' => $this->gambarPalsu(),
        ])->assertSessionHas('gagal');

        $this->assertNull($masuk->fresh()->foto);
        $this->assertSame([], $this->disk()->allFiles('barang-masuk'));
    }
}
